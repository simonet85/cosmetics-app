# Setting Up Webhook Access for Local Development

## Why Do You Need This?

MoneyFusion needs to send payment notifications (webhooks) to your application when a payment status changes. Since your app is running locally (localhost), MoneyFusion can't reach it directly. We need to expose it to the internet temporarily.

## Option 1: ngrok (Recommended for Development)

### Quick Setup

1. **Install ngrok**
   - Download from: https://ngrok.com/download
   - Or via Chocolatey: `choco install ngrok`

2. **Sign up for free ngrok account** (optional but recommended)
   - Go to: https://dashboard.ngrok.com/signup
   - Get your auth token from: https://dashboard.ngrok.com/get-started/your-authtoken
   - Configure: `ngrok config add-authtoken YOUR_AUTH_TOKEN`

3. **Use the helper script**
   ```bash
   start-dev-with-webhook.bat
   ```

4. **Or manually start ngrok**
   ```bash
   # Terminal 1: Start Laravel
   php artisan serve

   # Terminal 2: Start ngrok
   ngrok http 8000
   ```

5. **Update .env with the ngrok URL**
   Copy the HTTPS URL from ngrok (e.g., `https://xxxx.ngrok-free.app`) and update:
   ```env
   APP_URL=https://xxxx.ngrok-free.app
   MONEYFUSION_WEBHOOK_URL=https://xxxx.ngrok-free.app/api/moneyfusion/webhook
   MONEYFUSION_RETURN_URL=https://xxxx.ngrok-free.app/payment/callback
   ```

6. **Clear config cache**
   ```bash
   php artisan config:clear
   ```

### ngrok Pro Features (Paid)

- **Static domain**: Your URL won't change each restart
- **Reserved domains**: Use custom subdomains
- **No "Visit Site" warning page**

Example with static domain:
```bash
ngrok http 8000 --domain=your-app.ngrok-free.app
```

## Option 2: Expose (Free Alternative)

Expose is similar to ngrok but simpler:

1. **Install via Composer**
   ```bash
   composer global require beyondcode/expose
   ```

2. **Start tunnel**
   ```bash
   expose share http://localhost:8000
   ```

3. **Update .env** with the provided URL

## Option 3: LocalTunnel (Free, No Signup)

1. **Install via npm**
   ```bash
   npm install -g localtunnel
   ```

2. **Start tunnel**
   ```bash
   lt --port 8000
   ```

3. **Update .env** with the provided URL

## Option 4: Laragon's Share Feature

If you're using Laragon:

1. Right-click Laragon tray icon
2. Click "Share" → "ngrok"
3. Follow the prompts

## Testing Your Webhook Endpoint

After setting up, test if your webhook is accessible:

### Method 1: Using Browser
Visit: `https://your-ngrok-url.ngrok-free.app/api/moneyfusion/webhook`

You should see a response (not a 404 error)

### Method 2: Using curl
```bash
curl -X POST https://your-ngrok-url.ngrok-free.app/api/moneyfusion/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

### Method 3: Check Laravel Routes
```bash
php artisan route:list --path=moneyfusion
```

## Monitoring Incoming Webhooks

### View ngrok Dashboard
When ngrok is running, visit: http://localhost:4040

This shows:
- All HTTP requests to your tunnel
- Request/response details
- Replay requests for debugging

### View Laravel Logs
```bash
php artisan pail
# or
tail -f storage/logs/laravel.log
```

## Production Deployment

⚠️ **Important**: ngrok/tunneling is ONLY for development!

For production:
1. Deploy to a real server with a public domain
2. Update MoneyFusion dashboard with production webhook URL
3. Set `MONEYFUSION_VERIFY_SSL=true`

## Troubleshooting

### ngrok URL changes on restart
**Solution**: Get a free ngrok account for a more stable URL, or use paid plan for static domain

### "Visit Site" warning page
**Solution**:
- Click "Visit Site" button once
- Or upgrade to ngrok Pro to remove this
- Or use alternative like Expose

### Webhook not receiving data
1. Check ngrok is running: http://localhost:4040
2. Check Laravel logs: `php artisan pail`
3. Verify webhook route exists: `php artisan route:list --path=moneyfusion`
4. Test manually with curl or Postman

### SSL/HTTPS errors in local dev
- Make sure `MONEYFUSION_VERIFY_SSL=false` in .env
- Always use HTTPS ngrok URL (not HTTP)

## Best Practice for Team Development

Create a `.env.example` file with placeholders:
```env
APP_URL=https://YOUR_NGROK_URL_HERE.ngrok-free.app
MONEYFUSION_WEBHOOK_URL=${APP_URL}/api/moneyfusion/webhook
MONEYFUSION_RETURN_URL=${APP_URL}/payment/callback
```

Each developer updates with their own ngrok URL.
