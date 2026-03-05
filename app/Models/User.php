<?php
namespace App\Models;

use MongoDB\Laravel\Auth\User as Authenticatable;
use MongoDB\Laravel\Sanctum\HasApiTokens; // ← GANTI ini
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // pakai HasApiTokens dari MongoDB

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'phone', 'is_active', 'driver_profile', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        // 'password' => 'hashed', ← tetap hapus ini
        'is_active'         => 'boolean',
    ];
}