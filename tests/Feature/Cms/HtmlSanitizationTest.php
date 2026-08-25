<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\User;
use App\Support\Html;

it('sanitises rich-text HTML in a section payload on save', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)->post('/cms/pages', [
        'slug' => 'sanitize-me',
        'template' => 'default',
        'status' => 'published',
        'title' => ['ar' => 'اختبار'],
        'sections' => [
            [
                'type' => 'rich_text',
                'payload' => [
                    'html' => [
                        'ar' => '<p onclick="steal()">مرحبا</p><script>alert(1)</script><a href="javascript:evil()">x</a>',
                        'en' => '<p><strong>Hello</strong></p>',
                    ],
                ],
                'isVisible' => true,
            ],
        ],
    ])->assertRedirect();

    $section = Page::where('slug', 'sanitize-me')->firstOrFail()->sections->first();
    $ar = $section->payload['html']['ar'];

    expect($ar)->toContain('مرحبا')
        ->and($ar)->not->toContain('<script')
        ->and($ar)->not->toContain('onclick')
        ->and($ar)->not->toContain('javascript:')
        ->and($section->payload['html']['en'])->toContain('<strong>Hello</strong>');
});

it('sanitises html nested inside accordion items', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)->post('/cms/pages', [
        'slug' => 'nested-html',
        'template' => 'default',
        'status' => 'draft',
        'title' => ['ar' => 'ع'],
        'sections' => [
            [
                'type' => 'accordion',
                'payload' => [
                    'heading' => ['ar' => 'عنوان'],
                    'items' => [
                        ['title' => ['ar' => 'بند'], 'html' => ['ar' => '<p>نص<img src=x onerror=alert(1)></p>']],
                    ],
                ],
                'isVisible' => true,
            ],
        ],
    ])->assertRedirect();

    $payload = Page::where('slug', 'nested-html')->firstOrFail()->sections->first()->payload;

    // Images are allowed (editor-inserted media), but the dangerous event
    // handler is still stripped by the nested sanitiser.
    expect($payload['items'][0]['html']['ar'])
        ->toContain('نص')
        ->toContain('<img')
        ->not->toContain('onerror');
});

it('allows images but strips non-colour inline CSS in rich text', function () {
    $clean = Html::clean('<p><img src="/storage/media/x.jpg" alt="a" onclick="evil()"><span style="color:#069ed9;background:url(x);width:9px">t</span></p>');

    expect($clean)->toContain('<img')
        ->toContain('src="/storage/media/x.jpg"')
        ->not->toContain('onclick')
        ->toContain('color:#069ed9')
        ->not->toContain('url(')
        ->not->toContain('width');
});

it('keeps every tag the editor can produce', function () {
    $rich = '<h2>ع</h2><h3>ع</h3><p><strong>ب</strong> <em>ج</em> <u>د</u> <s>ه</s></p>'
        .'<ul><li>و</li></ul><ol><li>ز</li></ol><blockquote>ح</blockquote>'
        .'<a href="https://druze.id" title="t">ط</a>';

    $clean = Html::clean($rich);

    foreach (['<h2>', '<h3>', '<strong>', '<em>', '<u>', '<s>', '<ul>', '<ol>', '<li>', '<blockquote>', 'href="https://druze.id"'] as $needle) {
        expect($clean)->toContain($needle);
    }
});

it('produces a plain-text preview from html', function () {
    expect(Html::toText('<p>Hello <strong>bold</strong> world</p>'))->toBe('Hello bold world');
});
