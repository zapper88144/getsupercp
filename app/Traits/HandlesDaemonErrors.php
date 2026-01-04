<?php

namespace App\Traits;

use App\Exceptions\DaemonException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HandlesDaemonErrors
{
    /**
     * Wrap a daemon call with standardized error handling.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @param  (callable(Throwable): void)|null  $onError
     * @return T
     *
     * @throws DaemonException
     */
    protected function handleDaemonCall(callable $callback, string $errorMessage, ?callable $onError = null)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            if ($onError) {
                $onError($e);
            }

            throw new DaemonException($errorMessage, 0, null, $e);
        }
    }

    /**
     * Handle a daemon error and return a redirect or JSON response with error details.
     */
    protected function handleDaemonError(Throwable $e, ?string $defaultMessage = null, ?string $redirectTo = null, int $statusCode = 500): RedirectResponse|JsonResponse
    {
        Log::error('Daemon error handled in controller', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $message = $defaultMessage ?: 'An error occurred while communicating with the system daemon.';
        $suggestion = null;

        if ($e instanceof DaemonException) {
            $message = $e->getMessage();
            $suggestion = $e->getRecoverySuggestion();
        }

        if (request()->expectsJson() || request()->isXmlHttpRequest()) {
            return response()->json([
                'error' => $message,
                'message' => $message, // For compatibility with some tests
                'recovery_suggestion' => $suggestion,
            ], $statusCode);
        }

        $redirect = $redirectTo ? redirect($redirectTo) : back();

        return $redirect->with('error', $message)
            ->with('recovery_suggestion', $suggestion);
    }
}
