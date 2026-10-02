<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'otp_code',
        'channel',
        'purpose',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    public function hasExceededAttempts(int $maxAttempts = 5): bool
    {
        return $this->attempts >= $maxAttempts;
    }

    public function isAlreadyVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function isValid(string $code, int $maxAttempts = 5): bool
    {
        if ($this->isExpired() || $this->hasExceededAttempts($maxAttempts) || $this->isAlreadyVerified()) {
            return false;
        }

        return hash_equals((string)$this->otp_code, trim((string)$code));
    }
}