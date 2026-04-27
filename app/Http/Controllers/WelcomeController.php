<?php

namespace App\Http\Controllers;

use App\Models\LandingSetting;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\User;
use App\Models\Rating;

class WelcomeController extends Controller
{
    private array $defaults = [
        'hero_slides' => [
            'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=85&w=1920',
            'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&q=85&w=1920',
            'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&q=85&w=1920',
        ],
        'how_it_works_image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&q=80&w=800',
        'cta_image'          => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?auto=format&fit=crop&q=80&w=900',
    ];

    public function index()
    {
        $heroSlides = LandingSetting::where('key', 'regexp', '/^hero_slide_\d+$/')
            ->orderBy('key', 'asc')
            ->pluck('value')
            ->values()
            ->toArray();

        if (empty($heroSlides)) {
            $heroSlides = $this->defaults['hero_slides'];
        }

        $landingImages = [
            'how_it_works_image' => LandingSetting::get('how_it_works_image', $this->defaults['how_it_works_image']),
            'why_us_mockup'      => LandingSetting::get('why_us_mockup', asset('image/mockup.png')),
            'cta_image'          => LandingSetting::get('cta_image', $this->defaults['cta_image']),
        ];

        $stats = [
            'total_vehicles'  => Vehicle::where('status', 'available')->count(),
            'total_bookings'  => Booking::where('status', 'completed')->count(),
            'happy_customers' => User::where('role', 'pengguna')->count(),
        ];

        $vehicles = Vehicle::where('status', '!=', 'maintenance')->limit(6)->get();

        // Ambil rating real — 4+ bintang, punya komentar, limit 10 terbaru
        // Lalu enrich dengan nama user dan info booking
        $realRatings = Rating::where('score', '>=', 4)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $testimonials = $realRatings->map(function ($rating) {
            // Ambil nama user
            $user = User::find($rating->user_id);
            // Ambil info booking untuk nama kendaraan
            $booking = \App\Models\Booking::find($rating->booking_id);

            $name    = $user?->name ?? 'Pelanggan';
            $vehicle = $booking?->vehicle['name'] ?? null;
            $role    = $vehicle ? 'Pengguna ' . $vehicle : 'Pelanggan Setia';
            $initials = strtoupper(collect(explode(' ', $name))->take(2)->map(fn($w) => $w[0] ?? '')->implode(''));

            return [
                'init'  => $initials,
                'name'  => $name,
                'role'  => $role,
                'stars' => $rating->score,
                'text'  => $rating->comment,
            ];
        })->values();

        // Hitung rata-rata rating real
        $ratingAvg   = $realRatings->avg('score') ?: 4.9;
        $ratingCount = Rating::count();

        return view('welcome', compact(
            'heroSlides', 'landingImages', 'stats', 'vehicles',
            'testimonials', 'ratingAvg', 'ratingCount'
        ));
    }
}