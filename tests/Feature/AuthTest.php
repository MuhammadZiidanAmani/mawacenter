<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Selamat Datang');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'rahasia123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->get('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->post('/login', [
            'username' => 'petugas',
            'password' => 'keliru',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_email_cannot_be_used_to_login(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/login', [
            'username' => $user->email,
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }
}
