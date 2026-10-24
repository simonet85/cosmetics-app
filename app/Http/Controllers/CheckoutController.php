<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Simonet85\LaravelMoneyFusion\MoneyFusionService;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index()
    {
        $cartItems = array_values($this->cart->lines());

        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        ['subtotal' => $subtotal, 'tax' => $tax, 'shipping' => $shipping, 'total' => $total] = $this->cart->totals();

        $enabledPaymentMethods = PaymentMethods::enabled();

        return view('checkout.index', compact('cartItems', 'subtotal', 'tax', 'shipping', 'total', 'enabledPaymentMethods'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'quartier' => 'required|string|max:255',
            'payment_method' => 'required|in:credit_card,paypal,bank_transfer,cash_on_delivery,moneyfusion',
            'billing_same_as_shipping' => 'nullable',
            // Billing address fields (required only when checkbox is NOT checked)
            'billing_first_name' => 'required_without:billing_same_as_shipping|nullable|string|max:255',
            'billing_last_name' => 'required_without:billing_same_as_shipping|nullable|string|max:255',
            'billing_city' => 'required_without:billing_same_as_shipping|nullable|string|max:255',
            'billing_quartier' => 'required_without:billing_same_as_shipping|nullable|string|max:255',
        ], [
            // Shipping address messages
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'city.required' => 'La ville est obligatoire.',
            'quartier.required' => 'Le quartier est obligatoire.',
            'payment_method.required' => 'La méthode de paiement est obligatoire.',

            // Billing address messages
            'billing_first_name.required_without' => 'Le prénom de facturation est obligatoire.',
            'billing_last_name.required_without' => 'Le nom de facturation est obligatoire.',
            'billing_city.required_without' => 'La ville de facturation est obligatoire.',
            'billing_quartier.required_without' => 'Le quartier de facturation est obligatoire.',
        ]);

        // The admin can switch payment methods off in the settings: enforce it here, not just in the form.
        if (! (PaymentMethods::enabled()[$request->payment_method] ?? false)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['payment_method' => 'Ce mode de paiement n\'est pas disponible.']);
        }

        // Priced from the database right now, not from what the session remembered at "add to cart"
        $lines = $this->cart->lines();

        if (empty($lines)) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        try {
            DB::beginTransaction();

            ['subtotal' => $subtotal, 'tax' => $tax, 'shipping' => $shipping, 'total' => $total] = $this->cart->totals($lines);

            // Prepare shipping address
            $shippingAddress = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'city' => $request->city,
                'quartier' => $request->quartier,
                'phone' => $request->phone,
            ];

            // Prepare billing address
            $billingAddress = $request->billing_same_as_shipping
                ? $shippingAddress
                : [
                    'first_name' => $request->billing_first_name,
                    'last_name' => $request->billing_last_name,
                    'city' => $request->billing_city,
                    'quartier' => $request->billing_quartier,
                ];

            // Create order
            $order = Order::create([
                'user_id' => auth()->check() ? auth()->id() : null,
                'order_number' => 'ORD-'.strtoupper(uniqid()),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shipping,
                'total' => $total,
                'customer_email' => $request->email,
                'customer_phone' => $request->phone,
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress,
                'notes' => $request->notes,
            ]);

            // Create order items and update stock. Rows are locked so two simultaneous
            // orders cannot both take the last unit.
            foreach ($lines as $line) {
                $item = ['product_id' => $line['product']->id, 'variant_id' => $line['variant']?->id, 'quantity' => $line['quantity'], 'price' => $line['price']];
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuffisant pour {$product->name}");
                }

                $variant = null;
                if (! empty($item['variant_id'])) {
                    $variant = ProductVariant::whereKey($item['variant_id'])
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $variant || ! $variant->is_active) {
                        throw new \Exception("Variante indisponible pour {$product->name}");
                    }

                    if ($variant->stock < $item['quantity']) {
                        throw new \Exception("Stock insuffisant pour {$product->name} ({$variant->label})");
                    }
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                ]);

                $product->decrement('stock', $item['quantity']);
                $variant?->decrement('stock', $item['quantity']);
            }

            // Handle MoneyFusion payment
            if ($request->payment_method === 'moneyfusion') {
                try {
                    // Prepare articles for MoneyFusion
                    $articles = [];
                    foreach ($lines as $line) {
                        $articles[] = [
                            'name' => $line['product']->name,
                            'price' => (int) $line['price'],
                            'quantity' => $line['quantity'],
                        ];
                    }

                    // Create MoneyFusion payment
                    // Resolved here, not injected in the constructor: the gateway throws when it is not
                    // configured, which would otherwise break every other payment method too.
                    $paymentResult = app(MoneyFusionService::class)->createPayment([
                        'total_price' => (float) $total,
                        'articles' => $articles,
                        'nom_client' => $request->first_name.' '.$request->last_name,
                        'numero_send' => $request->phone,
                        'user_id' => auth()->id(),
                        'order_id' => $order->id,
                        // Name the order in the return URL: the payer is not guaranteed to come back with the token.
                        // The callback never trusts it anyway, it asks MoneyFusion for the real status.
                        'return_url' => route('payment.callback', ['order' => $order->id]),
                    ]);

                    // Update order with MoneyFusion information
                    $order->update([
                        'moneyfusion_token' => $paymentResult['token'],
                        'moneyfusion_payment_url' => $paymentResult['url'],
                    ]);

                    DB::commit();

                    // Redirect to MoneyFusion payment page
                    return redirect($paymentResult['url']);

                } catch (\Exception $e) {
                    DB::rollBack();

                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Erreur lors de l\'initialisation du paiement MoneyFusion: '.$e->getMessage());
                }
            }

            // Clear cart for non-MoneyFusion payments
            app(\App\Services\CartService::class)->clear();

            DB::commit();

            return redirect($order->successUrl())
                ->with('success', 'Votre commande a été passée avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur s\'est produite lors du traitement de votre commande: '.$e->getMessage());
        }
    }

    public function success(Request $request, $orderId)
    {
        $order = Order::with(['items.product.images', 'items.variant'])
            ->findOrFail($orderId);

        // Order ids are sequential, so the page is only for the owner or someone holding the signed link
        // (guests are redirected to it right after ordering).
        abort_unless($order->isViewableBy(auth()->user(), $request), 403);

        return view('checkout.success', compact('order'));
    }

    /**
     * Shown after the payer comes back from MoneyFusion while the payment is not confirmed yet.
     * It polls the payment status and moves on by itself once the gateway confirms.
     */
    public function pending(Request $request, Order $order)
    {
        abort_unless($order->isViewableBy(auth()->user(), $request), 403);

        if ($order->payment_status === 'paid') {
            return redirect($order->successUrl());
        }

        return view('checkout.pending', [
            'order' => $order,
            'statusUrl' => $order->paymentStatusUrl(),
            'failed' => in_array($order->payment_status, ['failed', 'refunded'], true) || $order->status === 'cancelled',
        ]);
    }
}
