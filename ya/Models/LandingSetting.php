<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class LandingSetting extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'landing_settings';
    protected $fillable   = ['key', 'value', 'type'];

    public static function get(string $key, $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Pakai raw upsert MongoDB agar lebih reliable daripada updateOrCreate
     */
    public static function set(string $key, string $value, string $type = 'image'): void
    {
        $existing = static::where('key', $key)->first();

        if ($existing) {
            $existing->value = $value;
            $existing->type  = $type;
            $existing->save();
        } else {
            static::create([
                'key'   => $key,
                'value' => $value,
                'type'  => $type,
            ]);
        }
    }

    public static function allAsArray(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }
}