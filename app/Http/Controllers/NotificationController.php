<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\NotificationController as ApiNotification;
use App\Http\Traits\WebApiProxy;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use WebApiProxy;

    public function index(Request $request, ApiNotification $api)
    {
        $filter = $request->query('filter', 'all');
        $req    = $this->makeApiRequest(['filter' => $filter]);

        ['notifications' => $notifications, 'counts' => $counts] = $api->indexForWeb($req);

        return view('notifications.index', compact('notifications', 'filter', 'counts'));
    }

    public function markRead(string $id, ApiNotification $api)
    {
        $this->proxyApi(fn() => $api->markRead($id));
        return back();
    }

    public function markAllRead(ApiNotification $api)
    {
        $this->proxyApi(fn() => $api->readAll());
        return back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }

    public function destroy(string $id, ApiNotification $api)
    {
        $this->proxyApi(fn() => $api->destroy($id));
        return back()->with('success', 'Notifikasi dihapus.');
    }

    public function destroyAll(ApiNotification $api)
    {
        $this->proxyApi(fn() => $api->destroyAll());
        return back()->with('success', 'Semua notifikasi dihapus.');
    }

    public function destroySelected(Request $request, ApiNotification $api)
    {
        $req = $this->makeApiRequest([], ['ids' => $request->ids]);
        $this->proxyApi(fn() => $api->destroySelected($req));

        return back()->with('success', count($request->ids ?? []) . ' notifikasi dihapus.');
    }
}
