<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\LandingController as ApiLanding;
use Illuminate\Support\Facades\Cache;

class WelcomeController extends Controller
{
    public function index(ApiLanding $landing)
    {
        // Panggil ApiLanding langsung in-process — hindari HTTP loopback (deadlock di php artisan serve)
        $data = Cache::remember('welcome:landing:v1', now()->addMinutes(5), function () use ($landing) {
            return $landing->index()->getData(true)['data'] ?? [];
        });

        $heroSlides    = $data['heroSlides'] ?? [];
        $landingImages = $data['landingImages'] ?? [];
        $stats         = $data['stats'] ?? [];

        // Konversi array ke object agar view bisa akses $vehicle->property
        $vehicles     = collect($data['vehicles'] ?? [])->map(fn($v) => (object) $v);
        $testimonials = collect($data['testimonials'] ?? []);

        $ratingAvg   = $data['ratingAvg'] ?? 4.9;
        $ratingCount = $data['ratingCount'] ?? 0;

        return view('welcome', compact(
            'heroSlides', 'landingImages', 'stats', 'vehicles',
            'testimonials', 'ratingAvg', 'ratingCount'
        ));
    }
}
