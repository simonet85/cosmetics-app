<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $replySubject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;color:#1f2937;">
    <div style="max-width:600px;margin:0 auto;background-color:#ffffff;">
        <div style="background-color:#0f5132;padding:32px 24px;text-align:center;color:#ffffff;">
            <p style="margin:0;font-size:24px;font-weight:700;letter-spacing:2px;">{{ config('app.name') }}</p>
            <p style="margin:8px 0 0;font-size:15px;opacity:.9;">Réponse à votre message</p>
        </div>

        <div style="padding:32px 24px;">
            <p style="margin:0 0 16px;">Bonjour {{ $contactMessage->name }},</p>

            <div style="margin:0 0 24px;padding:16px;border-left:4px solid #0f5132;background-color:#f0fdf4;white-space:pre-line;">{{ $replyText }}</div>

            <p style="margin:0 0 8px;font-size:14px;color:#4b5563;">Votre message du {{ $contactMessage->created_at->format('d/m/Y à H:i') }} :</p>
            <blockquote style="margin:0 0 24px;padding:12px 16px;border-left:3px solid #d1d5db;color:#4b5563;white-space:pre-line;">{{ $contactMessage->message }}</blockquote>

            <p style="margin:0;">Cordialement,<br>{{ config('app.name') }}</p>
        </div>

        <div style="padding:16px 24px;background-color:#f9fafb;color:#6b7280;font-size:12px;text-align:center;">
            Cet e-mail répond à votre demande envoyée depuis notre formulaire de contact.
        </div>
    </div>
</body>
</html>
