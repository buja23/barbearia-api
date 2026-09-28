<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Register
    // -------------------------------------------------------------------------

    #[Test]
    public function user_can_register_and_receives_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'João Silva',
            'email'                 => 'joao@example.com',
            'password'              => 'Senha123456',
            'password_confirmation' => 'Senha123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'user']);

        $this->assertDatabaseHas('users', ['email' => 'joao@example.com', 'role' => 'client']);
    }

    #[Test]
    public function register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->postJson('/api/register', [
            'name'                  => 'Outro',
            'email'                 => 'existing@example.com',
            'password'              => 'Senha123456',
            'password_confirmation' => 'Senha123456',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function register_fails_with_weak_password(): void
    {
        $this->postJson('/api/register', [
            'name'                  => 'João',
            'email'                 => 'joao@example.com',
            'password'              => 'semNumero',
            'password_confirmation' => 'semNumero',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function register_fails_with_password_below_minimum_length(): void
    {
        $this->postJson('/api/register', [
            'name'                  => 'João',
            'email'                 => 'joao@example.com',
            'password'              => 'Short1',
            'password_confirmation' => 'Short1',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    #[Test]
    public function user_can_login_and_receives_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Senha123456')]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'Senha123456',
        ])->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'user']);
    }

    #[Test]
    public function login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Senha123456')]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'WrongPassword',
        ])->assertStatus(422);
    }

    #[Test]
    public function login_fails_with_unknown_email(): void
    {
        $this->postJson('/api/login', [
            'email'    => 'nobody@example.com',
            'password' => 'Senha123456',
        ])->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    #[Test]
    public function authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJson(['message' => 'Logout realizado com sucesso']);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_protected_route(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Update profile
    // -------------------------------------------------------------------------

    #[Test]
    public function authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user)
            ->putJson('/api/user', [
                'name'  => 'Novo Nome',
                'email' => 'novo@example.com',
            ])->assertStatus(200)
                ->assertJsonPath('user.name', 'Novo Nome');

        $this->assertDatabaseHas('users', ['email' => 'novo@example.com', 'name' => 'Novo Nome']);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function profile_update_persists_hashed_password_and_preserves_unchanged_email_verification(): void
    {
        $user = User::factory()->create();
        $verifiedAt = $user->email_verified_at;
        $token = $user->createToken('profile-test')->plainTextToken;

        $this->withToken($token)->putJson('/api/user', [
            'name' => 'Nome atualizado', 'email' => $user->email,
            'current_password' => 'password', 'password' => 'NovaSenha12345',
            'password_confirmation' => 'NovaSenha12345',
        ])->assertOk()->assertJsonStructure(['message', 'user'])->assertJsonMissingPath('user.password');

        $user->refresh();
        $this->assertTrue(Hash::check('NovaSenha12345', $user->password));
        $this->assertNotSame('NovaSenha12345', $user->password);
        $this->assertTrue($verifiedAt->equalTo($user->email_verified_at));
    }

    #[Test]
    public function wrong_current_password_does_not_change_profile_or_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson('/api/user', [
            'name' => 'Nome bloqueado', 'email' => $user->email,
            'current_password' => 'incorrect', 'password' => 'NovaSenha12345',
            'password_confirmation' => 'NovaSenha12345',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
