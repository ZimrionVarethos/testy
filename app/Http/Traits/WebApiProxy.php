<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait WebApiProxy
{
    /**
     * Build a fake Request injected with the current session user,
     * so API controllers resolve $request->user() without a token.
     */
    protected function makeApiRequest(array $query = [], array $body = [], array $files = []): Request
    {
        $req = new Request(
            array_merge($query, $body), // query (GET params) — merge biar validate() bisa baca
            $body,                       // request (POST body)
            [], [], $files
        );
    
        $req->setUserResolver(fn() => Auth::user());
        return $req;
    }
    /**
     * Call an API controller method, decode JSON, and abort on failure.
     * Returns the full decoded array (including 'success', 'data', etc.).
     */
    protected function proxyApi(callable $fn): array
    {
        $response = $fn();
        $data = json_decode($response->getContent(), true) ?? [];
        if (!($data['success'] ?? false)) {
            abort($response->getStatusCode(), $data['message'] ?? 'Terjadi kesalahan server.');
        }
        return $data;
    }

    /**
     * Same as proxyApi but does NOT abort on failure — returns raw decoded array.
     * Useful when you need to inspect 'success' yourself.
     */
    protected function tryProxyApi(callable $fn): array
    {
        $response = $fn();
        return json_decode($response->getContent(), true) ?? [];
    }


    
}
