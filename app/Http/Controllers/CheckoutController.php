<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Simonet85\LaravelMoneyFusion\MoneyFusionService;

class CheckoutController extends Controller
{
    protected MoneyFusionService $moneyFusion;

    public function __construct(MoneyFusionService $moneyFusion)
    {
        $this->moneyFusion = $moneyFusion;
    }
    public function index()
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        $cartItems = [];
        $subtotal = 0;

        foreach ($cart as $id => $item) {
            $product = Product::with('images')->find($item['product_id']);
            if ($product) {
                $cartItem = [
                    'id' => $id,
                    'product' => $product,
                    'variant' => isset($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity']
                ];
                $cartItems[] = $cartItem;
                $subtotal += $cartItem['subtotal'];
            }
        }

        // Get tax rate from settings (convert percentage to decimal)
        $taxRate = floatval(getSetting('tax_rate', '15')) / 100;
        $tax = $subtotal * $taxRate;

        // Get shipping settings
        $standardShippingCost = floatval(getSetting('standard_shipping_cost', '0'));
        $freeShippingThreshold = floatval(getSetting('free_shipping_threshold', '50000'));
        $shipping = $subtotal >= $freeShippingThreshold ? 0 : $standardShippingCost;

        $total = $subtotal + $tax + $shipping;

        // Get enabled payment methods from settings
        $enabledPaymentMethods = [
            'credit_card' => getSetting('payment_credit_card_enabled', '1') === '1',
            'paypal' => getSetting('payment_paypal_enabled', '1') === '1',
            'bank_transfer' => getSetting('payment_bank_transfer_enabled', '1') === '1',
            'cash_on_delivery' => getSetting('payment_cash_on_delivery_enabled', '1') === '1',
            'moneyfusion' => getSetting('payment_moneyfusion_enabled', '1') === '1',
        ];

        return view('checkout.index', compact('cartItems', 'subtotal', 'tax', 'shipping', 'total', 'enabledPaymentMethods'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'required|string|max:20',
            'country' => 'required|string|max:255',
            'payment_method' => 'required|in:credit_card,paypal,bank_transfer,cash_on_delivery,moneyfusion',
            'billing_same_as_shipping' => 'nullable',
            // Billing address fields (required only when checkbox is NOT checked)
            'billing_first_name' => 'required_without:billing_same_as_shipping|nullable|string|max:255',
            'billing_last_name' => 'required_without:billing_same_as_shipping|nullable|string|max:255',
            'billing_address' => 'required_without:billing_same_as_shipping|nullable|string|max:255',
            'billing_city' => 'required_without:billing_same_as_shipping|nullable|string|max:255',
            'billing_state' => 'required_without:billing_same_as_shipping|nullable|string|max:255',
            'billing_zip_code' => 'required_without:billing_same_as_shipping|nullable|string|max:20',
            'billing_country' => 'required_without:billing_same_as_shipping|nullable|string|max:255',
        ], [
            // Shipping address messages
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'address.required' => 'L\'adresse est obligatoire.',
            'city.required' => 'La ville est obligatoire.',
            'state.required' => 'La province/état est obligatoire.',
            'zip_code.required' => 'Le code postal est obligatoire.',
            'country.required' => 'Le pays est obligatoire.',
            'payment_method.required' => 'La méthode de paiement est obligatoire.',

            // Billing address messages
            'billing_first_name.required_without' => 'Le prénom de facturation est obligatoire.',
            'billing_last_name.required_without' => 'Le nom de facturation est obligatoire.',
            'billing_address.required_without' => 'L\'adresse de facturation est obligatoire.',
            'billing_city.required_without' => 'La ville de facturation est obligatoire.',
            'billing_state.required_without' => 'La province/état de facturation est obligatoire.',
            'billing_zip_code.required_without' => 'Le code postal de facturation est obligatoire.',
            'billing_country.required_without' => 'Le pays de facturation est obligatoire.',
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        try {
            DB::beginTransaction();

            // Calculate totals
            $subtotal = 0;
            foreach ($cart as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }

            // Get tax rate from settings (convert percentage to decimal)
            $taxRate = floatval(getSetting('tax_rate', '15')) / 100;
            $tax = $subtotal * $taxRate;

            // Get shipping settings
            $standardShippingCost = floatval(getSetting('standard_shipping_cost', '0'));
            $freeShippingThreshold = floatval(getSetting('free_shipping_threshold', '50000'));
            $shipping = $subtotal >= $freeShippingThreshold ? 0 : $standardShippingCost;

            $total = $subtotal + $tax + $shipping;

            // Prepare shipping address
            $shippingAddress = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'country' => $request->country,
                'phone' => $request->phone,
            ];

            // Prepare billing address
            $billingAddress = $request->billing_same_as_shipping
                ? $shippingAddress
                : [
                    'first_name' => $request->billing_first_name,
                    'last_name' => $request->billing_last_name,
                    'address' => $request->billing_address,
                    'city' => $request->billing_city,
                    'state' => $request->billing_state,
                    'zip_code' => $request->billing_zip_code,
                    'country' => $request->billing_country,
                ];

            // Create order
            $order = Order::create([
                'user_id' => auth()->check() ? auth()->id() : null,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shipping,
                'total' => $total,
                'customer_email' => $request->email,
                'customer_phone' => $request->phone,
                'shipping_address' => json_encode($shippingAddress),
                'billing_address' => json_encode($billingAddress),
                'notes' => $request->notes,
            ]);

            // Create order items and update stock
            foreach ($cart as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check stock availability
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuffisant pour {$product->name}");
                }

                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $item['variant_id'] ?? null,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                ]);

                // Update product stock
                $product->decrement('stock', $item['quantity']);
            }

            // Handle MoneyFusion payment
            if ($request->payment_method === 'moneyfusion') {
                try {
                    // Prepare articles for MoneyFusion
                    $articles = [];
                    foreach ($cart as $item) {
                        $product = Product::find($item['product_id']);
                        $articles[] = [
                            'name' => $product->name,
                            'price' => (int) $item['price'],
                            'quantity' => $item['quantity'],
                        ];
                    }

                    // Create MoneyFusion payment
                    $paymentResult = $this->moneyFusion->createPayment([
                        'total_price' => (float) $total,
                        'articles' => $articles,
                        'nom_client' => $request->first_name . ' ' . $request->last_name,
                        'numero_send' => $request->phone,
                        'user_id' => auth()->id(),
                        'order_id' => $order->id,
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
                        ->with('error', 'Erreur lors de l\'initialisation du paiement MoneyFusion: ' . $e->getMessage());
                }
            }

            // Clear cart for non-MoneyFusion payments
            session()->forget('cart');

            DB::commit();

            return redirect()->route('checkout.success', $order->id)
                ->with('success', 'Votre commande a été passée avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur s\'est produite lors du traitement de votre commande: ' . $e->getMessage());
        }
    }

    public function success($orderId)
    {
        $order = Order::with(['items.product.images', 'items.variant'])
            ->findOrFail($orderId);

        // Check if user owns this order (if authenticated)
        if (auth()->check() && $order->user_id !== auth()->id()) {
            abort(403);
        }

        return view('checkout.success', compact('order'));
    }
}
