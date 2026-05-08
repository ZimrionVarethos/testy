<?php

namespace App\Http\Controllers\Pengguna;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BookingController as ApiBooking;
use App\Http\Controllers\Api\VehicleController as ApiVehicle;
use App\Http\Traits\WebApiProxy;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    use WebApiProxy;

    public function index(Request $request, ApiVehicle $api, ApiBooking $apiBooking)
    {
        $type      = $request->query('type');
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        if (!$startDate || !$endDate) {
            return view('pengguna.vehicles.index', [
                'vehicles'  => null,
                'type'      => $type,
                'startDate' => null,
                'endDate'   => null,
            ]);
        }

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end   = Carbon::parse($endDate)->endOfDay();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Format tanggal tidak valid.']);
        }

        if ($start->isPast()) {
            return back()->withErrors(['error' => 'Tanggal mulai tidak boleh di masa lalu.']);
        }

        if ($end->lte($start)) {
            return back()->withErrors(['error' => 'Tanggal selesai harus setelah tanggal mulai.']);
        }

        $bookedVehicleIds = $apiBooking->bookedVehicleIdsForWeb($start, $end);

        $vehicleReq = $this->makeApiRequest([
            'type'     => $type,
            'sort'     => 'price_per_day',
            'per_page' => 9,
        ]);
        ['vehicles' => $vehicles] = $api->indexForWeb($vehicleReq);

        $vehicles->setCollection(
            $vehicles->getCollection()->filter(function ($v) use ($bookedVehicleIds) {
                return $v->status !== 'maintenance'
                    && !in_array((string) $v->_id, $bookedVehicleIds);
            })->values()
        );

        $durationDays = max(1, (int) ceil($start->floatDiffInDays($end)));

        return view('pengguna.vehicles.index', compact(
            'vehicles', 'type', 'startDate', 'endDate', 'durationDays'
        ));
    }

    public function show(string $id, ApiVehicle $api)
    {
        $vehicle = $api->showForWeb($id);
        return view('pengguna.vehicles.show', compact('vehicle'));
    }

    public function book(Request $request, string $id, ApiVehicle $api, ApiBooking $apiBooking)
    {
        $vehicle = $api->showForWeb($id);

        if ($vehicle->status === 'maintenance') {
            return redirect()->route('vehicles.index')
                ->withErrors(['error' => 'Kendaraan sedang dalam perawatan.']);
        }

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        if (!$startDate || !$endDate) {
            return redirect()->route('vehicles.index')
                ->withErrors(['error' => 'Pilih tanggal terlebih dahulu.']);
        }

        $start        = Carbon::parse($startDate);
        $end          = Carbon::parse($endDate);
        $durationDays = max(1, (int) ceil($start->floatDiffInDays($end)));
        $totalPrice   = $durationDays * $vehicle->price_per_day;

        $conflict = $apiBooking->conflictCheckForWeb((string) $vehicle->_id, $start, $end);

        if ($conflict) {
            return redirect()->route('vehicles.index', compact('startDate', 'endDate'))
                ->withErrors(['error' => 'Maaf, kendaraan ini baru saja dipesan orang lain. Pilih kendaraan lain.']);
        }

        return view('pengguna.vehicles.book', compact(
            'vehicle', 'startDate', 'endDate', 'durationDays', 'totalPrice'
        ));
    }

    public function storeBooking(Request $request, string $id, ApiBooking $api)
    {
        $req = $this->makeApiRequest([], [
            'vehicle_id'     => $id,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'pickup_address' => $request->pickup_address,
            'pickup_lat'     => $request->pickup_lat,
            'pickup_lng'     => $request->pickup_lng,
            'notes'          => $request->notes,
        ]);

        $result    = $this->proxyApi(fn() => $api->store($req));
        $bookingId = $result['data']['id'];

        return redirect(route('bookings.pay', $bookingId))
            ->with('success', 'Pesanan dibuat! Selesaikan pembayaran dalam 30 menit.');
    }
}
