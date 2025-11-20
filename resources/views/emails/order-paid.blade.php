<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header .company-name {
            font-size: 16px;
            margin-top: 10px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .success-message {
            background-color: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin-bottom: 30px;
        }
        .success-message h2 {
            margin: 0 0 10px 0;
            color: #065f46;
            font-size: 22px;
        }
        .success-message p {
            margin: 0;
            color: #047857;
            line-height: 1.6;
        }
        .order-summary {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .order-summary h3 {
            margin: 0 0 15px 0;
            color: #374151;
            font-size: 18px;
        }
        .order-info {
            margin-bottom: 20px;
        }
        .order-info table {
            width: 100%;
        }
        .order-info td {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .order-info .label {
            font-weight: 600;
            color: #6b7280;
            width: 150px;
        }
        .order-info .value {
            color: #111827;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .items-table th {
            background-color: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .totals {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
        }
        .totals table {
            width: 100%;
            margin-left: auto;
            max-width: 300px;
        }
        .totals td {
            padding: 8px 0;
        }
        .totals .label {
            text-align: left;
            color: #6b7280;
        }
        .totals .amount {
            text-align: right;
            color: #111827;
            font-weight: 500;
        }
        .totals .grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            border-top: 2px solid #667eea;
            padding-top: 10px;
        }
        .attachment-notice {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        .attachment-notice p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }
        .addresses {
            display: table;
            width: 100%;
            margin: 20px 0;
        }
        .address-column {
            display: table-cell;
            width: 50%;
            padding-right: 15px;
            vertical-align: top;
        }
        .address-block {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .address-block h4 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 14px;
            text-transform: uppercase;
        }
        .address-block p {
            margin: 5px 0;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.5;
        }
        .next-steps {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 30px 0;
        }
        .next-steps h3 {
            margin: 0 0 15px 0;
            color: #1e40af;
            font-size: 18px;
        }
        .next-steps ul {
            margin: 0;
            padding-left: 20px;
            color: #1e40af;
        }
        .next-steps li {
            margin: 8px 0;
            line-height: 1.6;
        }
        .cta-button {
            text-align: center;
            margin: 30px 0;
        }
        .cta-button a {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
            color: #6b7280;
            font-size: 14px;
        }
        .footer .social-links {
            margin-top: 15px;
        }
        .footer .social-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>GLOWING COSMETICS</h1>
            <div class="company-name">Klab Consulting</div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Success Message -->
            <div class="success-message">
                <h2>Merci de votre commande !</h2>
                <p>
                    Nous avons reçu votre paiement et votre commande est confirmée.
                    Vous recevrez un autre e-mail lorsque votre commande sera expédiée.
                </p>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3>RÉSUMÉ DE COMMANDE</h3>

                <div class="order-info">
                    <table>
                        <tr>
                            <td class="label">Numéro de commande :</td>
                            <td class="value"><strong>#{{ $order->order_number }}</strong></td>
                        </tr>
                        <tr>
                            <td class="label">Date de commande :</td>
                            <td class="value">{{ $order->created_at->format('F d, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Statut du paiement :</td>
                            <td class="value" style="color: #10b981; font-weight: bold; text-transform: uppercase;">
                                {{ $order->payment_status }}
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Montant total :</td>
                            <td class="value" style="font-size: 18px; font-weight: bold; color: #667eea;">
                                {{ number_format($order->total, 2) }} FCFA
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Items -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-right">Prix</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->product_name }}</strong>
                                    @if($item->variant)
                                        <br>
                                        <small style="color: #6b7280;">
                                            @php
                                                $attributes = json_decode($item->variant->attributes, true);
                                            @endphp
                                            @if($attributes)
                                                @foreach($attributes as $key => $value)
                                                    {{ ucfirst($key) }}: {{ $value }}{{ !$loop->last ? ', ' : '' }}
                                                @endforeach
                                            @endif
                                        </small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right">{{ number_format($item->price, 2) }} FCFA</td>
                                <td class="text-right">{{ number_format($item->total, 2) }} FCFA</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="totals">
                    <table>
                        <tr>
                            <td class="label">Sous-total :</td>
                            <td class="amount">{{ number_format($order->subtotal, 2) }} FCFA</td>
                        </tr>
                        @if($order->shipping > 0)
                            <tr>
                                <td class="label">Frais de port :</td>
                                <td class="amount">{{ number_format($order->shipping, 2) }} FCFA</td>
                            </tr>
                        @endif
                        @if($order->tax > 0)
                            <tr>
                                <td class="label">Taxe :</td>
                                <td class="amount">{{ number_format($order->tax, 2) }} FCFA</td>
                            </tr>
                        @endif
                        @if($order->discount > 0)
                            <tr>
                                <td class="label">Discount:</td>
                                <td class="amount">-{{ number_format($order->discount, 2) }} FCFA</td>
                            </tr>
                        @endif
                        <tr class="grand-total">
                            <td class="label">Total:</td>
                            <td class="amount">{{ number_format($order->total, 2) }} FCFA</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Attachment Notice -->
            <div class="attachment-notice">
                <p>
                    <strong>📎 Facture jointe</strong><br>
                    Votre facture détaillée est jointe à cet e-mail sous forme de fichier PDF.
                    Veuillez la conserver pour vos dossiers.
                </p>
            </div>

            <!-- Addresses -->
            <div class="addresses">
                <div class="address-column">
                    <div class="address-block">
                        <h4>Adresse de facturation</h4>
                        @php
                            $billing = json_decode($order->billing_address, true);
                        @endphp
                        @if($billing)
                            <p><strong>{{ $billing['first_name'] ?? '' }} {{ $billing['last_name'] ?? '' }}</strong></p>
                            <p>Ville : {{ $billing['city'] ?? '' }}</p>
                            <p>Quartier : {{ $billing['quartier'] ?? '' }}</p>
                        @endif
                    </div>
                </div>
                <div class="address-column">
                    <div class="address-block">
                        <h4>Adresse de livraison</h4>
                        @php
                            $shipping = json_decode($order->shipping_address, true);
                        @endphp
                        @if($shipping)
                            <p><strong>{{ $shipping['first_name'] ?? '' }} {{ $shipping['last_name'] ?? '' }}</strong></p>
                            <p>Ville : {{ $shipping['city'] ?? '' }}</p>
                            <p>Quartier : {{ $shipping['quartier'] ?? '' }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="next-steps">
                <h3>Que faire maintenant?</h3>
                <ul>
                    <li>Nous traitons votre commande et l'expédierons bientôt</li>
                    <li>Vous recevrez un e-mail de confirmation d'expédition avec les informations de suivi</li>
                    <li>Livraison estimée : 1-3 jours ouvrables</li>
                    <li>Suivez le statut de votre commande à tout moment depuis votre compte</li>
                </ul>
            </div>

            <!-- CTA Button -->
            <div class="cta-button">
                <a href="{{ config('app.url') }}/orders/{{ $order->id }}">Voir les détails de la commande</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>GLOWING COSMETICS</strong></p>
            <p>Email: info@klab-consulting.com</p>
            <p>Site web: www.klab-consulting.com</p>
            <p style="margin-top: 15px; font-size: 12px; color: #9ca3af;">
                Si vous avez des questions concernant votre commande, n'hésitez pas à nous contacter.
            </p>
            <p style="font-size: 12px; color: #9ca3af;">
                Cet e-mail a été envoyé à {{ $order->customer_email }}
            </p>
        </div>
    </div>
</body>
</html>
