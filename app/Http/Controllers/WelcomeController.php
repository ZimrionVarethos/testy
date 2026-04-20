<?php

namespace App\Http\Controllers;

use App\Models\LandingSetting;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\User;

class WelcomeController extends Controller
{
    // Default images jika belum ada setting di DB
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
        // Ambil slide hero secara dinamis dari DB
        $heroSlides = LandingSetting::where('key', 'regexp', '/^hero_slide_\d+$/')
            ->orderBy('key', 'asc')
            ->pluck('value')
            ->values()
            ->toArray();

        // Fallback ke default jika belum ada slide di DB
        if (empty($heroSlides)) {
            $heroSlides = $this->defaults['hero_slides'];
        }

        // Gambar statis
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

        $vehicles = Vehicle::where('status', 'available')->limit(6)->get();

        return view('welcome', compact('heroSlides', 'landingImages', 'stats', 'vehicles'));
    }
}