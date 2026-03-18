<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LogService
{
    /**
     * Niveaux de log disponibles.
     */
    protected array $levels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    /**
     * Channel par défaut (configuré dans config/logging.php).
     */
    protected string $defaultChannel = 'stack';

    // -------------------------------------------------------------------------
    // Méthodes principales
    // -------------------------------------------------------------------------

    /**
     * Log une action utilisateur (succès d'une opération CRUD, etc.).
     *
     * @param string      $action   ex: "user.created", "product.updated"
     * @param array       $context  Données supplémentaires à enregistrer
     * @param string|null $channel  Channel Laravel (ex: 'daily', 'slack')
     */
    public function action(string $action, array $context = [], ?string $channel = null): void
    {
        $this->log('info', $action, $this->enrichContext($context), $channel);
    }

    /**
     * Log une erreur métier ou technique.
     *
     * @param string          $message  Description de l'erreur
     * @param \Throwable|null $exception Exception PHP (optionnelle)
     * @param array           $context  Contexte additionnel
     * @param string|null     $channel
     */
    public function error(string $message, ?\Throwable $exception = null, array $context = [], ?string $channel = null): void
    {
        if ($exception) {
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ];
        }

        $this->log('error', $message, $this->enrichContext($context), $channel);
    }

    /**
     * Log un avertissement.
     */
    public function warning(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('warning', $message, $this->enrichContext($context), $channel);
    }

    /**
     * Log un message de debug (uniquement en développement).
     */
    public function debug(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('debug', $message, $this->enrichContext($context), $channel);
    }

    /**
     * Log une requête HTTP entrante (utile pour les APIs).
     */
    public function request(Request $request, array $extra = [], ?string $channel = null): void
    {
        $context = [
            'method'      => $request->method(),
            'url'         => $request->fullUrl(),
            'ip'          => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'payload'     => $this->sanitizePayload($request->all()),
        ];

        $this->log('info', 'HTTP Request', $this->enrichContext(array_merge($context, $extra)), $channel);
    }

    /**
     * Log une réponse HTTP (code, durée d'exécution, etc.).
     */
    public function response(int $statusCode, float $durationMs, array $extra = [], ?string $channel = null): void
    {
        $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info');

        $context = [
            'status_code'   => $statusCode,
            'duration_ms'   => round($durationMs, 2),
        ];

        $this->log($level, 'HTTP Response', $this->enrichContext(array_merge($context, $extra)), $channel);
    }

    /**
     * Log générique avec niveau personnalisé.
     */
    public function write(string $level, string $message, array $context = [], ?string $channel = null): void
    {
        if (! in_array($level, $this->levels)) {
            throw new \InvalidArgumentException("Niveau de log invalide : {$level}");
        }

        $this->log($level, $message, $this->enrichContext($context), $channel);
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    /**
     * Dispatch l'écriture vers le bon channel Laravel.
     */
    protected function log(string $level, string $message, array $context, ?string $channel): void
    {
        $logger = $channel
            ? Log::channel($channel)
            : Log::channel($this->defaultChannel);

        $logger->{$level}($message, $context);
    }

    /**
     * Enrichit automatiquement le contexte avec des métadonnées utiles.
     */
    protected function enrichContext(array $context): array
    {
        return array_merge([
            'user_id'    => Auth::id(),
            'user_email' => optional(Auth::user())->email,
            'session_id' => session()->getId(),
            'timestamp'  => now()->toIso8601String(),
            'env'        => app()->environment(),
        ], $context);
    }

    /**
     * Supprime les champs sensibles du payload avant de les logger.
     */
    protected function sanitizePayload(array $payload): array
    {
        $sensitiveFields = config('logging.sensitive_fields', [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'credit_card',
            'card_number',
            'cvv',
        ]);

        foreach ($sensitiveFields as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = '***REDACTED***';
            }
        }

        return $payload;
    }
}
