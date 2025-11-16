@echo off
echo ==========================================
echo Starting Development Server with Webhooks
echo ==========================================
echo.

REM Check if ngrok is installed
where ngrok >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: ngrok is not installed!
    echo Please install ngrok from https://ngrok.com/download
    echo Or install via Chocolatey: choco install ngrok
    pause
    exit /b 1
)

echo Step 1: Starting Laravel development server on port 8000...
start "Laravel Server" cmd /k "php artisan serve"
timeout /t 3 /nobreak >nul

echo.
echo Step 2: Starting ngrok tunnel...
echo.
echo IMPORTANT: After ngrok starts, update your .env file with the ngrok URL
echo Look for the "Forwarding" line, example:
echo   Forwarding  https://xxxx.ngrok-free.app -^> http://localhost:8000
echo.
echo Then update these lines in your .env:
echo   APP_URL=https://xxxx.ngrok-free.app
echo   MONEYFUSION_WEBHOOK_URL=https://xxxx.ngrok-free.app/api/moneyfusion/webhook
echo   MONEYFUSION_RETURN_URL=https://xxxx.ngrok-free.app/payment/callback
echo.
echo After updating .env, run: php artisan config:clear
echo.
pause
ngrok http 8000
