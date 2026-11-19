<?php

declare(strict_types=1);

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\ScannerGuard\ScannerGuard;
use JeffersonGoncalves\ScannerGuardTraefik\ScannerGuardTraefikServiceProvider;

function traefikPoll(string $token = 'traefik-secret')
{
    return test()->getJson('/scanner-guard/traefik', ['Authorization' => "Bearer {$token}"]);
}

it('serves the denylist as a denyip plugin middleware', function () {
    config(['scanner-guard.ip_blocklist' => ['185.177.72.0/24', '2001:db8::/32', 'not-an-ip']]);
    app(ScannerGuard::class)->applyDenylist([
        'bans' => [['ip' => '203.0.113.7', 'reason' => 'scanner_path', 'expires_at' => now()->addDay()->toIso8601String()]],
    ]);

    traefikPoll()
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertExactJson([
            'http' => ['middlewares' => ['scanner-guard' => ['plugin' => ['denyip' => [
                'ipDenyList' => ['185.177.72.0/24', '2001:db8::/32', '203.0.113.7'],
            ]]]]],
        ]);
});

it('serves the placeholder when nothing is banned, so the plugin still loads', function () {
    traefikPoll()->assertOk()->assertJsonPath('http.middlewares.scanner-guard.plugin.denyip.ipDenyList', ['192.0.2.1']);
});

it('uses the configured middleware, plugin and list key names', function () {
    config([
        'scanner-guard-traefik.middleware' => 'block-scanners',
        'scanner-guard-traefik.plugin' => 'mydeny',
        'scanner-guard-traefik.list_key' => 'sourceRange',
    ]);

    traefikPoll()->assertOk()->assertJsonPath('http.middlewares.block-scanners.plugin.mydeny.sourceRange', ['192.0.2.1']);
});

it('rejects a missing or wrong token', function () {
    test()->getJson('/scanner-guard/traefik')->assertForbidden();
    traefikPoll('wrong')->assertForbidden();
});

it('always rejects when no token is configured', function () {
    config(['scanner-guard-traefik.token' => null]);

    traefikPoll('')->assertForbidden();
});

it('does not register the route unless enabled', function () {
    config(['scanner-guard-traefik.enabled' => false]);
    Route::setRoutes(new RouteCollection);

    (new ScannerGuardTraefikServiceProvider(app()))->packageBooted();

    expect(Route::has('scanner-guard-traefik.config'))->toBeFalse();
});
