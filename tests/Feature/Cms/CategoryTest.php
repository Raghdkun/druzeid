<?php

declare(strict_types=1);

use App\Models\ArticleCategory;
use App\Models\Media;
use App\Models\User;

it('lets an admin create a category with an image', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $media = Media::factory()->create();

    $this->actingAs($admin)->post('/cms/categories', [
        'slug' => 'heritage',
        'name' => ['ar' => 'تراث', 'en' => 'Heritage'],
        'description' => ['ar' => 'وصف'],
        'accent' => 'green',
        'sort' => 2,
        'imageMediaId' => $media->id,
    ])->assertRedirect();

    $category = ArticleCategory::where('slug', 'heritage')->firstOrFail();
    expect($category->getTranslation('name', 'en'))->toBe('Heritage')
        ->and($category->accent)->toBe('green')
        ->and($category->image_media_id)->toBe($media->id)
        ->and($category->featuredMedia?->toImageProps())->not->toBeNull();
});

it('allows a category without an image (nullable)', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/cms/categories', [
        'slug' => 'no-image',
        'name' => ['ar' => 'بدون صورة'],
    ])->assertRedirect();

    expect(ArticleCategory::where('slug', 'no-image')->firstOrFail()->image_media_id)->toBeNull();
});

it('updates a category image', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = ArticleCategory::factory()->create();
    $media = Media::factory()->create();

    $this->actingAs($admin)->put("/cms/categories/{$category->id}", [
        'slug' => $category->slug,
        'name' => $category->getTranslations('name'),
        'imageMediaId' => $media->id,
    ])->assertRedirect();

    expect($category->fresh()->image_media_id)->toBe($media->id);
});

it('rejects a duplicate category slug', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    ArticleCategory::factory()->create(['slug' => 'taken']);

    $this->actingAs($admin)->post('/cms/categories', [
        'slug' => 'taken',
        'name' => ['ar' => 'مكرر'],
    ])->assertSessionHasErrors('slug');
});

it('blocks guests from managing categories', function () {
    $this->get('/cms/categories')->assertRedirect('/login');
    $this->post('/cms/categories', ['slug' => 'x', 'name' => ['ar' => 'x']])->assertRedirect('/login');
});

it('shows the category list to an admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    ArticleCategory::factory()->create(['name' => ['ar' => 'تصنيف']]);

    $this->actingAs($admin)->get('/cms/categories')->assertOk()->assertInertia(fn ($p) => $p
        ->component('cms/categories/index')
        ->has('categories', 1)
    );
});
