<?php namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;

class DriverController extends Controller
{
    public function index()
    {
        $drivers = User::where('role', 'driver')->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.drivers.index', compact('drivers'));
    }

    public function show(string $id)
    {
        $driver   = User::findOrFail($id);
        $bookings = Booking::where('driver.driver_id', $id)->orderBy('created_at', 'desc')->limit(10)->get();
        return view('admin.drivers.show', compact('driver', 'bookings'));
    }

    public function toggle(string $id)
    {
        $driver = User::findOrFail($id);
        $driver->update(['is_active' => !$driver->is_active]);
        $status = $driver->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Driver berhasil {$status}.");
    }
}