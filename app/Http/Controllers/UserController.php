<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Services\AuditService;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::with('profile.role')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                    });
            })
            ->orderBy('id', 'desc')
            ->paginate(\App\Models\Parameter::getSystemSettings()->records_per_page ?? 10);

        $roles = Role::where('status', 1)->get();

        return view('users.index', compact('users', 'roles', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:0,1'],
        ], [
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe contener al menos 8 caracteres.',
            'password.mixed' => 'La contraseña debe incluir al menos una letra mayúscula y una minúscula.',
            'password.letters' => 'La contraseña debe contener letras.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.symbols' => 'La contraseña debe incluir al menos un carácter especial.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
        ]);

        Profile::create([
            'user_id' => $user->id,
            'role_id' => $validated['role_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'avatar' => null,
        ]);

        Log::info('USER_CREATED', [
            'actor_id' => auth()->id(),
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        AuditService::log('CREATE', 'USERS', "Creación de usuario '{$user->name}' ({$user->email})", null, [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $validated['role_id'],
            'status' => $user->status,
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario registrado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:0,1'],
        ], [
            'password.min' => 'La contraseña debe contener al menos 8 caracteres.',
            'password.mixed' => 'La contraseña debe incluir al menos una letra mayúscula y una minúscula.',
            'password.letters' => 'La contraseña debe contener letras.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.symbols' => 'La contraseña debe incluir al menos un carácter especial.',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->status = $validated['status'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if ($user->profile) {
            $user->profile->update([
                'role_id' => $validated['role_id'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
            ]);
        } else {
            Profile::create([
                'user_id' => $user->id,
                'role_id' => $validated['role_id'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
            ]);
        }

        Log::info('USER_UPDATED', [
            'actor_id' => auth()->id(),
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        AuditService::log('UPDATE', 'USERS', "Actualización de usuario '{$user->name}' ({$user->email})", null, [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $validated['role_id'],
            'status' => $user->status,
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        if ($user->id === 1) {
            return back()->with('error', 'No se puede eliminar el usuario Super Administrador.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $deletedId = $user->id;
        $deletedName = $user->name;
        $deletedEmail = $user->email;
        $user->delete();

        Log::info('USER_DELETED', [
            'actor_id' => auth()->id(),
            'deleted_user_id' => $deletedId,
            'deleted_user_email' => $deletedEmail,
            'ip' => request()->ip(),
        ]);

        AuditService::log('DELETE', 'USERS', "Eliminación de usuario '{$deletedName}' ({$deletedEmail})", [
            'id' => $deletedId,
            'name' => $deletedName,
            'email' => $deletedEmail,
        ], null);

        return redirect()->route('users.index')->with('success', 'Usuario eliminado exitosamente.');
    }

    public function exportExcel(\App\Services\ExportService $exportService)
    {
        $users = User::with('profile.role')->orderBy('id', 'asc')->get();

        $headers = [
            'ID',
            'Usuario',
            'Nombre Completo',
            'Correo Electrónico',
            'Rol',
            'Teléfono',
            'Estatus',
        ];

        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user->id,
                $user->name,
                $user->profile?->full_name ?: 'Sin perfil',
                $user->email,
                $user->role?->name ?: 'Sin Rol',
                $user->profile?->phone ?: 'N/A',
                $user->status == 1 ? 'Activo' : 'Inactivo',
            ];
        }

        return $exportService->downloadExcel('Reporte de Usuarios', $headers, $rows, 'usuarios_' . date('Y-m-d'));
    }

    public function exportPdf(Request $request, \App\Services\ExportService $exportService)
    {
        $users = User::with('profile.role')->orderBy('id', 'asc')->get();
        $action = $request->query('action', 'download');

        return $exportService->generatePdf('reports.users', [
            'reportTitle' => 'Catálogo General de Usuarios',
            'users' => $users,
        ], 'reporte_usuarios_' . date('Y-m-d'), $action);
    }
}
