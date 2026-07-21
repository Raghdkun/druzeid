<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests from the dashboard to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('sends an authenticated user from /dashboard to the CMS overview', function () {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_EDITOR]));

    $this->get(route('dashboard'))->assertRedirect('/cms');
    $this->get('/cms')->assertOk()
        ->assertInertia(fn ($page) => $page->component('cms/overview'));
});
