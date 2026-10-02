<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'path',
        'is_preset',
    ];

    protected $casts = [
        'is_preset' => 'boolean',
    ];

    /**
     * Presets de imágenes aprobadas por Dkript Inc.
     */
    public static function defaultPresets(): array
    {
        return [
            ['name' => 'Dkript Logo', 'path' => 'assets/images/branding/logo-dkript.png'],
            ['name' => 'Dkript Icon', 'path' => 'assets/images/branding/icon-dkript.png'],
            ['name' => 'Drypt Oficial', 'path' => 'assets/images/branding/drypt-oficial.png'],
            ['name' => 'Drypt Alchemist', 'path' => 'assets/images/branding/icon-drypt-front.png'],
            ['name' => 'Drypt Banner', 'path' => 'assets/images/branding/banner-drypt.png'],
            ['name' => 'Drypt Workspace', 'path' => 'assets/images/branding/bg-developer-workspace.png'],
            ['name' => 'Drypt Side', 'path' => 'assets/images/branding/icon-drypt-side.png'],
            ['name' => 'Drypt Single', 'path' => 'assets/images/branding/icon-drypt-single.png'],
        ];
    }
}
