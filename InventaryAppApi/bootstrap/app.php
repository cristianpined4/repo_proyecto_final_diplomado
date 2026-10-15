<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\BranchSecurityMiddleware::class,
        ]);

        // Rate Limiting global para API (60 requests por minuto por IP)
        // $middleware->throttle(api: '60,1'); // Temporalmente comentado para compatibilidad con tests
    
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'sanctum/csrf-cookie',
            'login',
            'logout'
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Headers de seguridad adicionales
        $middleware->web(append: [
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $rawErrors = $e->validator->errors()->toArray();
                $allMessages = [];

                // Normalizar mensajes para que todo lo relativo a producto_id / product_id requerido diga "El producto es requerido."
                $normalizedErrors = [];
                foreach ($rawErrors as $field => $messages) {
                    $normalizedMessages = [];
                    foreach ($messages as $msg) {
                        if (preg_match('/(producto[_\s]?id|product[_\s]?id).*?(required|requerido|obligatorio)/i', $msg) ||
                            preg_match('/(producto|product).*?es (requerido|obligatorio)/i', $msg) ||
                            $field === 'producto_id' || $field === 'product_id' || str_contains($field, '.producto_id') || str_contains($field, '.product_id')) {
                            if (stripos($msg, 'exists') !== false || stripos($msg, 'no existe') !== false || stripos($msg, 'inválido') !== false) {
                                $normalizedMsg = 'El producto seleccionado no existe o no es válido.';
                            } else {
                                $normalizedMsg = 'El producto es requerido.';
                            }
                        } else {
                            $normalizedMsg = $msg;
                        }
                        $normalizedMessages[] = $normalizedMsg;
                        $allMessages[] = $normalizedMsg;
                    }
                    $normalizedErrors[$field] = $normalizedMessages;
                }

                $firstError = $allMessages[0] ?? 'Los datos proporcionados no son válidos.';
                $extraCount = count($allMessages) - 1;

                if ($extraCount > 0) {
                    $plural = $extraCount === 1 ? 'error más' : 'errores más';
                    $summaryMessage = "{$firstError} (y {$extraCount} {$plural})";
                } else {
                    $summaryMessage = $firstError;
                }

                return response()->json([
                    'message' => $summaryMessage,
                    'errors' => $normalizedErrors,
                ], 422);
            }
        });
    })->create();