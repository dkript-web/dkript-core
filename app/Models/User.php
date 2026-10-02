<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'integer',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function getRoleAttribute()
    {
        return $this->profile?->role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->profile?->role_id === 1;
    }

    /**
     * Valida si el usuario tiene asignada la posición RBAC requerida en un módulo específico.
     * Posiciones: 1=Crear, 2=Editar, 3=Eliminar, 4=Ver/PDF, 5=Especial/Excel.
     */
    public function hasPermission(int|string $moduleId, int|string $position): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $matrix = session('permission_matrix');
        if (is_array($matrix)) {
            if (in_array('*', $matrix, true)) {
                return true;
            }
            return in_array("{$moduleId}:{$position}", $matrix);
        }

        $role = $this->profile?->role;
        if (!$role || $role->status != 1) {
            return false;
        }

        return $role->permissions()
            ->where('permissions.menu_option_id', (int)$moduleId)
            ->where('permissions.position', (int)$position)
            ->where('permissions.status', 1)
            ->exists();
    }

    /**
     * Determina si el usuario tiene la autenticación de dos factores (2FA TOTP) activa y confirmada.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    /**
     * Determina si el usuario inició la configuración de 2FA pero aún no la ha confirmado.
     */
    public function hasTwoFactorPending(): bool
    {
        return !is_null($this->two_factor_secret) && is_null($this->two_factor_confirmed_at);
    }
}


