<?php

declare(strict_types=1);

use App\Models\User;

it('bars an editor from settings and users', function (string $uri) {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_EDITOR]))
        ->get($uri)
        ->assertForbidden();
})->with([
    'settings' => ['/cms/settings'],
    'users' => ['/cms/users'],
]);

it('allows an admin into settings and users', function (string $uri, string $component) {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
        ->get($uri)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    'settings' => ['/cms/settings', 'cms/settings/edit'],
    'users' => ['/cms/users', 'cms/users/index'],
]);

it('lets both roles manage content', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get('/cms/pages')->assertOk();
    $this->actingAs($user)->get('/cms/media')->assertOk();
    $this->actingAs($user)->get('/cms/menus')->assertOk();
})->with([
    'admin' => [User::ROLE_ADMIN],
    'editor' => [User::ROLE_EDITOR],
]);

it('will not remove the last administrator', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->put("/cms/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_EDITOR,
        ])
        ->assertSessionHas('error');

    expect($admin->fresh()->role)->toBe(User::ROLE_ADMIN);
});
