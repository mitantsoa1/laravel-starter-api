<?php

namespace App\Traits;

use App\Services\LogService;
use Illuminate\Http\Request;
use Throwable;

/**
 * Trait LoggableTrait
 *
 * À inclure dans n'importe quel contrôleur pour accéder
 * facilement aux méthodes de log sans injection répétée.
 *
 * Usage dans un contrôleur :
 *   use App\Traits\LoggableTrait;
 *   class UserController extends Controller {
 *       use LoggableTrait;
 *   }
 */
trait LoggableTrait
{
    private ?LogService $_logService = null;

    /**
     * Retourne (ou instancie) le LogService.
     */
    protected function logger(): LogService
    {
        if ($this->_logService === null) {
            $this->_logService = app(LogService::class);
        }

        return $this->_logService;
    }

    /**
     * Log une action métier réussie.
     * ex: $this->logAction('user.created', ['user_id' => $user->id]);
     */
    protected function logAction(string $action, array $context = [], ?string $channel = null): void
    {
        $context['controller'] = static::class;
        $this->logger()->action($action, $context, $channel);
    }

    /**
     * Log une erreur (avec ou sans exception).
     */
    protected function logError(string $message, ?Throwable $e = null, array $context = [], ?string $channel = null): void
    {
        $context['controller'] = static::class;
        $this->logger()->error($message, $e, $context, $channel);
    }

    /**
     * Log un avertissement.
     */
    protected function logWarning(string $message, array $context = [], ?string $channel = null): void
    {
        $context['controller'] = static::class;
        $this->logger()->warning($message, $context, $channel);
    }

    /**
     * Log un message de debug.
     */
    protected function logDebug(string $message, array $context = [], ?string $channel = null): void
    {
        $context['controller'] = static::class;
        $this->logger()->debug($message, $context, $channel);
    }

    /**
     * Log la requête HTTP courante.
     */
    protected function logRequest(Request $request, array $extra = [], ?string $channel = null): void
    {
        $extra['controller'] = static::class;
        $this->logger()->request($request, $extra, $channel);
    }
}
