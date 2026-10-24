<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Mail\OrderReceived;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Support\CheckoutToken;
use App\Support\PaymentMethods;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Simonet85\LaravelMoneyFusion\MoneyFusionService;
use Throwable;

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
        $checkoutToken = CheckoutToken::make();

        return view('storefront.checkout', compact('cartItems', 'subtotal', 'tax', 'shipping', 'total', 'enabledPaymentMethods', 'checkoutToken'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'checkout_token' => 'required|string',
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
            'notes' => 'nullable|string|max:1000',
        ], [
            'checkout_token.required' => 'Cette page a expiré. Rechargez la page de paiement.',
            // Shipping address messages
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'city.required' => 'La ville est obligatoire.',
            'quartier.required' => 'Le quartier est obligatoire.',
            'payment_method.required' => 'Choisissez un mode de paiement.',

            // Billing address messages
            'billing_first_name.required_without' => 'Le prénom de facturation est obligatoire.',
            'billing_last_name.required_without' => 'Le nom de facturation est obligatoire.',
            'billing_city.required_without' => 'La ville de facturation est obligatoire.',
            'billing_quartier.required_without' => 'Le quartier de facturation est obligatoire.',
        ]);

        $token = $request->input('checkout_token');

        if (! CheckoutToken::valid($token)) {
            return redirect()->route('checkout.index')
                ->with('error', 'Cette page de paiement a expiré. Vérifiez votre commande et validez-la à nouveau.');
        }

        // The same form sent twice (double click, reload, second tab) leads to the order it already created
        $key = 'checkout:'.hash('sha256', $token);
        if ($existing = $this->existingOrder($key)) {
            return $this->toOrder($existing);
        }

        // Two requests carrying the same token at the same moment: only one may place the order
        $lock = Cache::lock($key.':lock', 60);
        if (! $lock->get()) {
            return redirect()->back()->withInput()
                ->with('error', 'Votre commande est en cours de traitement. Patientez quelques secondes, ne la validez pas une seconde fois.');
        }

        try {
            if ($existing = $this->existingOrder($key)) {
                return $this->toOrder($existing);
            }

            $response = $this->place($request, $key);
        } finally {
            $lock->release();
        }

        return $response;
    }

    /**
     * Cash orders are not paid yet, so nothing else tells the customer the order exists: send the receipt.
     * The order is already saved; a mail problem is logged and never turns into a failed checkout.
     */
    private function sendReceived(Order $order): void
    {
        if (getSetting('send_order_confirmation', '1') !== '1' || ! $order->customer_email) {
            return;
        }

        try {
            Mail::to($order->customer_email)->send(new OrderReceived($order->loadMissing('items.variant')));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function existingOrder(string $key): ?Order
    {
        $id = Cache::get($key.':order');

        return $id ? Order::find($id) : null;
    }

    private function toOrder(Order $order): RedirectResponse
    {
        if ($order->moneyfusion_payment_url && $order->payment_status === 'pending') {
            return redirect($order->moneyfusion_payment_url);
        }

        return redirect($order->successUrl())->with('success', 'Cette commande a déjà été enregistrée.');
    }

    private function place(Request $request, string $key): RedirectResponse
    {
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
                    throw new CheckoutException("Il ne reste pas assez de stock pour {$product->name}. Modifiez la quantité dans votre panier.");
                }

                $variant = null;
                if (! empty($item['variant_id'])) {
                    $variant = ProductVariant::whereKey($item['variant_id'])
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $variant || ! $variant->is_active) {
                        throw new CheckoutException("L'option choisie pour {$product->name} n'est plus disponible. Retirez-la de votre panier.");
                    }

                    if ($variant->stock < $item['quantity']) {
                        throw new CheckoutException("Il ne reste pas assez de stock pour {$product->name} ({$variant->label}). Modifiez la quantité dans votre panier.");
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
                    Cache::put($key.':order', $order->id, now()->addDay());

                    // Redirect to MoneyFusion payment page
                    return redirect($paymentResult['url']);
                } catch (Throwable $e) {
                    DB::rollBack();
                    report($e);

                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Le paiement mobile n\'a pas pu être lancé. Votre panier est conservé : réessayez ou choisissez un autre mode de paiement.');
                }
            }

            // Clear cart for non-MoneyFusion payments
            $this->cart->clear();

            DB::commit();
            Cache::put($key.':order', $order->id, now()->addDay());

            $this->sendReceived($order);

            return redirect($order->successUrl())
                ->with('success', 'Votre commande a été passée avec succès!');
        } catch (CheckoutException $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Votre commande n\'a pas pu être enregistrée. Votre panier est conservé : réessayez dans un instant.');
        }
    }

    public function success(Request $request, $orderId)
    {
        $order = Order::with(['items.product.images', 'items.variant'])
            ->findOrFail($orderId);

        // Order ids are sequential, so the page is only for the owner or someone holding the signed link
        // (guests are redirected to it right after ordering).
        abort_unless($order->isViewableBy(auth()->user(), $request), 403);

        return view('storefront.order', compact('order'));
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

        return view('storefront.payment-pending', [
            'order' => $order,
            'statusUrl' => $order->paymentStatusUrl(),
            'failed' => in_array($order->payment_status, ['failed', 'refunded'], true) || $order->status === 'cancelled',
        ]);
    }
}
