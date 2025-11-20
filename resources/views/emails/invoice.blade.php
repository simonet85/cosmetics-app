<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .invoice-container {
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        .header .company-name {
            font-size: 18px;
            font-weight: normal;
        }
        .invoice-info {
            margin-bottom: 30px;
        }
        .invoice-info table {
            width: 100%;
        }
        .invoice-info td {
            padding: 5px 0;
        }
        .invoice-info .label {
            font-weight: bold;
            width: 120px;
        }
        .addresses {
            margin-bottom: 30px;
        }
        .addresses table {
            width: 100%;
        }
        .addresses td {
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .address-block {
            margin-bottom: 15px;
        }
        .address-block h3 {
            font-size: 14px;
            color: #667eea;
            margin-bottom: 8px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table thead {
            background-color: #667eea;
            color: white;
        }
        .items-table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .items-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .totals {
            margin-left: auto;
            width: 300px;
            margin-bottom: 30px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .totals .label {
            text-align: left;
            font-weight: 500;
        }
        .totals .amount {
            text-align: right;
            font-weight: 500;
        }
        .totals .grand-total {
            font-size: 16px;
            font-weight: bold;
            color: #667eea;
            border-top: 2px solid #667eea;
            padding-top: 10px;
        }
        .payment-info {
            background-color: #f8f9ff;
            padding: 15px;
            border-left: 4px solid #667eea;
            margin-bottom: 30px;
        }
        .payment-info h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .payment-info table {
            width: 100%;
        }
        .payment-info td {
            padding: 5px 0;
        }
        .payment-info .label {
            font-weight: bold;
            width: 150px;
        }
        .signature-section {
            margin-top: 50px;
            margin-bottom: 30px;
        }
        .signature-section table {
            width: 100%;
        }
        .signature-section td {
            width: 50%;
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid #333;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <h1>RECU</h1>
            <div class="company-name">GLOWING COSMETICS</div>
            <!-- <div class="company-name">Klab Consulting</div> -->
        </div>

        <!-- Invoice Information -->
        <div class="invoice-info">
            <table>
                <tr>
                    <td class="label">Numéro de reçu :</td>
                    <td>{{ $order->order_number }}</td>
                    <td class="label">Date du reçu :</td>
                    <td>{{ $order->created_at->format('F d, Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Date de la commande :</td>
                    <td>{{ $order->created_at->format('F d, Y H:i') }}</td>
                    <td class="label">Statut du paiement :</td>
                    <td style="text-transform: uppercase; color: {{ $order->payment_status === 'paid' ? '#10b981' : '#ef4444' }}; font-weight: bold;">
                        {{ $order->payment_status }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Addresses -->
        <div class="addresses">
            <table>
                <tr>
                    <td>
                        <div class="address-block">
                            <h3>ADRESSE DE FACTURATION </h3>
                            @php
                                $billing = json_decode($order->billing_address, true);
                            @endphp
                            @if($billing)
                                <p><strong>{{ $billing['first_name'] ?? '' }} {{ $billing['last_name'] ?? '' }}</strong></p>
                                <p>Ville : {{ $billing['city'] ?? '' }}</p>
                                <p>Quartier : {{ $billing['quartier'] ?? '' }}</p>
                                @if(!empty($billing['phone']))
                                    <p>Téléphone : {{ $billing['phone'] }}</p>
                                @endif
                                <p>Email: {{ $order->customer_email }}</p>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="address-block">
                            <h3>ADRESSE DE LIVRAISON</h3>
                            @php
                                $shipping = json_decode($order->shipping_address, true);
                            @endphp
                            @if($shipping)
                                <p><strong>{{ $shipping['first_name'] ?? '' }} {{ $shipping['last_name'] ?? '' }}</strong></p>
                                <p>Ville : {{ $shipping['city'] ?? '' }}</p>
                                <p>Quartier : {{ $shipping['quartier'] ?? '' }}</p>
                                @if(!empty($shipping['phone']))
                                    <p>Téléphone : {{ $shipping['phone'] }}</p>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Article</th>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 15%;" class="text-center">Quantité</th>
                    <th style="width: 17.5%;" class="text-right">Prix Unitaire</th>
                    <th style="width: 17.5%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item->product_name }}</strong>
                            @if($item->sku)
                                <br><small style="color: #666;">SKU: {{ $item->sku }}</small>
                            @endif
                            @if($item->variant)
                                <br><small style="color: #666;">
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
                        <td class="text-right">${{ number_format($item->price, 2) }}</td>
                        <td class="text-right">${{ number_format($item->total, 2) }}</td>
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
                        <td class="label">Frais de transport :</td>
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
                        <td class="label">Rabais :</td>
                        <td class="amount">-{{ number_format($order->discount, 2) }} FCFA</td>
                    </tr>
                @endif
                <tr class="grand-total">
                    <td class="label">TOTAL  :</td>
                    <td class="amount">{{ number_format($order->total, 2) }} FCFA</td>
                </tr>
            </table>
        </div>

        <!-- Payment Information -->
        <div class="payment-info">
            <h3> Informations de paiement</h3>
            <table>
                <tr>
                    <td class="label">Méthode de paiement :</td>
                    <td>MoneyFusion</td>
                </tr>
                <tr>
                    <td class="label">Statut du paiement :</td>
                    <td style="text-transform: uppercase; font-weight: bold;">{{ $order->payment_status }}</td>
                </tr>
                @if($order->moneyFusionPayment)
                    <tr>
                        <td class="label">ID de transaction :</td>
                        <td>{{ $order->moneyFusionPayment->transaction_id ?? 'N/A' }}</td>
                    </tr>
                    @if($order->moneyFusionPayment->paid_at)
                        <tr>
                            <td class="label">Date de paiement :</td>
                            <td>{{ \Carbon\Carbon::parse($order->moneyFusionPayment->paid_at)->format('F d, Y H:i') }}</td>
                        </tr>
                    @endif
                @endif
            </table>
        </div>

        <!-- Signature Section -->
        <!-- <div class="signature-section">
            <table>
                <tr>
                    <td>
                        <strong>Signature du client</strong>
                    </td>
                    <td>
                        <strong>Signature autorisée</strong>
                    </td>
                </tr>
            </table>
        </div> -->

        <!-- Footer -->
        <div class="footer">
            <p><strong>GLOWING COSMETICS</strong></p>
            <p>Email: info@klab-consulting.com | Website: www.klab-consulting.com</p>
            <p style="margin-top: 10px; font-style: italic;">
                Merci pour votre confiance ! Ceci est une facture générée par ordinateur et ne nécessite pas de signature physique.
            </p>
            <!-- <p style="margin-top: 10px;">
                <strong>Termes et conditions :</strong> Le paiement est dû dans les 30 jours. Les paiements en retard peuvent entraîner des frais supplémentaires.
                Veuillez conserver cette facture pour vos dossiers.
            </p> -->
        </div>
    </div>
</body>
</html>
