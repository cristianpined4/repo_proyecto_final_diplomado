<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UsersController extends Controller
{
    /**
     * Iniciar sesión.
     */
    public function login(Request $r)
    {
        // Cerrar cualquier sesión activa antes de procesar el nuevo login
        Auth::guard('web')->logout();

        $r->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Validar si el usuario o su sucursal están inactivos antes de autenticar (excepto roles globales para evitar lockouts)
        $userCheck = User::with('sucursal')->where('username', $r->username)->first();
        if ($userCheck) {
            if (!$userCheck->status) {
                return response()->json(['message' => 'El usuario se encuentra inactivo.', 'status' => false], 403);
            }
            $isGlobal = $userCheck->username === 'admin' || $userCheck->hasRole('admin') || $userCheck->hasRole('gerente');
            if (!$isGlobal && $userCheck->sucursal && !$userCheck->sucursal->activa) {
                return response()->json(['message' => 'La sucursal asignada a este usuario se encuentra inactiva.', 'status' => false], 403);
            }
        }

        if (!Auth::attempt(['username' => $r->username, 'password' => $r->password])) {
            return response()->json(['message' => 'Credenciales inválidas', 'status' => false], 401);
        }

        // Regenerar sesión para prevenir session fixation
        if ($r->hasSession()) {
            $r->session()->regenerate();
        }

        $user = Auth::user()->load(['roles', 'permissions']);

        // Registrar auditoría de Login
        \App\Models\AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'model_type' => User::class,
            'model_id' => $user->id,
            'ip_address' => $r->ip(),
            'user_agent' => $r->userAgent()
        ]);

        $formattedUser = [
            'id' => $user->id,
            'name' => $user->name,
            'lastname' => $user->lastname,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'sucursal_id' => $user->sucursal_id,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];

        return response()->json([
            'message' => 'Login exitoso',
            'data' => $formattedUser,
            'status' => true
        ], 200);
    }

    /**
     * Cerrar sesión.
     */
    public function logout(Request $r)
    {
        $user = $r->user();
        if ($user) {
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'model_type' => User::class,
                'model_id' => $user->id,
                'ip_address' => $r->ip(),
                'user_agent' => $r->userAgent()
            ]);

            $user->tokens()->delete();
        }

        Auth::guard('web')->logout();

        if ($r->hasSession()) {
            $r->session()->invalidate();
            $r->session()->regenerateToken();
        }

        return response()->json(['message' => 'Sesión finalizada', 'status' => true], 200);
    }

    /**
     * Obtener el perfil del usuario autenticado.
     */
    public function me(Request $r)
    {
        $user = $r->user();
        $formattedUser = [
            'id' => $user->id,
            'name' => $user->name,
            'lastname' => $user->lastname,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'sucursal_id' => $user->sucursal_id,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
        return response()->json(['user' => $formattedUser, 'status' => true], 200);
    }

    /**
     * Listado de usuarios con filtros y paginación.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');
        $sortBy = $request->input('sortBy', 'name');
        $descending = $request->input('descending', 'false') === 'true' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'name', 'lastname', 'username', 'created_at', 'email'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'name';
        }

        $query = User::with(['roles', 'sucursal']);

        if ($request->filled('rol')) {
            $rol = $request->input('rol');
            $query->whereHas('roles', function($q) use ($rol) {
                $q->where('name', $rol);
            });
        }

        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->input('sucursal_id'));
        }

        if ($request->filled('status')) {
            $statusVal = ($request->input('status') === '1' || $request->input('status') === 'true' || $request->input('status') === 1) ? 1 : 0;
            $query->where('status', $statusVal);
        }

        $startDate = $request->input('fecha_desde');
        $endDate = $request->input('fecha_hasta');

        if (!empty($startDate) && !empty($endDate)) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('users.created_at', [$start, $end]);
        } elseif (!empty($startDate)) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $query->where('users.created_at', '>=', $start);
        } elseif (!empty($endDate)) {
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $query->where('users.created_at', '<=', $end);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('lastname', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('username', 'ILIKE', "%{$search}%");
            });
        }

        $query->orderBy($sortBy, $descending);

        if ($limit > 0) {
            $users = $query->paginate($limit);
        } else {
            $users = $query->get();
        }

        // Obtener también roles y sucursales disponibles para los modales
        $rolesList = \Spatie\Permission\Models\Role::get(['id', 'name']);
        $sucursalesList = \App\Models\Sucursal::where('activa', true)->get(['id', 'nombre']);

        return response()->json([
            'success' => true,
            'data' => $users,
            'roles' => $rolesList,
            'sucursales' => $sucursalesList
        ], 200);
    }

    /**
     * Registrar un nuevo usuario.
     */
    public function store(Request $r)
    {
        $r->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:4',
            'sucursal_id' => 'nullable|exists:sucursals,id',
            'role' => 'required|exists:roles,name',
            'status' => 'required|boolean'
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $r->name,
                'lastname' => $r->lastname,
                'username' => $r->username,
                'email' => $r->email,
                'phone' => $r->phone,
                'password' => Hash::make($r->password),
                'sucursal_id' => $r->sucursal_id,
                'status' => $r->status
            ]);

            $user->assignRole($r->role);

            // Bitácora de Auditoría
            \App\Models\AuditLog::create([
                'user_id' => Auth::id() ?? 1,
                'action' => 'user_create',
                'model_type' => User::class,
                'model_id' => $user->id,
                'ip_address' => $r->ip(),
                'user_agent' => $r->userAgent()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'data' => $user->load('roles'), 'message' => 'Usuario creado correctamente'], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Actualizar datos de usuario (Administrativo).
     */
    public function update(Request $r, $id)
    {
        $user = User::findOrFail($id);

        $r->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:4',
            'sucursal_id' => 'nullable|exists:sucursals,id',
            'role' => 'required|exists:roles,name',
            'status' => 'required|boolean'
        ]);

        try {
            DB::beginTransaction();

            $user->name = $r->name;
            $user->lastname = $r->lastname;
            $user->username = $r->username;
            $user->email = $r->email;
            $user->phone = $r->phone;
            $user->sucursal_id = $r->sucursal_id;
            $user->status = $r->status;

            if ($r->password) {
                $user->password = Hash::make($r->password);
            }

            $user->save();

            // Sincronizar roles de Spatie
            $user->syncRoles($r->role);

            \App\Models\AuditLog::create([
                'user_id' => Auth::id() ?? 1,
                'action' => 'user_update',
                'model_type' => User::class,
                'model_id' => $user->id,
                'ip_address' => $r->ip(),
                'user_agent' => $r->userAgent()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'data' => $user->load('roles'), 'message' => 'Usuario actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Actualizar perfil de usuario propio.
     */
    public function updateProfile(Request $r)
    {
        $user = Auth::user();

        $r->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:4|confirmed',
        ]);

        try {
            DB::beginTransaction();

            $user->name = $r->name;
            $user->lastname = $r->lastname;
            $user->username = $r->username;
            $user->email = $r->email;
            $user->phone = $r->phone;

            if ($r->password) {
                $user->password = Hash::make($r->password);
            }

            $user->save();

            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'action' => 'profile_update',
                'model_type' => User::class,
                'model_id' => $user->id,
                'ip_address' => $r->ip(),
                'user_agent' => $r->userAgent()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'data' => $user->load('roles'), 'message' => 'Perfil actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Desactivar o eliminar usuario.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->username === 'admin') {
            return response()->json(['success' => false, 'message' => 'El administrador supremo del sistema no puede ser eliminado.'], 403);
        }

        try {
            DB::beginTransaction();
            
            // Verificar si tiene transacciones vinculadas (ventas o compras)
            $hasSales = \App\Models\Ventas::where('user_id', $id)->exists();
            $hasPurchases = \App\Models\Compras::where('user_id', $id)->exists();

            if ($hasSales || $hasPurchases) {
                // Desactivar en vez de eliminar físicamente para preservar integridad referencial de auditoría
                $user->status = false;
                $user->save();
                DB::commit();
                return response()->json(['success' => true, 'message' => 'El usuario tiene transacciones vinculadas, por lo que ha sido desactivado en lugar de eliminado.'], 200);
            }

            $user->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Usuario eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }
}
