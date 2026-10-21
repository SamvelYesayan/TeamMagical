<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private array $spa = ['Origin' => 'http://localhost:5173', 'Referer' => 'http://localhost:5173/login'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_user_can_login_and_is_remembered_by_session(): void
    {
        $this->makeUser('hayko');

        $this->withHeaders($this->spa)
            ->postJson('/api/login', ['username' => 'hayko', 'password' => 'Password12345'])
            ->assertOk()
            ->assertJsonPath('user.username', 'hayko')
            ->assertJsonPath('user.role', 'employee');

        $this->withHeaders($this->spa)->getJson('/api/me')->assertOk()->assertJsonPath('user.username', 'hayko');
        $this->assertTrue(ActivityLog::query()->where('action', 'auth.login')->exists());
    }

    public function test_wrong_password_is_rejected_and_logged(): void
    {
        $this->makeUser('hayko');

        $this->withHeaders($this->spa)
            ->postJson('/api/login', ['username' => 'hayko', 'password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');

        $this->assertTrue(ActivityLog::query()->where('action', 'auth.login_failed')->exists());
    }

    public function test_disabled_user_cannot_login(): void
    {
        $this->makeUser('blocked', Role::EMPLOYEE, User::DISABLED);

        $this->withHeaders($this->spa)
            ->postJson('/api/login', ['username' => 'blocked', 'password' => 'Password12345'])
            ->assertStatus(422);
    }

    public function test_login_is_rate_limited(): void
    {
        $this->makeUser('hayko');

        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders($this->spa)->postJson('/api/login', ['username' => 'hayko', 'password' => 'bad'.$i]);
        }

        $this->withHeaders($this->spa)
            ->postJson('/api/login', ['username' => 'hayko', 'password' => 'Password12345'])
            ->assertStatus(429);
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/deadlines')->assertStatus(401);
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    public function test_employee_cannot_use_admin_routes(): void
    {
        $employee = $this->makeUser('hayko');

        $this->actingAs($employee)->getJson('/api/users')->assertForbidden();
        $this->actingAs($employee)->getJson('/api/activity-logs')->assertForbidden();
        $this->actingAs($employee)->postJson('/api/projects', ['name' => 'X'])->assertForbidden();
        $this->actingAs($employee)->postJson('/api/deadlines', [])->assertForbidden();
    }

    public function test_disabled_account_loses_access_immediately(): void
    {
        $user = $this->makeUser('hayko');
        $this->actingAs($user)->getJson('/api/me')->assertOk();

        $user->update(['status' => User::DISABLED]);

        $this->actingAs($user->fresh())->getJson('/api/me')->assertStatus(401);
    }

    public function test_temporary_password_must_be_changed_before_anything_else(): void
    {
        $user = $this->makeUser('hayko');
        $user->update(['must_change_password' => true]);

        $this->actingAs($user)->getJson('/api/deadlines')->assertForbidden()->assertJsonPath('code', 'password_change_required');
        $this->actingAs($user)->getJson('/api/me')->assertOk();

        $this->actingAs($user)
            ->putJson('/api/me/password', [
                'current_password' => 'Password12345',
                'password' => 'BrandNewPass99',
                'password_confirmation' => 'BrandNewPass99',
            ])
            ->assertOk();

        $this->assertFalse($user->fresh()->must_change_password);
        $this->actingAs($user->fresh())->getJson('/api/deadlines')->assertOk();
    }

    public function test_admin_can_manage_users_and_last_admin_is_protected(): void
    {
        $admin = $this->makeUser('boss', Role::ADMIN);

        $this->actingAs($admin)
            ->postJson('/api/users', [
                'name' => 'Hayko', 'username' => 'hayko', 'password' => 'Password12345', 'role' => 'employee', 'position' => 'Developer',
            ])
            ->assertCreated()
            ->assertJsonPath('data.username', 'hayko');

        $this->assertTrue(ActivityLog::query()->where('action', 'user.created')->exists());

        // The only active administrator cannot be disabled, demoted or deleted.
        $this->actingAs($admin)->postJson("/api/users/{$admin->id}/disable")->assertStatus(409);
        $this->actingAs($admin)->deleteJson("/api/users/{$admin->id}")->assertStatus(409);

        $hayko = User::query()->where('username', 'hayko')->firstOrFail();
        $this->actingAs($admin)->postJson("/api/users/{$hayko->id}/disable")->assertOk();
        $this->assertEquals(User::DISABLED, $hayko->fresh()->status);
        $this->assertTrue(ActivityLog::query()->where('action', 'user.disabled')->exists());

        $this->actingAs($admin)
            ->postJson("/api/users/{$hayko->id}/reset-password", ['password' => 'AnotherPass123'])
            ->assertOk();
        $this->assertTrue($hayko->fresh()->must_change_password);
    }
}
