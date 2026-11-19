<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ScannerGuardTraefik\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JeffersonGoncalves\ScannerGuard\ScannerGuard;

/**
 * Polled by Traefik's HTTP provider: a dynamic configuration holding one
 * deny-IP plugin middleware with the current denylist. Entries come from
 * ScannerGuard::denylistEntries(), already validated, since one bad entry
 * would stop the plugin (and every router using it) from loading.
 */
class TraefikConfigController
{
    public function __invoke(Request $request, ScannerGuard $scannerGuard): JsonResponse
    {
        $token = (string) config('scanner-guard-traefik.token');

        abort_unless($token !== '' && hash_equals($token, (string) $request->bearerToken()), 403);

        $ips = array_column($scannerGuard->denylistEntries(), 'ip');

        return response()->json([
            'http' => [
                'middlewares' => [
                    (string) config('scanner-guard-traefik.middleware') => [
                        'plugin' => [
                            (string) config('scanner-guard-traefik.plugin') => [
                                (string) config('scanner-guard-traefik.list_key') => $ips !== [] ? $ips : [(string) config('scanner-guard-traefik.placeholder')],
                            ],
                        ],
                    ],
                ],
            ],
        ])->header('Cache-Control', 'no-store');
    }
}
