<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('stores an uploaded image as an image', function () {
    $this->actingAs($this->admin)
        ->post('/cms/media', ['file' => UploadedFile::fake()->image('photo.jpg', 800, 600)])
        ->assertRedirect();

    $media = Media::sole();

    expect($media->kind)->toBe('image')
        ->and($media->needs_alt_review)->toBeTrue();
});

it('stores an uploaded mp4 as a video and never queues it for alt review', function () {
    $this->actingAs($this->admin)
        ->post('/cms/media', ['file' => UploadedFile::fake()->create('clip.mp4', 2048, 'video/mp4')])
        ->assertRedirect();

    $media = Media::sole();

    expect($media->kind)->toBe('video')
        ->and($media->mime)->toBe('video/mp4')
        ->and($media->needs_alt_review)->toBeFalse()
        // No raster derivatives are generated for video.
        ->and($media->variants)->toBe([]);
});

it('rejects an SVG upload (script-carrying markup served from /storage)', function () {
    $this->actingAs($this->admin)
        ->post('/cms/media', ['file' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml')])
        ->assertSessionHasErrors('file');

    expect(Media::count())->toBe(0);
});

it('rejects a disguised executable by its detected MIME', function () {
    $this->actingAs($this->admin)
        ->post('/cms/media', ['file' => UploadedFile::fake()->create('evil.mp4', 4, 'application/x-php')])
        ->assertSessionHasErrors('file');

    expect(Media::count())->toBe(0);
});

it('rejects an image over the image size limit even under the video cap', function () {
    // 9 MB: under the 100 MB video cap, over the 8 MB image sub-limit.
    $this->actingAs($this->admin)
        ->post('/cms/media', ['file' => UploadedFile::fake()->image('huge.jpg')->size(9216)])
        ->assertSessionHas('error');

    expect(Media::count())->toBe(0);
});

it('forbids uploads from a guest', function () {
    $this->post('/cms/media', ['file' => UploadedFile::fake()->image('photo.jpg')])
        ->assertRedirect('/login');

    expect(Media::count())->toBe(0);
});
