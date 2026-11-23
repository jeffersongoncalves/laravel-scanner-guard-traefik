<?php

declare(strict_types=1);

use JeffersonGoncalves\ScannerGuard\ScannerGuard;
use JeffersonGoncalves\ScannerGuardTraefik\TraefikFileSink;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/scanner-guard-traefik-'.uniqid();
    mkdir($this->dir);
    $this->file = $this->dir.'/scanner-guard.yml';
    config(['scanner-guard-traefik.file' => $this->file]);
});

afterEach(function () {
    array_map('unlink', glob($this->dir.'/*') ?: []);
    rmdir($this->dir);
});

function traefikFileIps(string $file): array
{
    return json_decode((string) file_get_contents($file), true)['http']['middlewares']['scanner-guard']['plugin']['denyip']['ipDenyList'];
}

it('writes the file from the entries', function () {
    (new TraefikFileSink)->sync([
        ['ip' => '185.177.72.0/24', 'reason' => 'static', 'expires_at' => null],
        ['ip' => '203.0.113.7', 'reason' => 'scanner_path', 'expires_at' => null],
    ]);

    // Only the target is left: the run's temp file was renamed onto it.
    expect(traefikFileIps($this->file))->toBe(['185.177.72.0/24', '203.0.113.7'])
        ->and(array_map('basename', glob($this->dir.'/*') ?: []))->toBe(['scanner-guard.yml']);
});

it('leaves the file readable by other users', function () {
    (new TraefikFileSink)->sync([]);

    expect(fileperms($this->file) & 0777)->toBe(0644);
})->skipOnWindows();

it('writes the placeholder for an empty list', function () {
    (new TraefikFileSink)->sync([]);

    expect(traefikFileIps($this->file))->toBe(['192.0.2.1']);
});

it('does not rewrite an unchanged file', function () {
    (new TraefikFileSink)->sync([]);
    touch($this->file, 1);
    clearstatcache();

    (new TraefikFileSink)->sync([]);
    clearstatcache();

    expect(filemtime($this->file))->toBe(1);
});

it('throws when no file is set', function () {
    config(['scanner-guard-traefik.file' => null]);

    (new TraefikFileSink)->sync([]);
})->throws(RuntimeException::class, 'SCANNER_GUARD_TRAEFIK_FILE');

it('throws when the file is not writable', function () {
    (new TraefikFileSink(sys_get_temp_dir().'/scanner-guard-missing-'.uniqid().'/scanner-guard.yml'))->sync([]);
})->throws(RuntimeException::class, 'Could not write');

it('runs as a scanner-guard sink on sync-denylist', function () {
    config(['scanner-guard.sinks' => [TraefikFileSink::class], 'scanner-guard.sync_on_ban' => false]);
    app(ScannerGuard::class)->applyDenylist([
        'bans' => [['ip' => '203.0.113.7', 'reason' => 'scanner_path', 'expires_at' => now()->addDay()->toIso8601String()]],
    ]);

    $this->artisan('scanner-guard:sync-denylist')->assertSuccessful();

    expect(traefikFileIps($this->file))->toBe(['203.0.113.7']);
});

it('writes the same file as the command', function () {
    $entries = [['ip' => '203.0.113.7', 'reason' => 'scanner_path', 'expires_at' => now()->addDay()->toIso8601String()]];
    app(ScannerGuard::class)->applyDenylist(['bans' => $entries]);

    $this->artisan('scanner-guard:traefik-write')->assertSuccessful();
    $fromCommand = file_get_contents($this->file);
    unlink($this->file);

    (new TraefikFileSink)->sync(app(ScannerGuard::class)->denylistEntries());

    expect(file_get_contents($this->file))->toBe($fromCommand);
});
