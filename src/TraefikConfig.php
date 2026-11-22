<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ScannerGuardTraefik;

/**
 * Traefik dynamic configuration holding one deny-IP plugin middleware with
 * the given denylist. Entries come from scanner-guard already validated,
 * since one bad entry (or an empty list) would stop the plugin, and every
 * router using it, from loading.
 */
class TraefikConfig
{
    /**
     * @param  list<array{ip: string, reason: string, expires_at: string|null}>  $entries
     * @return array{http: array{middlewares: array<string, array{plugin: array<string, array<string, list<string>>>}>}}
     */
    public static function fromEntries(array $entries): array
    {
        $ips = array_column($entries, 'ip');

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
