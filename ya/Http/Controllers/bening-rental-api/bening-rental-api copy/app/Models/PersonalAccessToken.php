<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;
use MongoDB\Laravel\Eloquent\Model as MongoModel;

/**
 * PersonalAccessToken (MongoDB)
 *
 * File ini HILANG dari project — AuthController sudah import model ini
 * tapi file-nya tidak pernah dibuat. Tanpa ini, login/register error.
 *
 * Sanctum secara default menyimpan token di tabel SQL.
 * Karena kita pakai MongoDB, kita perlu override model-nya.
 */
class PersonalAccessToken extends SanctumToken
{
    use \MongoDB\Laravel\Eloquent\DocumentModel;

    protected $connection = 'mongodb';
    protected $collection = 'personal_access_tokens';

    /**
     * Sanctum perlu tahu model mana yang dipakai untuk token.
     * Daftarkan di AppServiceProvider atau config/sanctum.php.
     */
}