<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Auth\User as Authenticatable;  // ← GANTI dari Illuminate\Foundation\Auth\User

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $connection = 'mongodb';  // ← TAMBAH
    protected $collection = 'users';   // ← TAMBAH

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',           // ← TAMBAH
        'phone',          // ← TAMBAH
        'is_active',      // ← TAMBAH
        'driver_profile', // ← TAMBAH
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];
}