<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ScannerGuardTraefik;

use JeffersonGoncalves\ScannerGuard\Contracts\DenylistSink;
use RuntimeException;

/**
 * Writes the denylist for Traefik's file provider. As a scanner-guard sink
 * it runs on every ban, unban and blocklist change. Unlike the HTTP
 * provider, the file outlives the app: if the app is down the file is left
 * untouched and Traefik keeps serving the last list.
 */
class TraefikFileSink implements DenylistSink
{
    public function __construct(protected ?string $path = null) {}

    public function sync(array $entries): void
    {
        $path = (string) ($this->path ?? config('scanner-guard-traefik.file'));

        if ($path === '') {
            throw new RuntimeException('No Traefik file: set SCANNER_GUARD_TRAEFIK_FILE.');
        }

        // JSON is valid YAML, so a .yml file holding it loads in Traefik.
        $contents = json_encode(TraefikConfig::fromEntries($entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";

        // Unchanged: skip the write so Traefik doesn't reload every run.
        if (is_file($path) && file_get_contents($path) === $contents) {
            return;
        }

        // Temp file + rename, so Traefik never reads a half-written file. One
        // temp file per run (the queued sync and a manual traefik-write can
        // overlap), in the same directory so the rename stays atomic. It has
        // no .yml extension, so Traefik's directory watch ignores it.
        $dir = dirname($path);
        $tmp = @tempnam($dir, 'scanner-guard-');

        // tempnam() falls back to the system temp dir when $dir isn't usable.
        if ($tmp === false || realpath(dirname($tmp)) !== realpath($dir)) {
            if ($tmp !== false) {
                @unlink($tmp);
            }

            throw new RuntimeException("Could not write the Traefik file {$path}.");
        }

        // tempnam() creates 0600; Traefik may read as another user.
        if (@file_put_contents($tmp, $contents) === false || ! @chmod($tmp, 0644) || ! @rename($tmp, $path)) {
            @unlink($tmp);

            throw new RuntimeException("Could not write the Traefik file {$path}.");
        }
    }
}
