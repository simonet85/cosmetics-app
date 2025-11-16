# MoneyFusion Webhook Setup Guide for Laragon

## Overview

This guide explains how to expose your Laragon local development environment to the internet so MoneyFusion can send webhook notifications to your application.

## Current Configuration

Your webhook URLs are configured in `.env`:
```env
MONEYFUSION_WEBHOOK_URL=https://8e3bc06c2e3a.ngrok-free.app/api/moneyfusion/webhook
MONEYFUSION_RETURN_URL=https://8e3bc06c2e3a.ngrok-free.app/payment/callback
```

## Available Webhook Endpoints

✅ **Webhook Endpoint** (for MoneyFusion to send notifications):
- POST `/api/moneyfusion/webhook`
- No CSRF protection required
- Logs all incoming webhooks
- Automatically updates payment status in database

✅ **Payment Callback** (where users return after payment):
- GET `/payment/callback`
- Shows payment status page to user

## Setup Instructions

### Option 1: Using ngrok (Recommended)

#### Step 1: Install ngrok

**Method A: Download Directly**
1. Go to https://ngrok.com/download
2. Download ngrok for Windows
3. Extract to a folder (e.g., `C:\tools\ngrok`)
4. Add to PATH environment variable

**Method B: Using Chocolatey**
```bash
choco install ngrok
```

#### Step 2: Sign Up (Optional but Recommended)
1. Create free account at https://dashboard.ngrok.com/signup
2. Get your auth token from https://dashboard.ngrok.com/get-started/your-authtoken
3. Configure ngrok:
```bash
ngrok config add-authtoken YOUR_AUTH_TOKEN_HERE
```

#### Step 3: Start ngrok Tunnel
```bash
# Point to your Laragon Apache/Nginx port (usually 80)
ngrok http 80 --host-header=cosmetics-app.test
```

**Note**: The `--host-header` flag is important for Laragon virtual hosts!

#### Step 4: Copy the HTTPS URL
ngrok will display output like:
```
Forwarding  https://abc123.ngrok-free.app -> http://localhost:80
```

Copy the HTTPS URL (e.g., `https://abc123.ngrok-free.app`)

#### Step 5: Update .env
Update these lines in your `.env` file:
```env
APP_URL=https://abc123.ngrok-free.app
MONEYFUSION_WEBHOOK_URL=https://abc123.ngrok-free.app/api/moneyfusion/webhook
MONEYFUSION_RETURN_URL=https://abc123.ngrok-free.app/payment/callback
```

#### Step 6: Clear Config Cache
```bash
php artisan config:clear
```

#### Step 7: Test Your Webhook
Visit the ngrok dashboard at http://localhost:4040 to monitor incoming requests.

### Option 2: Using Laragon's Built-in Share Feature

1. Right-click on Laragon tray icon
2. Select **"Share"** → **"ngrok"**
3. Follow the prompts
4. Copy the provided URL
5. Update `.env` as shown in Step 5 above

### Option 3: Using Expose (Alternative)

```bash
# Install
composer global require beyondcode/expose

# Start tunnel
expose share http://cosmetics-app.test --host-header=cosmetics-app.test
```

## Testing Your Webhook

### Test 1: Check Route Exists
```bash
php artisan route:list --path=moneyfusion
```

You should see:
```
POST  api/moneyfusion/webhook ......... moneyfusion.webhook
POST  moneyfusion/webhook ............ moneyfusion.webhook
```

### Test 2: Test Locally
```bash
curl -X POST http://cosmetics-app.test/api/moneyfusion/webhook \
  -H "Content-Type: application/json" \
  -d '{"token":"test123","statut":"paid"}'
```

Expected response:
```json
{"success":true,"message":"Webhook processed successfully"}
```

Or if payment not found:
```json
{"error":"Payment not found"}
```

### Test 3: Test via ngrok
After starting ngrok, test the public URL:
```bash
curl -X POST https://YOUR_NGROK_URL.ngrok-free.app/api/moneyfusion/webhook \
  -H "Content-Type: application/json" \
  -d '{"token":"690ea508d9ec896e723e5f4a","statut":"paid"}'
```

Use a real token from your database (check with: `php artisan moneyfusion:test-payment`)

### Test 4: Monitor Webhooks
1. Visit http://localhost:4040 (ngrok dashboard)
2. Make a test payment
3. Watch for incoming POST requests to `/api/moneyfusion/webhook`
4. Check Laravel logs: `php artisan pail` or view `storage/logs/laravel.log`

## Webhook Payload Format

MoneyFusion will send webhooks in this format:
```json
{
  "token": "690ea508d9ec896e723e5f4a",
  "statut": "paid",
  "numeroTransaction": "MF123456789",
  "moyen": "orange_money",
  "frais": 150,
  "montant": 5000
}
```

Possible `statut` values:
- `pending` - Payment initiated
- `paid` - Payment successful
- `failed` - Payment failed
- `cancelled` - Payment cancelled

## Troubleshooting

### ngrok URL changes every restart
**Solution**:
- Sign up for free ngrok account (get auth token)
- Or upgrade to ngrok Pro for static domain
- Or use Expose instead

### "Visit Site" warning page on ngrok
**Solution**:
- Click "Visit Site" button
- Or upgrade to ngrok Pro
- Or use Expose (no warning page)

### Webhook not receiving data
**Check these:**
1. ✅ ngrok is running: `http://localhost:4040`
2. ✅ Laravel logs: `php artisan pail`
3. ✅ Route exists: `php artisan route:list --path=moneyfusion`
4. ✅ `.env` has correct ngrok URL
5. ✅ Config cache cleared: `php artisan config:clear`

### 403 Forbidden on webhook
**Solution**: Webhook route already excludes CSRF middleware

### Laragon virtual host not working with ngrok
**Solution**: Use `--host-header` flag:
```bash
ngrok http 80 --host-header=cosmetics-app.test
```

## Monitoring Webhooks

### View in ngrok Dashboard
Visit: http://localhost:4040
- Shows all HTTP requests
- Request/response details
- Ability to replay requests

### View in Laravel Logs
```bash
# Live tail
php artisan pail

# Or view file directly
tail -f storage/logs/laravel.log | findstr MoneyFusion
```

### Check Database
```bash
php artisan tinker
```
```php
// Get all payments
App\Models\MoneyFusionPayment::all();

// Get paid payments
App\Models\MoneyFusionPayment::paid()->get();

// Check latest payment
App\Models\MoneyFusionPayment::latest()->first();
```

## Production Deployment

⚠️ **Important**: ngrok/tunneling is ONLY for local development!

For production:
1. Deploy to real server (VPS, shared hosting, cloud)
2. Get SSL certificate (Let's Encrypt, etc.)
3. Update MoneyFusion dashboard with production URLs
4. Update `.env`:
   ```env
   APP_URL=https://your-production-domain.com
   MONEYFUSION_WEBHOOK_URL=https://your-production-domain.com/api/moneyfusion/webhook
   MONEYFUSION_RETURN_URL=https://your-production-domain.com/payment/callback
   MONEYFUSION_VERIFY_SSL=true  # IMPORTANT!
   ```

## Quick Start Script

Use the provided batch file:
```bash
start-dev-with-webhook.bat
```

This will:
1. Check if ngrok is installed
2. Remind you to update `.env`
3. Start ngrok tunnel

## Additional Resources

- ngrok Documentation: https://ngrok.com/docs
- Expose Documentation: https://beyondco.de/docs/expose
- MoneyFusion API Docs: https://moneyfusion.net/dashboard/fusionpay

## Summary of Created Files

✅ Webhook Controller: `app/Http/Controllers/MoneyFusion/WebhookController.php`
✅ Callback Controller: `app/Http/Controllers/MoneyFusion/PaymentCallbackController.php`
✅ Error View: `resources/views/moneyfusion/error.blade.php`
✅ Routes: Added to `routes/web.php`
✅ Compatibility Provider: `app/Providers/MoneyFusionCompatibilityServiceProvider.php`

## Need Help?

Check the logs:
```bash
php artisan pail
```

View all MoneyFusion routes:
```bash
php artisan route:list --path=moneyfusion
```

Test payment creation:
```bash
php artisan moneyfusion:test-payment
```

Check payment status:
```bash
php artisan moneyfusion:check-payment {token}
```
