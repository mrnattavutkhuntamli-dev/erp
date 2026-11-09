<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'current_password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('changing the email requires the current password', function () {
    $user = User::factory()->create();
    $oldEmail = $user->email;

    $this->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => 'new-address@example.com',
        ])
        ->assertSessionHasErrors('current_password');

    $this->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => 'new-address@example.com',
            'current_password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('current_password');

    $this->assertSame($oldEmail, $user->refresh()->email);
});
