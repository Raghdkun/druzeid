<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;

it('lets an admin edit site settings including social links', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)->put('/cms/settings', [
        'siteShortName' => ['ar' => 'الهوية', 'en' => 'Identity'],
        'siteTagline' => ['ar' => 'شعار', 'en' => 'Tagline'],
        'footerNote' => ['ar' => 'تذييل'],
        'contactEmail' => 'hello@druze.id',
        'contactPhones' => [['label' => ['ar' => 'موبايل'], 'value' => '00000000']],
        'contactAddress' => 'Address',
        'socialLinks' => [
            ['platform' => 'facebook', 'url' => 'https://facebook.com/druze'],
            ['platform' => 'instagram', 'url' => 'https://instagram.com/druze'],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    expect(Setting::localized('site.short_name', 'en'))->toBe('Identity')
        ->and(Setting::get('contact.email'))->toBe('hello@druze.id')
        ->and(Setting::get('social.links'))->toHaveCount(2)
        ->and(Setting::get('social.links')[0]['platform'])->toBe('facebook');
});

it('validates social link urls', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)->put('/cms/settings', [
        'siteShortName' => [],
        'siteTagline' => [],
        'footerNote' => [],
        'socialLinks' => [['platform' => 'x', 'url' => 'not-a-url']],
    ])->assertSessionHasErrors('socialLinks.0.url');
});

it('exposes social links to the public site chrome', function () {
    Setting::put('social.links', [['platform' => 'facebook', 'url' => 'https://facebook.com/x']], 'social');
    Page::factory()->home()->create();

    // The setting keys contain dots (social.links), which the fluent
    // assertion path can't address, so inspect the shared props directly.
    $response = $this->get('/');
    $props = $response->viewData('page')['props'];

    expect($props['settings']['social.links'][0]['platform'])->toBe('facebook');
});
