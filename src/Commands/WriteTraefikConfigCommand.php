<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ScannerGuardTraefik\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\ScannerGuard\ScannerGuard;
use JeffersonGoncalves\ScannerGuardTraefik\TraefikConfig;

/**
 * Writes the dynamic configuration for Traefik's file provider. Unlike the
 * HTTP provider, the file outlives the app: if the app is down (or the
 * denylist can't be read) the file is left untouched and Traefik keeps
 * serving the last list, so the routers using the middleware stay up.
 */
class WriteTraefikConfigCommand extends Command
{
    protected $signature = 'scanner-guard:traefik-write {path? : Defaults to scanner-guard-traefik.file}';

    protected $description = 'Write the denylist as a Traefik dynamic configuration file';

    public function handle(ScannerGuard $scannerGuard): int
    {
        $path = (string) ($this->argument('path') ?? config('scanner-guard-traefik.file'));

        if ($path === '') {
            $this->error('No path given: pass one or set SCANNER_GUARD_TRAEFIK_FILE.');

            return self::FAILURE;
        }

        // JSON is valid YAML, so a .yml file holding it loads in Traefik.
        $contents = json_encode(TraefikConfig::build($scannerGuard), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";

        // Unchanged: skip the write so Traefik doesn't reload every run.
        if (is_file($path) && file_get_contents($path) === $contents) {
            return self::SUCCESS;
        }

        // Temp file + rename, so Traefik never reads a half-written file.
        // Traefik's directory watch ignores the .tmp extension.
        $tmp = $path.'.tmp';

        if (file_put_contents($tmp, $contents) === false || ! rename($tmp, $path)) {
            $this->error("Could not write {$path}.");

            return self::FAILURE;
        }

        $this->info("Wrote {$path}.");

        return self::SUCCESS;
    }
}
