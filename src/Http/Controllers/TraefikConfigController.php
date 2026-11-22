<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ScannerGuardTraefik\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JeffersonGoncalves\ScannerGuard\ScannerGuard;
use JeffersonGoncalves\ScannerGuardTraefik\TraefikConfig;

/**
 * Polled by Traefik's HTTP provider.
 */
class TraefikConfigController
{
    public function __invoke(Request $request, ScannerGuard $scannerGuard): JsonResponse
    {
        $token = (string) config('scanner-guard-traefik.token');

        abort_unless($token !== '' && hash_equals($token, (string) $request->bearerToken()), 403);

        return response()->json(TraefikConfig::build($scannerGuard))->header('Cache-Control', 'no-store');
    }
}
