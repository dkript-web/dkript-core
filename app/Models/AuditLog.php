<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'action',
        'module',
        'description',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'url',
        'method',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope para filtrado por búsqueda de texto general.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
              ->orWhere('user_name', 'like', "%{$term}%")
              ->orWhere('user_email', 'like', "%{$term}%")
              ->orWhere('ip_address', 'like', "%{$term}%")
              ->orWhere('action', 'like', "%{$term}%")
              ->orWhere('module', 'like', "%{$term}%");
        });
    }

    /**
     * Scope para filtrado por módulo.
     */
    public function scopeFilterByModule(Builder $query, ?string $module): Builder
    {
        if (empty($module) || $module === 'all') {
            return $query;
        }

        return $query->where('module', strtoupper($module));
    }

    /**
     * Scope para filtrado por acción.
     */
    public function scopeFilterByAction(Builder $query, ?string $action): Builder
    {
        if (empty($action) || $action === 'all') {
            return $query;
        }

        return $query->where('action', strtoupper($action));
    }

    /**
     * Scope para filtrado por rango de fechas.
     */
    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if (!empty($from)) {
            $query->whereDate('created_at', '>=', $from);
        }

        if (!empty($to)) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }
}
