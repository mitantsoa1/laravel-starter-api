<?php

namespace App\Http\Middleware;

use App\Services\LogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware HttpLogMiddleware
 *
 * Log automatiquement chaque requête + réponse HTTP.
 * À enregistrer dans bootstrap/app.php (Laravel 11+) ou Kernel.php (Laravel 10).
 *
 * Laravel 11+ — bootstrap/app.php :
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->append(\App\Http\Middleware\HttpLogMiddleware::class);
 *   })
 *
 * Laravel 10 — app/Http/Kernel.php, dans $middleware :
 *   \App\Http\Middleware\HttpLogMiddleware::class,
 */
class HttpLogMiddleware
{
    public function __construct(private readonly LogService $log)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log de la requête entrante
        $this->log->request($request, [
            'route' => optional($request->route())->getName(),
        ]);

        /** @var Response $response */
        $response = $next($request);

        // Log de la réponse
        $durationMs = (microtime(true) - $startTime) * 1000;

        $this->log->response($response->getStatusCode(), $durationMs, [
            'route' => optional($request->route())->getName(),
        ]);

        return $response;
    }
}
