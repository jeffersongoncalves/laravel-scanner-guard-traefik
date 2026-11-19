<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ScannerGuardTraefik;

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\ScannerGuardTraefik\Http\Controllers\TraefikConfigController;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ScannerGuardTraefikServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-scanner-guard-traefik')
            ->hasConfigFile('scanner-guard-traefik');
    }

    public function packageBooted(): void
    {
        // No middleware group: a machine-to-machine GET needs no session or CSRF.
        if (config('scanner-guard-traefik.enabled', false)) {
            Route::get((string) config('scanner-guard-traefik.path', 'scanner-guard/traefik'), TraefikConfigController::class)
                ->name('scanner-guard-traefik.config');
        }
    }
}
