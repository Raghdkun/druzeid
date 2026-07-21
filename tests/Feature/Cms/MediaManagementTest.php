<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\User;

beforeEach(function () {
    $this->editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
});

it('clears the alt-review flag once Arabic alt text is provided', function () {
    $media = Media::factory()->needsAltReview()->create();

    expect($media->needs_alt_review)->toBeTrue();

    $this->actingAs($this->editor)
        ->put("/cms/media/{$media->id}", [
            'alt' => ['ar' => 'وصف الصورة', 'en' => 'Image description'],
            'isDecorative' => false,
        ])
        ->assertRedirect();

    $media->refresh();
    expect($media->needs_alt_review)->toBeFalse()
        ->and($media->getTranslation('alt', 'ar'))->toBe('وصف الصورة');
});

it('keeps an image in the review queue when Arabic alt is still missing', function () {
    $media = Media::factory()->needsAltReview()->create();

    $this->actingAs($this->editor)
        ->put("/cms/media/{$media->id}", [
            'alt' => ['en' => 'English only'],
            'isDecorative' => false,
        ])
        ->assertRedirect();

    expect($media->fresh()->needs_alt_review)->toBeTrue();
});

it('clears the review flag when an image is marked decorative', function () {
    $media = Media::factory()->needsAltReview()->create();

    $this->actingAs($this->editor)
        ->put("/cms/media/{$media->id}", ['isDecorative' => true])
        ->assertRedirect();

    $media->refresh();
    expect($media->needs_alt_review)->toBeFalse()
        ->and($media->is_decorative)->toBeTrue();
});

it('filters the library to the review queue', function () {
    Media::factory()->count(3)->create();
    Media::factory()->needsAltReview()->count(2)->create();

    $this->actingAs($this->editor)
        ->get('/cms/media?filter=review')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('cms/media/index')
            ->where('reviewCount', 2)
            ->has('media.data', 2));
});
