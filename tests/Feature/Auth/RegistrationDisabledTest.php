<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Support\Facades\Route;

it('does not expose a public registration route', function () {
    // Fortify's registration feature is disabled: the CMS is staff-only.
    expect(Route::has('register'))->toBeFalse();

    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Intruder',
        'email' => 'intruder@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertNotFound();

    expect(User::where('email', 'intruder@example.com')->exists())->toBeFalse();
});
