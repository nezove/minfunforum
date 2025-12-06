<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeadersMiddleware
{
    public function handle($request, Closure $next)
{
    
    // Не изменяйте JSON ответы
    if ($response->headers->get('content-type') === 'application/json') {
        return $response;
    }
    

        $response = $next($request);

        // Защита от Clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');
        // Скрываем версию сервера
$response->headers->set('Server', 'WebServer');
$response->headers->set('X-Powered-By', '');

        // Защита от MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Включаем XSS Protection (хотя CSP лучше)
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Принудительное использование HTTPS (если используете HTTPS)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        
        // Настроено специально для вашего форума с Bootstrap, CDN и hCaptcha
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://js.hcaptcha.com https://hcaptcha.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com https://hcaptcha.com; " .
               "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " .
               "img-src 'self' data: https: blob:; " .
               "connect-src 'self' https://hcaptcha.com; " .
               "frame-src 'self' https://hcaptcha.com https://*.hcaptcha.com; " .
               "media-src 'self'; " .
               "object-src 'none'; " .
               "frame-ancestors 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self'";
               
        $response->headers->set('Content-Security-Policy', $csp);
        
        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Permissions Policy (раньше Feature-Policy)
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=(), usb=()');

        return $response;
    }
}