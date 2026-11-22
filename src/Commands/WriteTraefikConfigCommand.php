<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ScannerGuardTraefik\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\ScannerGuard\ScannerGuard;
use JeffersonGoncalves\ScannerGuardTraefik\TraefikFileSink;
use Throwable;

/**
 * Runs TraefikFileSink once: for the first write at boot, or by hand.
 * With the sink in scanner-guard.sinks, bans update the file on their own.
 */
class WriteTraefikConfigCommand extends Command
{
    protected $signature = 'scanner-guard:traefik-write {path? : Defaults to scanner-guard-traefik.file}';

    protected $description = 'Write the denylist as a Traefik dynamic configuration file';

    public function handle(ScannerGuard $scannerGuard): int
    {
        $path = $this->argument('path');

        try {
            (new TraefikFileSink(is_string($path) ? $path : null))->sync($scannerGuard->denylistEntries());
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Traefik file is up to date.');

        return self::SUCCESS;
    }
}
