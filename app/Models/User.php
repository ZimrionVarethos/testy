<?php
namespace App\Models;

use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'phone', 'is_active', 'driver_profile', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // ✅ Override createToken agar tidak hit SQL
    public function createToken(string $name, array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null)
    {
        $plainTextToken = Str::random(40);

        $token = PersonalAccessToken::create([
            'name'           => $name,
            'token'          => hash('sha256', $plainTextToken),
            'abilities'      => $abilities,
            'tokenable_type' => static::class,
            'tokenable_id'   => (string) $this->getKey(),
            'expires_at'     => $expiresAt,
        ]);

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }
}