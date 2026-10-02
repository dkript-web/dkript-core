<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MenuOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'route_name',
        'order',
        'status',
    ];

    protected $casts = [
        'order' => 'integer',
        'status' => 'integer',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class)->orderBy('position');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_option')->withTimestamps();
    }
}
