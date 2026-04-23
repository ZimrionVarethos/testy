<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /**
         * PERBAIKAN: Daftarkan model PersonalAccessToken versi MongoDB.
         *
         * Tanpa ini, Sanctum akan mencoba menyimpan token ke tabel SQL
         * (personal_access_tokens), yang tidak ada karena kita pakai MongoDB.
         * Akibatnya login berhasil tapi token tidak tersimpan → semua
         * endpoint protected mengembalikan 401.
         */
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}