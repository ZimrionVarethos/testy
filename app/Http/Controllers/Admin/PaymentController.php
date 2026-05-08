<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\PaymentController as ApiPayment;
use App\Http\Traits\WebApiProxy;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use WebApiProxy;

    public function index(Request $request, ApiPayment $api)
    {
        $req = $this->makeApiRequest([
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ]);

        ['payments' => $payments, 'summary' => $summary] = $api->adminIndexForWeb($req);

        return view('admin.payments.index', compact('payments', 'summary'));
    }

    public function show(string $id, ApiPayment $api)
    {
        $req = $this->makeApiRequest();
        ['payment' => $payment, 'booking' => $booking] = $api->showForWeb($req, $id);

        return view('admin.payments.show', compact('payment', 'booking'));
    }
}
