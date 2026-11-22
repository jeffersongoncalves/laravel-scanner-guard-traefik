<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Endpoint
    |--------------------------------------------------------------------------
    |
    | When enabled, registers GET /{path} returning a Traefik dynamic
    | configuration (JSON) for its HTTP provider. Every request must send
    | `Authorization: Bearer {token}` (providers.http.headers); with no token
    | set the endpoint always answers 403. The response carries raw IPs.
    |
    */
    'enabled' => env('SCANNER_GUARD_TRAEFIK_ENABLED', false),

    'path' => env('SCANNER_GUARD_TRAEFIK_PATH', 'scanner-guard/traefik'),

    'token' => env('SCANNER_GUARD_TRAEFIK_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | File
    |--------------------------------------------------------------------------
    |
    | Where `php artisan scanner-guard:traefik-write` writes the same
    | configuration for Traefik's file provider (recommended): the file
    | survives the app being down, so Traefik keeps the last list instead
    | of dropping the middleware and every router using it.
    |
    */
    'file' => env('SCANNER_GUARD_TRAEFIK_FILE'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | `middleware` is the name routers reference as `{middleware}@http`.
    | `plugin` is the key the deny plugin is declared under in Traefik's
    | static config (experimental.plugins), `list_key` the plugin's option
    | holding the IPs. Defaults match github.com/kevtainer/denyip.
    |
    */
    'middleware' => env('SCANNER_GUARD_TRAEFIK_MIDDLEWARE', 'scanner-guard'),

    'plugin' => env('SCANNER_GUARD_TRAEFIK_PLUGIN', 'denyip'),

    'list_key' => env('SCANNER_GUARD_TRAEFIK_LIST_KEY', 'ipDenyList'),

    /*
    |--------------------------------------------------------------------------
    | Placeholder
    |--------------------------------------------------------------------------
    |
    | denyip refuses to load with an empty list, which takes down every router
    | using the middleware. With nothing banned, this address is served
    | instead: 192.0.2.1 is in TEST-NET-1 (RFC 5737), never a real client.
    |
    */
    'placeholder' => '192.0.2.1',

];
