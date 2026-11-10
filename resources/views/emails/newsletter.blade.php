<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campaign->subject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;color:#1f2937;">
    <div style="max-width:600px;margin:0 auto;background-color:#ffffff;">
        <div style="background-color:#0f5132;padding:32px 24px;text-align:center;color:#ffffff;">
            <p style="margin:0;font-size:24px;font-weight:700;letter-spacing:2px;">{{ config('app.name') }}</p>
            <p style="margin:8px 0 0;font-size:15px;opacity:.9;">Newsletter</p>
        </div>

        <div style="padding:32px 24px;">
            <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;">{{ $campaign->subject }}</h1>
            <div style="margin:0;font-size:16px;line-height:1.6;">{!! nl2br(e($campaign->body)) !!}</div>
        </div>

        <div style="padding:16px 24px;background-color:#f9fafb;color:#6b7280;font-size:12px;text-align:center;">
            <p style="margin:0 0 8px;">Vous recevez cet e-mail car votre adresse est inscrite à la newsletter de {{ config('app.name') }}.</p>
            <p style="margin:0;">
                <a href="{{ route('newsletter.unsubscribe', $subscriber->token) }}" style="color:#0f5132;text-decoration:underline;">Se désinscrire</a>
            </p>
        </div>
    </div>
</body>
</html>
