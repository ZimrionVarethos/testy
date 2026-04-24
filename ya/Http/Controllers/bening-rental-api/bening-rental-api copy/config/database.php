<?php

use Illuminate\Support\Str;
use Pdo\Mysql;


return [

    'default' => env('DB_CONNECTION', 'mongodb'),

    'connections' => [

        // ──────────────────────────────────────────────────────────────
        //  MongoDB (DIPERBAIKI)
        //
        //  Sebelumnya ada 2 bug:
        //  1. Ada DUA key 'connections' di file ini → PHP hanya pakai
        //     yang terakhir, koneksi sqlite/mysql hilang (tapi tidak masalah
        //     karena kita hanya pakai mongodb)
        //  2. Konfigurasi pakai host/port, tapi .env sudah punya DB_URI
        //     (Atlas connection string) → harus pakai 'dsn', bukan host/port
        // ──────────────────────────────────────────────────────────────
        'mongodb' => [
            'driver'   => 'mongodb',
            'dsn'      => env('DB_URI'),        // ← PERBAIKAN: pakai URI Atlas
            'database' => env('DB_DATABASE', 'rental'),
        ],

        // Tetap ada untuk migration & testing lokal jika dibutuhkan
        'sqlite' => [
            'driver'                  => 'sqlite',
            'url'                     => env('DB_URL'),
            'database'                => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'                  => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver'    => 'mysql',
            'url'       => env('DB_URL'),
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'laravel'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ],

    ],

    'migrations' => [
        'table'                => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix'  => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')) . '-database-'),
        ],

        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];