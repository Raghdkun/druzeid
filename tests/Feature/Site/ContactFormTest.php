<?php

declare(strict_types=1);

use App\Mail\ContactMessage;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Setting::put('contact.email', 'inbox@druze.id', 'contact');
    Mail::fake();
});

it('accepts a valid submission and mails the organization', function () {
    $this->post('/contact', [
        'name' => 'زائر',
        'email' => 'visitor@example.org',
        'subject' => 'استفسار',
        'message' => 'رسالة اختبار كافية الطول للتحقق',
        'rendered_at' => now()->timestamp - 30,
    ])->assertRedirect()->assertSessionHas('success');

    Mail::assertSent(ContactMessage::class, fn ($mail) => $mail->hasTo('inbox@druze.id'));
});

it('rejects a submission with missing required fields', function () {
    $this->post('/contact', [
        'name' => '',
        'email' => 'not-an-email',
        'message' => 'short',
    ])->assertSessionHasErrors(['name', 'email', 'message']);

    Mail::assertNothingSent();
});

it('silently rejects a bot that fills the honeypot', function () {
    $this->post('/contact', [
        'name' => 'Bot',
        'email' => 'bot@example.org',
        'message' => 'A sufficiently long message body here',
        'website' => 'http://spam.example',   // honeypot
        'rendered_at' => now()->timestamp - 30,
    ])->assertSessionHasErrors('website');

    Mail::assertNothingSent();
});

it('rejects a submission completed implausibly fast', function () {
    $this->post('/contact', [
        'name' => 'Speedy',
        'email' => 'fast@example.org',
        'message' => 'A sufficiently long message body here',
        'rendered_at' => now()->timestamp,   // 0 seconds elapsed
    ])->assertSessionHasErrors('message');

    Mail::assertNothingSent();
});

it('is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/contact', [
            'name' => "User {$i}",
            'email' => "user{$i}@example.org",
            'message' => 'A sufficiently long message body here',
            'rendered_at' => now()->timestamp - 30,
        ]);
    }

    $this->post('/contact', [
        'name' => 'Over Limit',
        'email' => 'over@example.org',
        'message' => 'A sufficiently long message body here',
        'rendered_at' => now()->timestamp - 30,
    ])->assertStatus(429);
});

it('posts to the locale-prefixed endpoint', function () {
    $this->post('/en/contact', [
        'name' => 'Visitor',
        'email' => 'visitor@example.org',
        'message' => 'A sufficiently long message body here',
        'rendered_at' => now()->timestamp - 30,
    ])->assertRedirect();

    Mail::assertSent(ContactMessage::class);
});
