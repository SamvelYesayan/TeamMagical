<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** RequestGuard caches the user per app instance; tests send several "devices" in one process. */
    private function bearer(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    public function test_mobile_login_returns_token_that_authenticates_api_calls(): void
    {
        $this->makeUser('hayko');

        $res = $this->postJson('/api/mobile/login', ['username' => 'hayko', 'password' => 'Password12345', 'device_name' => 'pixel'])
            ->assertOk()
            ->assertJsonPath('user.username', 'hayko');

        $token = $res->json('token');
        $this->assertNotEmpty($token);

        $this->bearer($token)->getJson('/api/me')->assertOk()->assertJsonPath('user.username', 'hayko');
        $this->bearer($token)->getJson('/api/deadlines')->assertOk();
        $this->bearer($token)->getJson('/api/users')->assertForbidden();
    }

    public function test_wrong_password_and_disabled_users_get_no_token(): void
    {
        $this->makeUser('hayko');
        $this->makeUser('blocked', Role::EMPLOYEE, User::DISABLED);

        $this->postJson('/api/mobile/login', ['username' => 'hayko', 'password' => 'nope'])->assertStatus(422)->assertJsonMissingPath('token');
        $this->postJson('/api/mobile/login', ['username' => 'blocked', 'password' => 'Password12345'])->assertStatus(422)->assertJsonMissingPath('token');
    }

    public function test_requests_without_token_are_rejected(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
        $this->bearer('invalid-token')->getJson('/api/me')->assertStatus(401);
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = $this->makeUser('hayko');
        $token = $this->postJson('/api/mobile/login', ['username' => 'hayko', 'password' => 'Password12345'])->json('token');

        $this->bearer($token)->postJson('/api/logout')->assertOk();
        $this->assertSame(0, $user->tokens()->count());
        $this->bearer($token)->getJson('/api/me')->assertStatus(401);
    }

    public function test_disabling_a_user_revokes_tokens_and_blocks_access(): void
    {
        $admin = $this->makeUser('boss', Role::ADMIN);
        $user = $this->makeUser('hayko');
        $token = $this->postJson('/api/mobile/login', ['username' => 'hayko', 'password' => 'Password12345'])->json('token');

        $this->actingAs($admin)->postJson("/api/users/{$user->id}/disable")->assertOk();
        $this->assertSame(0, $user->tokens()->count());
        $this->bearer($token)->getJson('/api/me')->assertStatus(401);
    }

    public function test_password_change_works_with_token_and_keeps_current_device(): void
    {
        $user = $this->makeUser('hayko');
        $a = $this->postJson('/api/mobile/login', ['username' => 'hayko', 'password' => 'Password12345', 'device_name' => 'a'])->json('token');
        $b = $this->postJson('/api/mobile/login', ['username' => 'hayko', 'password' => 'Password12345', 'device_name' => 'b'])->json('token');

        $this->bearer($a)->putJson('/api/me/password', [
            'current_password' => 'wrong', 'password' => 'BrandNewPass99', 'password_confirmation' => 'BrandNewPass99',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->bearer($a)->putJson('/api/me/password', [
            'current_password' => 'Password12345', 'password' => 'BrandNewPass99', 'password_confirmation' => 'BrandNewPass99',
        ])->assertOk();

        $this->bearer($a)->getJson('/api/me')->assertOk();
        $this->bearer($b)->getJson('/api/me')->assertStatus(401);
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_mobile_token_can_use_the_deadline_workflow(): void
    {
        $admin = $this->makeUser('boss', Role::ADMIN);
        $hayko = $this->makeUser('hayko');
        $adminToken = $this->postJson('/api/mobile/login', ['username' => 'boss', 'password' => 'Password12345'])->json('token');
        $haykoToken = $this->postJson('/api/mobile/login', ['username' => 'hayko', 'password' => 'Password12345'])->json('token');

        $id = $this->bearer($adminToken)->postJson('/api/deadlines', [
            'title' => 'Mobile task', 'assigned_to' => $hayko->id, 'due_at' => now()->addDays(3)->toISOString(),
        ])->assertCreated()->json('data.id');

        $this->bearer($haykoToken)->getJson("/api/deadlines/{$id}")->assertJsonPath('data.can.confirm', true);
        $this->bearer($haykoToken)->postJson("/api/deadlines/{$id}/confirm")->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->bearer($haykoToken)->postJson("/api/deadlines/{$id}/submissions", ['text' => 'done'])->assertOk();
        $this->bearer($adminToken)->getJson("/api/deadlines/{$id}")->assertJsonPath('data.can.review', true);
        unset($admin);
    }
}
