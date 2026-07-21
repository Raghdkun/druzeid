<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\User;
use App\Services\ImageVariants;
use Illuminate\Support\Facades\Storage;

it('builds a srcset string from the variant map', function () {
    $media = Media::factory()->create([
        'disk' => 'public',
        'variants' => [
            'webp' => [480 => 'media/x-480.webp', 960 => 'media/x-960.webp'],
        ],
    ]);

    expect($media->srcset('webp'))
        ->toContain('/storage/media/x-480.webp 480w')
        ->toContain('/storage/media/x-960.webp 960w')
        ->and($media->srcset('avif'))->toBeNull();
});

it('exposes responsive sources in image props', function () {
    $media = Media::factory()->create([
        'variants' => ['webp' => [768 => 'media/y-768.webp']],
    ]);

    $props = $media->toImageProps();

    expect($props['srcsetWebp'])->toContain('y-768.webp 768w')
        ->and($props['srcsetAvif'])->toBeNull();
});

it('generates downscaled webp derivatives for a real image', function () {
    Storage::fake('public');

    // A 1000x600 PNG source.
    $gd = imagecreatetruecolor(1000, 600);
    ob_start();
    imagepng($gd);
    Storage::disk('public')->put('media/sample.png', (string) ob_get_clean());
    imagedestroy($gd);

    $variants = app(ImageVariants::class)->generate('media/sample.png');

    // WebP at widths up to the source width (480, 768) — 1200/1920 skipped.
    expect($variants)->toHaveKey('webp')
        ->and(array_keys($variants['webp']))->toContain(480)
        ->and(array_keys($variants['webp']))->toContain(768)
        ->and(array_keys($variants['webp']))->not->toContain(1920);

    foreach ($variants['webp'] as $path) {
        expect(Storage::disk('public')->exists($path))->toBeTrue();
    }
});

it('skips vector sources', function () {
    Storage::fake('public');
    Storage::disk('public')->put('media/logo.svg', '<svg></svg>');

    expect(app(ImageVariants::class)->generate('media/logo.svg'))->toBe([]);
});

it('deletes derivatives when a media item is removed', function () {
    Storage::fake('public');
    Storage::disk('public')->put('media/z.jpg', 'x');
    Storage::disk('public')->put('media/z-480.webp', 'x');

    $media = Media::factory()->create([
        'path' => 'media/z.jpg',
        'variants' => ['webp' => [480 => 'media/z-480.webp']],
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->delete("/cms/media/{$media->id}")->assertRedirect();

    expect(Storage::disk('public')->exists('media/z-480.webp'))->toBeFalse()
        ->and(Media::find($media->id))->toBeNull();
});
