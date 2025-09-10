<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class OauthMailAccount extends Model
{
    protected $fillable = [
        'provider',
        'email',
        'access_token',
        'refresh_token',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => decrypt($value),
            set: fn ($value) => encrypt($value),
        );
    }

    public function isTokenExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isTokenExpiringSoon($minutes = 5)
    {
        return $this->expires_at && $this->expires_at->diffInMinutes(now()) <= $minutes;
    }
}
