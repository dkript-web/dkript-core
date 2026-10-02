<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\MenuOption;
use App\Services\AuditService;

class AuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if ($request->query('expired')) {
            session()->flash('warning', 'Tu sesión ha expirado por inactividad. Por favor ingresa tus credenciales nuevamente.');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']) . '|' . $request->ip());

        // Bloqueo por fuerza bruta (máximo 5 intentos fallidos en 60 segundos)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            Log::warning('AUTH_LOGIN_THROTTLED', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
                'seconds' => $seconds,
            ]);

            return back()->withErrors([
                'email' => "Demasiados intentos de acceso fallidos. Por seguridad, intente nuevamente en {$seconds} segundos.",
            ])->onlyInput('email');
        }

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Auth::validate($credentials)) {
            RateLimiter::hit($throttleKey, 60);

            Log::warning('AUTH_LOGIN_FAILED', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            ])->onlyInput('email');
        }

        if ($user->status != 1) {
            RateLimiter::hit($throttleKey, 60);

            Log::warning('AUTH_LOGIN_INACTIVE_USER', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => 'Tu cuenta se encuentra inactiva. Por favor contacta al administrador.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put([
                'login.id' => $user->id,
                'login.remember' => $request->boolean('remember'),
            ]);

            Log::info('AUTH_2FA_CHALLENGE_REQUIRED', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        Log::info('AUTH_LOGIN_SUCCESS', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        AuditService::log('LOGIN', 'AUTH', 'Inicio de sesión exitoso al panel', null, null, $user);

        static::establishUserSession($user);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Carga de Permisos y Módulos en Sesión (Estándar Dkript RBAC)
     */
    public static function establishUserSession(User $user): void
    {
        $roleId = $user->profile?->role_id;

        if ($roleId === 1) {
            // Super Administrador: Acceso absoluto automático
            $allOptionIds = MenuOption::where('status', 1)->pluck('id')->toArray();
            session([
                'myoptions' => $allOptionIds,
                'mypermits' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'permission_matrix' => ['*'],
            ]);
        } else {
            // Otros roles: consulta de módulos y permisos asignados
            $role = $user->profile?->role;
            if ($role && $role->status == 1) {
                $options = $role->menuOptions()->where('menu_options.status', 1)->pluck('menu_options.id')->toArray();
                $permits = $role->permissions()->where('permissions.status', 1)->pluck('permissions.position')->unique()->values()->toArray();
                $matrix = $role->permissions()->where('permissions.status', 1)
                    ->get(['menu_option_id', 'position'])
                    ->map(fn($p) => "{$p->menu_option_id}:{$p->position}")
                    ->toArray();
            } else {
                $options = [];
                $permits = [];
                $matrix = [];
            }

            session([
                'myoptions' => $options,
                'mypermits' => $permits,
                'permission_matrix' => $matrix,
            ]);
        }
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            Log::info('AUTH_LOGOUT', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            AuditService::log('LOGOUT', 'AUTH', 'Cierre de sesión seguro del sistema', null, null, $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Has cerrado sesión exitosamente.');
    }
}

