<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;


class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

         // Force HTTPS di production
         // Penting untuk keamanan, terutama saat menangani pembayaran dan data sensitif.
         // Pastikan juga load balancer/proxy sudah mengirim header X-Forwarded-Proto dengan benar.
         // Jika aplikasi Anda di-deploy di platform seperti Heroku, AWS ELB, atau Cloudflare, biasanya sudah otomatis mengatur header ini.
         // Jika tidak, Anda mungkin perlu menambahkan konfigurasi tambahan di server/proxy Anda untuk memastikan header ini dikirim.
         // Catatan: jangan paksa HTTPS di lingkungan lokal/development karena bisa menyulitkan pengembangan.
        if (app()->environment('production')) {
            \Illuminate\Http\Request::setTrustedProxies(
                ['*'],
                \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
            );
            URL::forceScheme('https');
        }
    }
}