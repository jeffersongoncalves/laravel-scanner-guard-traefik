<?php

declare(strict_types=1);

use JeffersonGoncalves\ScannerGuard\ScannerGuard;

beforeEach(function () {
    $this->file = sys_get_temp_dir().'/scanner-guard-traefik-'.uniqid().'.yml';
});

afterEach(function () {
    @unlink($this->file);
});

it('writes the denylist to the configured file', function () {
    config(['scanner-guard-traefik.file' => $this->file]);
    app(ScannerGuard::class)->applyDenylist([
        'bans' => [['ip' => '203.0.113.7', 'reason' => 'scanner_path', 'expires_at' => now()->addDay()->toIso8601String()]],
    ]);

    $this->artisan('scanner-guard:traefik-write')->assertSuccessful();

    expect(json_decode((string) file_get_contents($this->file), true))
        ->toBe(['http' => ['middlewares' => ['scanner-guard' => ['plugin' => ['denyip' => ['ipDenyList' => ['203.0.113.7']]]]]]])
        ->and(is_file($this->file.'.tmp'))->toBeFalse();
});

it('takes the path as an argument and writes the placeholder when nothing is banned', function () {
    $this->artisan('scanner-guard:traefik-write', ['path' => $this->file])->assertSuccessful();

    expect(json_decode((string) file_get_contents($this->file), true)['http']['middlewares']['scanner-guard']['plugin']['denyip']['ipDenyList'])
        ->toBe(['192.0.2.1']);
});

it('leaves an unchanged file alone', function () {
    $this->artisan('scanner-guard:traefik-write', ['path' => $this->file])->assertSuccessful();
    touch($this->file, 1);
    clearstatcache();

    $this->artisan('scanner-guard:traefik-write', ['path' => $this->file])->assertSuccessful();
    clearstatcache();

    expect(filemtime($this->file))->toBe(1);
});

it('fails without a path', function () {
    $this->artisan('scanner-guard:traefik-write')->assertFailed();
});
