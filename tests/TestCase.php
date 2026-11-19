<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ScannerGuardTraefik\Tests;

use JeffersonGoncalves\ScannerGuard\ScannerGuardServiceProvider;
use JeffersonGoncalves\ScannerGuardTraefik\ScannerGuardTraefikServiceProvider;
use JeffersonGoncalves\VisitorFingerprint\VisitorFingerprintServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            VisitorFingerprintServiceProvider::class,
            ScannerGuardServiceProvider::class,
            ScannerGuardTraefikServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('cache.default', 'array');
        // No database needed: bans come from the last pull of the http driver.
        $app['config']->set('scanner-guard.driver', 'http');
        $app['config']->set('scanner-guard-traefik.enabled', true);
        $app['config']->set('scanner-guard-traefik.token', 'traefik-secret');
    }
}
