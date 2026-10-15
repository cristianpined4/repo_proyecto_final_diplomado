<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BranchSecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            // Bypass sucursal check for auth routes to prevent lockouts
            $path = $request->path();
            if ($request->is('*login*', '*logout*', '*me*') || in_array($path, ['api/login', 'api/logout', 'api/me', 'login', 'logout', 'me'])) {
                return $next($request);
            }

            $headerSucursalId = $request->header('X-Sucursal-Id');

            // Determinar si el usuario tiene rol administrativo (global)
            $isGlobalUser = $user->hasRole('admin') || $user->hasRole('gerente');

            // Si el usuario tiene una sucursal fija asignada y NO es un usuario global (admin/gerente)
            if ($user->sucursal_id !== null && !$isGlobalUser) {
                // Forzar que use únicamente su sucursal asignada
                $activeBranchId = $user->sucursal_id;
                
                if ($headerSucursalId && (int)$headerSucursalId !== (int)$user->sucursal_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Acceso denegado: Este usuario solo tiene permisos para operar en su sucursal asignada.'
                    ], 403);
                }
            } else {
                // Usuario Global/Admin/Gerente: Puede usar cualquier sucursal. Toma la del header, o usa la primera activa como default.
                if ($headerSucursalId) {
                    $activeBranchId = (int)$headerSucursalId;
                } else {
                    $activeBranchId = (int)\App\Models\Sucursal::where('activa', true)->value('id') ?? 1;
                }
            }

            // Registrar la sucursal activa en la sesión o en un Singleton para uso global en controladores y scopes
            app()->singleton('active_sucursal_id', function() use ($activeBranchId) {
                return $activeBranchId;
            });
        }

        return $next($request);
    }
}
