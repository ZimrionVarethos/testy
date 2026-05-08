<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\TicketController as ApiTicket;
use App\Http\Traits\WebApiProxy;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use WebApiProxy;

    public function index(Request $request, ApiTicket $api)
    {
        $req  = $this->makeApiRequest(['status' => $request->query('status', 'all')]);
        $data = $api->adminIndexForWeb($req);

        $all    = $data['all'];
        $status = $data['status'];
        $counts = $data['counts'];

        $page    = (int) $request->query('page', 1);
        $perPage = 15;
        $tickets = new \Illuminate\Pagination\LengthAwarePaginator(
            $all->slice(($page - 1) * $perPage, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.tickets.index', compact('tickets', 'status', 'counts'));
    }

    public function show(string $id, ApiTicket $api)
    {
        $ticket = $api->adminShowForWeb($id);

        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, string $id, ApiTicket $api)
    {
        $req    = $this->makeApiRequest([], $request->only(['message', 'admin_notes']));
        $result = $this->tryProxyApi(fn() => $api->adminReply($req, $id));

        if (!($result['success'] ?? false)) {
            return back()->withErrors(['error' => $result['message'] ?? 'Gagal mengirim balasan.']);
        }

        return back()->with('success', 'Balasan terkirim.');
    }

    public function updateStatus(Request $request, string $id, ApiTicket $api)
    {
        $req    = $this->makeApiRequest([], ['status' => $request->status]);
        $result = $this->tryProxyApi(fn() => $api->adminUpdateStatus($req, $id));

        if (!($result['success'] ?? false)) {
            return back()->withErrors(['error' => $result['message'] ?? 'Gagal memperbarui status.']);
        }

        return back()->with('success', 'Status tiket diperbarui.');
    }
}
