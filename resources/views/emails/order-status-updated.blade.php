<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
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
        .status-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            margin: 20px 0;
        }
        .status-badge.processing {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-badge.shipped {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-badge.delivered {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-badge.cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-message {
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .status-message.processing {
            background-color: #eff6ff;
            border-color: #3b82f6;
        }
        .status-message.shipped {
            background-color: #fffbeb;
            border-color: #f59e0b;
        }
        .status-message.delivered {
            background-color: #ecfdf5;
            border-color: #10b981;
        }
        .status-message.cancelled {
            background-color: #fef2f2;
            border-color: #ef4444;
        }
        .status-message h2 {
            margin: 0 0 10px 0;
            font-size: 22px;
        }
        .status-message.processing h2 { color: #1e40af; }
        .status-message.shipped h2 { color: #92400e; }
        .status-message.delivered h2 { color: #065f46; }
        .status-message.cancelled h2 { color: #991b1b; }
        .status-message p {
            margin: 0;
            line-height: 1.6;
        }
        .status-message.processing p { color: #1e3a8a; }
        .status-message.shipped p { color: #78350f; }
        .status-message.delivered p { color: #047857; }
        .status-message.cancelled p { color: #7f1d1d; }
        .timeline {
            margin: 30px 0;
            padding: 20px;
            background-color: #f9fafb;
            border-radius: 8px;
        }
        .timeline h3 {
            margin: 0 0 20px 0;
            color: #374151;
            font-size: 18px;
        }
        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            position: relative;
            padding-left: 30px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 6px;
            top: 25px;
            width: 2px;
            height: calc(100% + 10px);
            background-color: #e5e7eb;
        }
        .timeline-item:last-child::before {
            display: none;
        }
        .timeline-icon {
            position: absolute;
            left: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 3px solid;
            background-color: white;
            margin-top: 5px;
        }
        .timeline-icon.active {
            border-color: #667eea;
            background-color: #667eea;
        }
        .timeline-icon.completed {
            border-color: #10b981;
            background-color: #10b981;
        }
        .timeline-icon.pending {
            border-color: #d1d5db;
            background-color: white;
        }
        .timeline-content h4 {
            margin: 0 0 5px 0;
            color: #111827;
            font-size: 16px;
        }
        .timeline-content p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        .order-info {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        .order-info h3 {
            margin: 0 0 15px 0;
            color: #374151;
            font-size: 18px;
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
        .tracking-info {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .tracking-info h4 {
            margin: 0 0 10px 0;
            color: #92400e;
            font-size: 16px;
        }
        .tracking-info p {
            margin: 5px 0;
            color: #78350f;
        }
        .tracking-info .tracking-number {
            font-size: 18px;
            font-weight: bold;
            color: #f59e0b;
            background-color: white;
            padding: 10px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 10px;
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
        .items-summary {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 6px;
        }
        .items-summary h4 {
            margin: 0 0 10px 0;
            color: #374151;
            font-size: 16px;
        }
        .items-summary ul {
            margin: 0;
            padding-left: 20px;
            color: #4b5563;
        }
        .items-summary li {
            margin: 5px 0;
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
            <!-- Status Badge -->
            <div style="text-align: center;">
                <span class="status-badge {{ $order->status }}">{{ strtoupper($order->status) }}</span>
            </div>

            <!-- Status-specific Message -->
            @if($order->status === 'processing')
                <div class="status-message processing">
                    <h2>Votre Commande est en cours de traitement !</h2>
                    <p>
                        Bonne nouvelle ! Nous avons commencé à préparer votre commande. Notre équipe emballe soigneusement vos articles et ils seront expédiés bientôt.
                    </p>
                </div>
            @elseif($order->status === 'shipped')
                <div class="status-message shipped">
                    <h2>Votre Commande a été Expédiée !</h2>
                    <p>
                        Excellent! Votre commande est en route vers vous. Vous pouvez suivre votre colis
                        en utilisant les informations de suivi ci-dessous.
                    </p>
                </div>

                @if($order->tracking_number)
                    <div class="tracking-info">
                        <h4>📦 Informations de suivi</h4>
                        <p>Transporteur : {{ $order->shipping_carrier ?? 'Livraison standard' }}</p>
                        <p>Numéro de suivi :</p>
                        <div class="tracking-number">{{ $order->tracking_number }}</div>
                        @if($order->tracking_url)
                            <p style="margin-top: 15px;">
                                <a href="{{ $order->tracking_url }}" style="color: #f59e0b; font-weight: bold;">
                                    Suivre le colis →
                                </a>
                            </p>
                        @endif
                    </div>
                @endif
            @elseif($order->status === 'delivered')
                <div class="status-message delivered">
                    <h2>Votre Commande a été Livrée !</h2>
                    <p>
                        Merveilleux ! Votre commande a été livrée avec succès. Nous espérons que vous aimez
                        vos nouveaux produits de Glowing Cosmetics !
                    </p>
                    <p style="margin-top: 15px;">
                        Si vous avez des questions ou des préoccupations concernant votre commande, n'hésitez pas à nous contacter.
                    </p>
                </div>
            @elseif($order->status === 'cancelled')
                <div class="status-message cancelled">
                    <h2>Votre Commande a été Annulée</h2>
                    <p>
                        Votre commande a été annulée comme demandé. Si vous n'avez pas demandé cette
                        annulation ou si vous avez des questions, veuillez contacter immédiatement notre
                        service client.
                    </p>
                    <p style="margin-top: 15px;">
                        Si un paiement a été effectué, un remboursement sera émis sur votre méthode de
                        paiement d'origine dans un délai de 5 à 7 jours ouvrables.
                    </p>
                </div>
            @endif

            <!-- Order Timeline -->
            <div class="timeline">
                <h3>Chronologie de la Commande</h3>

                <div class="timeline-item">
                    <div class="timeline-icon completed"></div>
                    <div class="timeline-content">
                        <h4>Commande Passée</h4>
                        <p>{{ $order->created_at->format('F d, Y H:i') }}</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon {{ in_array($order->status, ['processing', 'shipped', 'delivered']) ? 'completed' : ($order->status === 'cancelled' ? 'pending' : 'active') }}"></div>
                    <div class="timeline-content">
                        <h4>Traitement</h4>
                        <p>{{ in_array($order->status, ['processing', 'shipped', 'delivered']) ? 'Terminé' : ($order->status === 'cancelled' ? 'Annulé' : 'En attente') }}</p>
                    </div>
                </div>

                @if($order->status !== 'cancelled')
                    <div class="timeline-item">
                        <div class="timeline-icon {{ in_array($order->status, ['shipped', 'delivered']) ? 'completed' : ($order->status === 'processing' ? 'active' : 'pending') }}"></div>
                        <div class="timeline-content">
                            <h4>Expédié</h4>
                            <p>{{ in_array($order->status, ['shipped', 'delivered']) ? 'En transit' : 'Estimation : 1-2 jours ouvrables' }}</p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-icon {{ $order->status === 'delivered' ? 'completed' : ($order->status === 'shipped' ? 'active' : 'pending') }}"></div>
                        <div class="timeline-content">
                            <h4>Livraison</h4>
                            <p>{{ $order->status === 'delivered' ? $order->updated_at->format('F d, Y H:i') : 'Estimation : 3-5 jours ouvrables' }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Order Information -->
            <div class="order-info">
                <h3>Détails de la Commande</h3>
                <table>
                    <tr>
                        <td class="label">Numéro de Commande :</td>
                        <td class="value"><strong>#{{ $order->order_number }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Date de Commande :</td>
                        <td class="value">{{ $order->created_at->format('F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Total de la Commande :</td>
                        <td class="value"><strong>${{ number_format($order->total, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Statut :</td>
                        <td class="value" style="text-transform: uppercase; font-weight: bold;">
                            {{ $order->status }}
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Items Summary -->
            <div class="items-summary">
                <h4>Articles de la Commande ({{ $order->items->count() }} {{ $order->items->count() === 1 ? 'article' : 'articles' }})</h4>
                <ul>
                    @foreach($order->items as $item)
                        <li>
                            {{ $item->quantity }}x {{ $item->product_name }}
                            @if($item->variant)
                                @php
                                    $attributes = json_decode($item->variant->attributes, true);
                                @endphp
                                @if($attributes)
                                    (
                                    @foreach($attributes as $key => $value)
                                        {{ ucfirst($key) }}: {{ $value }}{{ !$loop->last ? ', ' : '' }}
                                    @endforeach
                                    )
                                @endif
                            @endif
                            - {{ number_format($item->total, 2) }} FCFA
                        </li>
                    @endforeach
                </ul>
            </div>
            <!-- CTA Button -->
            @if($order->status !== 'cancelled')
                <div class="cta-button">
                    <a href="{{ config('app.url') }}/orders/{{ $order->id }}">Voir les détails complets de la commande</a>
                </div>
            @endif

            <!-- Additional Information -->
            @if($order->status === 'delivered')
                <div style="background-color: #eff6ff; padding: 20px; border-radius: 8px; margin-top: 20px;">
                    <h4 style="margin: 0 0 10px 0; color: #1e40af;">Nous Aimons Vos Retours!</h4>
                    <p style="margin: 0; color: #1e3a8a; line-height: 1.6;">
                        Comment s'est passée votre expérience avec Glowing Cosmetics ? Vos retours nous aident
                        à améliorer nos produits et services. Veuillez prendre un moment pour laisser un avis.
                    </p>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>GLOWING COSMETICS </strong></p>
            <p>Email: info@klab-consulting.com</p>
            <p>Website: www.klab-consulting.com</p>
            <p style="margin-top: 15px; font-size: 12px; color: #9ca3af;">
                Si vous avez des questions concernant votre commande, veuillez nous contacter à info@klab-consulting.com
            </p>
            <p style="font-size: 12px; color: #9ca3af;">
                Cet e-mail a été envoyé à {{ $order->customer_email }}
            </p>
        </div>
    </div>
</body>
</html>
