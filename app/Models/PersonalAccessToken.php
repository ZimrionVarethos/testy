<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Laravel\Sanctum\Contracts\HasAbilities;
use Illuminate\Support\Str;

class PersonalAccessToken extends Model implements HasAbilities
{
    protected $connection = 'mongodb';
    protected $collection = 'personal_access_tokens';

    protected $guarded = [];

    protected $hidden = ['token'];

    protected $casts = ['abilities' => 'json', 'last_used_at' => 'datetime', 'expires_at' => 'datetime'];

    public function can($ability): bool
    {
        return in_array('*', $this->abilities ?? [])
            || in_array($ability, $this->abilities ?? []);
    }

    public function cant($ability): bool
    {
        return ! $this->can($ability);
    }

    public function tokenable()
    {
        return $this->morphTo('tokenable');
    }

    public static function findToken($token): ?static
    {
        if (! str_contains($token, '|')) return null;

        [$id, $plain] = explode('|', $token, 2);

        $instance = static::find($id);

        return $instance && hash_equals(hash('sha256', $plain), $instance->token)
            ? $instance
            : null;
    }
}