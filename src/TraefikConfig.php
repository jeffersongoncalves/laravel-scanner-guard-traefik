<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ScannerGuardTraefik;

use JeffersonGoncalves\ScannerGuard\ScannerGuard;

/**
 * Traefik dynamic configuration holding one deny-IP plugin middleware with
 * the current denylist. Entries come from ScannerGuard::denylistEntries(),
 * already validated, since one bad entry (or an empty list) would stop the
 * plugin, and every router using it, from loading.
 */
class TraefikConfig
{
    /**
     * @return array{http: array{middlewares: array<string, array{plugin: array<string, array<string, list<string>>>}>}}
     */
    public static function build(ScannerGuard $scannerGuard): array
    {
        $ips = array_column($scannerGuard->denylistEntries(), 'ip');

        return [
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
        ];
    }
}
