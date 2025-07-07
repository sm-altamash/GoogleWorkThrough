<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\GoogleToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Illuminate\Database\Eloquent\Relations\HasOne;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,HasRoles,AuthenticationLoggable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google2fa_secret',
        '2fa_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        '2fa_verified_at' => 'datetime',

    ];

    protected $dates = [
    '2fa_verified_at',
    ];


    public function googleToken(): HasOne
    {
        return $this->hasOne(GoogleToken::class);
    }
}
