<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement en attente</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; color: #374151; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 32px 20px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; }
        .header .company-name { font-size: 15px; margin-top: 8px; opacity: 0.9; }
        .content { padding: 32px 30px; line-height: 1.6; }
        .notice { background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 16px 20px; margin-bottom: 24px; color: #92400e; }
        .notice h2 { margin: 0 0 8px 0; font-size: 18px; }
        .details { background-color: #f9fafb; padding: 16px 20px; margin: 24px 0; }
        .details p { margin: 4px 0; }
        .button { display: inline-block; background-color: #111827; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: bold; }
        .footer { padding: 20px 30px; text-align: center; font-size: 12px; color: #6b7280; background-color: #f9fafb; }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>Glowing Cosmetics</h1>
        <div class="company-name">Votre paiement est en attente</div>
    </div>
    <div class="content">
        <p>Bonjour {{ $order->shipping_address['first_name'] ?? '' }},</p>

        <div class="notice">
            <h2>Nous n'avons pas encore reçu la confirmation de votre paiement</h2>
            <p style="margin: 0;">
                Si vous venez de payer, patientez quelques instants : la confirmation peut prendre un peu de temps.
            </p>
        </div>

        <div class="details">
            <p><strong>Commande :</strong> {{ $order->order_number }}</p>
            <p><strong>Montant :</strong> {{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
        </div>

        <p style="text-align: center; margin: 28px 0;">
            <a class="button" href="{{ $checkUrl }}">Vérifier mon paiement</a>
        </p>
        <p>
            Si vous n'avez pas finalisé le paiement, vous pouvez recommencer depuis la boutique.
            @if($expiresInHours)
                Sans confirmation de paiement, la commande sera annulée automatiquement dans environ {{ $expiresInHours }} heure(s).
            @endif
        </p>
        <p style="font-size: 13px;"><a href="{{ $shopUrl }}">Retourner à la boutique</a></p>
    </div>
    <div class="footer">
        Cet e-mail concerne la commande {{ $order->order_number }}. Pour toute question, répondez simplement à ce message.
    </div>
</div>
</body>
</html>
