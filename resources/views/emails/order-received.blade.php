<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande enregistrée</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#13201a;">
@php
    $shipping = $order->shipping_address ?? [];
    $cash = $order->payment_method === 'cash_on_delivery';
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;">
    <tr><td align="center" style="padding:24px 12px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;">
            <tr><td style="background:#0b7a4b;color:#ffffff;padding:24px 28px;">
                <div style="font-size:14px;letter-spacing:1px;text-transform:uppercase;">{{ config('app.name') }}</div>
                <h1 style="margin:8px 0 0;font-size:26px;line-height:1.2;">Commande enregistrée</h1>
            </td></tr>

            <tr><td style="padding:28px;">
                <p style="margin:0 0 12px;font-size:16px;">Bonjour {{ $shipping['first_name'] ?? '' }},</p>
                <p style="margin:0 0 12px;font-size:16px;line-height:1.5;">
                    Nous avons bien enregistré votre commande <strong>{{ $order->order_number }}</strong> du {{ $order->created_at->translatedFormat('j F Y à H:i') }}.
                    @if ($cash)
                        Vous la réglez <strong>{{ format_fcfa($order->total) }}</strong> en espèces à la livraison : préparez le montant exact.
                    @else
                        Elle sera préparée dès réception de votre paiement.
                    @endif
                </p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:2px solid #13201a;margin-top:20px;">
                    @foreach ($order->items as $item)
                        <tr>
                            <td style="padding:12px 0;border-bottom:1px solid #d5ded8;font-size:15px;">
                                <strong>{{ $item->product_name }}</strong>
                                @if ($item->variant)<br><span style="color:#4a5750;">{{ $item->variant->label }}</span>@endif
                                <br><span style="color:#4a5750;">{{ $item->quantity }} × {{ format_fcfa($item->price) }}</span>
                            </td>
                            <td align="right" style="padding:12px 0;border-bottom:1px solid #d5ded8;font-size:15px;white-space:nowrap;">{{ format_fcfa($item->total) }}</td>
                        </tr>
                    @endforeach
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;font-size:15px;">
                    <tr><td style="padding:4px 0;color:#4a5750;">Sous-total</td><td align="right">{{ format_fcfa($order->subtotal) }}</td></tr>
                    <tr><td style="padding:4px 0;color:#4a5750;">Taxes</td><td align="right">{{ format_fcfa($order->tax) }}</td></tr>
                    <tr><td style="padding:4px 0;color:#4a5750;">Livraison</td><td align="right">{{ $order->shipping_cost > 0 ? format_fcfa($order->shipping_cost) : 'Gratuite' }}</td></tr>
                    <tr><td style="padding:10px 0 0;font-size:18px;font-weight:bold;border-top:1px solid #13201a;">Total</td><td align="right" style="padding:10px 0 0;font-size:18px;font-weight:bold;border-top:1px solid #13201a;">{{ format_fcfa($order->total) }}</td></tr>
                </table>

                <p style="margin:24px 0 4px;font-size:15px;"><strong>Livraison</strong></p>
                <p style="margin:0;font-size:15px;line-height:1.5;">
                    {{ trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '')) }}<br>
                    {{ $shipping['quartier'] ?? '' }}@if (! empty($shipping['city'])), {{ $shipping['city'] }}@endif<br>
                    {{ $shipping['phone'] ?? $order->customer_phone }}
                </p>

                <p style="margin:28px 0 0;">
                    <a href="{{ $orderUrl }}" style="display:inline-block;background:#0b7a4b;color:#ffffff;text-decoration:none;padding:14px 22px;font-weight:bold;">Voir ma commande</a>
                </p>
                <p style="margin:24px 0 0;font-size:14px;color:#4a5750;line-height:1.5;">Une question ? Répondez à ce message ou écrivez-nous depuis le site en indiquant votre numéro de commande.</p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
