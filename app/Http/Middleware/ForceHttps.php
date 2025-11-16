<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ForceHttps
{
    public function handle(Request $request, Closure $next)
    {
        // Forcer HTTPS si APP_FORCE_HTTPS=true
        if (config('app.force_https', false)) {
            URL::forceScheme('https');
            
            // Définir les headers pour proxy
            $request->server->set('HTTPS', 'on');
            $request->server->set('SERVER_PORT', 443);
        }

        return $next($request);
    }
}