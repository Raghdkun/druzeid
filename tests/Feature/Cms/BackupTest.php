<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Backup\BackupService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('backups');
    // Media is exercised separately; keep the archive to the DB dump here.
    config()->set('backup.include_media', false);
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('creates a zip backup containing the database dump', function () {
    $name = app(BackupService::class)->create('2026-07-28_120000');

    expect($name)->toBe('backup-2026-07-28_120000.zip');
    Storage::disk('backups')->assertExists($name);

    $zip = new ZipArchive;
    $zip->open(Storage::disk('backups')->path($name));
    expect($zip->locateName('database.sql'))->not->toBeFalse();
    expect($zip->getFromName('database.sql'))->toContain('CREATE TABLE');
    $zip->close();
});

it('encrypts the archive with AES-256 when a password is configured', function () {
    if (! defined('ZipArchive::EM_AES_256')) {
        $this->markTestSkipped('This PHP build has no AES ZIP support.');
    }

    config()->set('backup.password', 'a-strong-secret');
    $service = app(BackupService::class);

    expect($service->encrypted())->toBeTrue();

    $name = $service->create('2026-07-28_130000');

    $zip = new ZipArchive;
    $zip->open(Storage::disk('backups')->path($name));
    // Without the password the encrypted entry cannot be read.
    expect(@$zip->getFromName('database.sql'))->toBeFalse();
    // With it, the dump decrypts.
    $zip->setPassword('a-strong-secret');
    expect($zip->getFromName('database.sql'))->toContain('CREATE TABLE');
    $zip->close();
});

it('reports not-encrypted when no password is set', function () {
    config()->set('backup.password', null);

    expect(app(BackupService::class)->encrypted())->toBeFalse();
});

it('keeps only the configured number of backups', function () {
    config()->set('backup.keep', 2);
    $service = app(BackupService::class);

    $service->create('2026-07-28_100000');
    $service->create('2026-07-28_110000');
    $service->create('2026-07-28_120000');

    $names = collect($service->list())->pluck('name');

    expect($names)->toHaveCount(2)
        ->and($names)->toContain('backup-2026-07-28_120000.zip')
        ->and($names)->toContain('backup-2026-07-28_110000.zip')
        ->and($names)->not->toContain('backup-2026-07-28_100000.zip');
});

it('lists backups newest first', function () {
    $service = app(BackupService::class);
    $service->create('2026-07-28_100000');
    $service->create('2026-07-28_120000');

    expect(collect($service->list())->pluck('name')->all())
        ->toBe(['backup-2026-07-28_120000.zip', 'backup-2026-07-28_100000.zip']);
});

it('rejects invalid or traversal backup names', function () {
    $service = app(BackupService::class);

    expect($service->path('../.env'))->toBeNull()
        ->and($service->path('backup-bad.zip'))->toBeNull()
        ->and($service->path('backup-2026-07-28_120000.txt'))->toBeNull()
        ->and($service->delete('../secret'))->toBeFalse();
});

it('lets an admin create a backup from the dashboard', function () {
    $this->actingAs($this->admin)
        ->post('/cms/backups')
        ->assertRedirect();

    expect(app(BackupService::class)->list())->toHaveCount(1);
});

it('shows the backups dashboard to an admin', function () {
    app(BackupService::class)->create('2026-07-28_120000');

    $this->actingAs($this->admin)->get('/cms/backups')->assertInertia(fn ($p) => $p
        ->component('cms/backups/index')
        ->has('backups', 1)
        ->where('keep', (int) config('backup.keep'))
        ->has('encrypted')
    );
});

it('streams a backup download for an admin', function () {
    $name = app(BackupService::class)->create('2026-07-28_140000');

    $this->actingAs($this->admin)
        ->get("/cms/backups/{$name}/download")
        ->assertOk()
        ->assertDownload($name);
});

it('deletes a backup for an admin', function () {
    $name = app(BackupService::class)->create('2026-07-28_160000');

    $this->actingAs($this->admin)
        ->delete("/cms/backups/{$name}")
        ->assertRedirect();

    Storage::disk('backups')->assertMissing($name);
});

it('redirects a guest away from the backups dashboard', function () {
    $this->get('/cms/backups')->assertRedirect('/login');
});

it('forbids an editor from the backups dashboard', function () {
    $editor = User::factory()->create(['role' => 'editor']);

    $this->actingAs($editor)->get('/cms/backups')->assertForbidden();
});

it('forbids a non-admin from downloading a backup', function () {
    $name = app(BackupService::class)->create('2026-07-28_150000');
    $editor = User::factory()->create(['role' => 'editor']);

    $this->actingAs($editor)->get("/cms/backups/{$name}/download")->assertForbidden();
});

it('returns 404 for a download with an invalid name', function () {
    $this->actingAs($this->admin)
        ->get('/cms/backups/not-a-backup.zip/download')
        ->assertNotFound();
});

it('creates a backup via the artisan command', function () {
    $this->artisan('backup:run')->assertSuccessful();

    expect(app(BackupService::class)->list())->toHaveCount(1);
});
