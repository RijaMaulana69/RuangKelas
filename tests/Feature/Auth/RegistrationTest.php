<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'pass12')
            ->set('password_confirmation', 'pass12')
            ->set('role', 'siswa');

        $component->call('register');

        $component->assertHasNoErrors();
        $component->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_registration_fails_if_password_shorter_than_6_chars(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'short@example.com')
            ->set('password', '12345')
            ->set('password_confirmation', '12345')
            ->set('role', 'siswa');

        $component->call('register');

        $component->assertHasErrors(['password' => 'min']);
        $this->assertGuest();
    }

    public function test_registration_fails_if_password_longer_than_8_chars(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'long@example.com')
            ->set('password', '123456789')
            ->set('password_confirmation', '123456789')
            ->set('role', 'siswa');

        $component->call('register');

        $component->assertHasErrors(['password' => 'max']);
        $this->assertGuest();
    }

    public function test_user_can_register_then_login_successfully(): void
    {
        // 1. Register akun siswa baru
        $register = Volt::test('pages.auth.register')
            ->set('name', 'Budi Santoso')
            ->set('email', 'budi@example.com')
            ->set('password', '123456')
            ->set('password_confirmation', '123456')
            ->set('role', 'siswa');

        $register->call('register');
        $register->assertHasNoErrors();
        $register->assertRedirect(route('login', absolute: false));
        $this->assertGuest();

        // 2. Login dengan akun yang baru didaftarkan
        $login = Volt::test('pages.auth.login')
            ->set('form.email', 'budi@example.com')
            ->set('form.password', '123456');

        $login->call('login');
        $login->assertHasNoErrors();
        $login->assertRedirect(route('siswa.dashboard', absolute: false));
        $this->assertAuthenticated();
    }
}
