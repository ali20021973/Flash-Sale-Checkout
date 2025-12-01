<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with custom log levels.
     */
    protected $levels = [];

    /**
     * A list of exception types that are not reported.
     */
    protected $dontReport = [];

    /**
     * A list of inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log if needed
        });
    }

    /**
     * Render exceptions as JSON for API requests.
     */
    public function render($request, Throwable $exception): JsonResponse
    {
        if ($request->is('api/*') || $request->wantsJson()) {
            $status = method_exists($exception, 'getStatusCode') 
                        ? $exception->getStatusCode() 
                        : 500;

            $message = $exception->getMessage() ?: 'Server Error';

            return response()->json([
                'success' => false,
                'message' => $message
            ], $status);
        }

        return parent::render($request, $exception);
    }
}
