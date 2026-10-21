<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AppNotification;
use App\Models\Deadline;
use App\Models\DeadlineChangeRequest;
use App\Models\Role;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Executable version of the 21-step manual checklist (see docs/MANUAL_TEST_CHECKLIST.md).
 * NOTE: written without the ability to execute it in the authoring environment; run `php artisan test`.
 */
class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $hayko;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->seed(RoleSeeder::class);

        $this->admin = $this->makeUser('sam_yesayan', Role::ADMIN);
        $this->hayko = $this->makeUser('hayko', Role::EMPLOYEE);
    }

    private function makeUser(string $username, string $role): User
    {
        return User::query()->create([
            'name' => ucfirst($username),
            'username' => $username,
            'password' => 'Secret12345',
            'role_id' => Role::query()->where('slug', $role)->value('id'),
            'status' => User::ACTIVE,
        ]);
    }

    /** Switch the authenticated user (guards cache the previous user between requests). */
    private function as(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user);
    }

    private function createDeadline(array $extra = []): int
    {
        $response = $this->as($this->admin)->postJson('/api/deadlines', array_merge([
            'title' => 'Finish Magical website',
            'assigned_to' => $this->hayko->id,
            'due_at' => now()->addDays(3)->toISOString(),
            'priority' => 'high',
        ], $extra));

        $response->assertCreated();

        return $response->json('data.id');
    }

    private function submitWork(int $deadlineId, string $text = 'Done'): void
    {
        $this->as($this->hayko)->post("/api/deadlines/{$deadlineId}/submissions", [
            'text' => $text,
            'files' => [UploadedFile::fake()->create('site.zip', 200, 'application/zip')],
        ], ['Accept' => 'application/json'])->assertOk();
    }

    public function test_complete_deadline_workflow(): void
    {
        $id = $this->createDeadline();

        // Steps 5-7: assigned, visible to the employee, status "new" with a notification.
        $this->assertDatabaseHas('deadlines', ['id' => $id, 'status' => 'new']);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->hayko->id, 'type' => 'deadline.assigned']);
        $this->as($this->hayko)->getJson("/api/deadlines/{$id}")
            ->assertOk()->assertJsonPath('data.can.confirm', true)->assertJsonPath('data.can.review', false);

        // Step 9-11: employee proposes another date, admin accepts.
        $proposed = now()->addDays(6);
        $this->as($this->hayko)->postJson("/api/deadlines/{$id}/change-requests", [
            'proposed_due_at' => $proposed->toISOString(),
            'reason' => 'Need more time',
        ])->assertOk();
        $this->assertDatabaseHas('deadlines', ['id' => $id, 'status' => 'pending_confirmation']);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->admin->id, 'type' => 'deadline.change_requested']);

        $request = DeadlineChangeRequest::query()->firstOrFail();
        $this->as($this->admin)->postJson("/api/change-requests/{$request->id}/decision", ['decision' => 'accept', 'comment' => 'OK'])->assertOk();

        $deadline = Deadline::query()->findOrFail($id);
        $this->assertSame('confirmed', $deadline->status);
        $this->assertEqualsWithDelta($proposed->timestamp, $deadline->due_at->timestamp, 2);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->hayko->id, 'type' => 'deadline.change_accepted']);

        // Steps 12-14: submit text + file.
        $this->submitWork($id);
        $this->assertDatabaseHas('deadlines', ['id' => $id, 'status' => 'submitted']);
        $this->assertDatabaseHas('submissions', ['deadline_id' => $id, 'number' => 1, 'status' => 'pending']);

        // Step 15: "incomplete" REQUIRES an explanation.
        $first = Submission::query()->where('number', 1)->firstOrFail();
        $this->as($this->admin)->postJson("/api/submissions/{$first->id}/review", ['decision' => 'needs_revision'])
            ->assertStatus(422)->assertJsonValidationErrors('feedback');

        $this->as($this->admin)->postJson("/api/submissions/{$first->id}/review", [
            'decision' => 'needs_revision',
            'feedback' => 'Mobile responsive version is not finished.',
        ])->assertOk();

        // Step 16: employee sees the feedback.
        $this->as($this->hayko)->getJson("/api/deadlines/{$id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'needs_revision')
            ->assertJsonPath('data.submissions.0.feedback', 'Mobile responsive version is not finished.');
        $this->assertDatabaseHas('notifications', ['user_id' => $this->hayko->id, 'type' => 'submission.needs_revision']);

        // Step 17-19: resubmit, history is kept (never overwritten), admin approves.
        $this->submitWork($id, 'Fixed mobile');
        $this->assertDatabaseCount('submissions', 2);
        $second = Submission::query()->where('number', 2)->firstOrFail();
        $this->as($this->admin)->postJson("/api/submissions/{$second->id}/review", ['decision' => 'approve'])->assertOk();

        $this->assertDatabaseHas('deadlines', ['id' => $id, 'status' => 'completed']);
        $this->assertDatabaseHas('submissions', ['id' => $first->id, 'status' => 'needs_revision']);
        $this->assertDatabaseHas('submissions', ['id' => $second->id, 'status' => 'approved']);

        // Step 20: audit trail.
        foreach (['deadline.created', 'deadline.assigned', 'deadline.change_requested', 'deadline.change_accepted', 'submission.created', 'submission.needs_revision', 'submission.approved'] as $action) {
            $this->assertTrue(ActivityLog::query()->where('action', $action)->exists(), "Missing audit entry: {$action}");
        }

        // Step 21: notifications API.
        $this->as($this->hayko)->getJson('/api/notifications/unread-count')->assertOk();
        $this->assertGreaterThan(0, AppNotification::query()->where('user_id', $this->hayko->id)->count());
    }

    public function test_employee_can_confirm_and_state_changes_are_enforced(): void
    {
        $id = $this->createDeadline();

        // Cannot submit before confirming.
        $this->as($this->hayko)->post("/api/deadlines/{$id}/submissions", ['text' => 'x'], ['Accept' => 'application/json'])->assertForbidden();

        $this->as($this->hayko)->postJson("/api/deadlines/{$id}/confirm")->assertOk();
        $this->assertDatabaseHas('deadlines', ['id' => $id, 'status' => 'confirmed']);

        // Cannot confirm twice.
        $this->as($this->hayko)->postJson("/api/deadlines/{$id}/confirm")->assertForbidden();

        $this->as($this->hayko)->postJson("/api/deadlines/{$id}/start")->assertOk();
        $this->assertDatabaseHas('deadlines', ['id' => $id, 'status' => 'in_progress']);
    }

    public function test_admin_can_assign_a_deadline_to_themselves(): void
    {
        $response = $this->as($this->admin)->postJson('/api/deadlines', [
            'title' => 'Prepare October financial plan',
            'assigned_to' => $this->admin->id,
            'due_at' => now()->addDays(2)->toISOString(),
        ])->assertCreated();

        $response->assertJsonPath('data.status', 'confirmed')->assertJsonPath('data.can.submit', true);
    }

    public function test_employees_cannot_use_admin_endpoints_or_edit_other_deadlines(): void
    {
        $id = $this->createDeadline();
        $other = $this->makeUser('anna', Role::EMPLOYEE);

        $this->as($this->hayko)->getJson('/api/users')->assertForbidden();
        $this->as($this->hayko)->getJson('/api/activity-logs')->assertForbidden();
        $this->as($this->hayko)->postJson('/api/deadlines', ['title' => 'x'])->assertForbidden();
        $this->as($this->hayko)->deleteJson("/api/deadlines/{$id}")->assertForbidden();

        // Team transparency: others can SEE it, but cannot act on it.
        $this->as($other)->getJson("/api/deadlines/{$id}")->assertOk()->assertJsonPath('data.can.confirm', false);
        $this->as($other)->postJson("/api/deadlines/{$id}/confirm")->assertForbidden();
    }

    public function test_submission_files_are_private(): void
    {
        $id = $this->createDeadline();
        $this->as($this->hayko)->postJson("/api/deadlines/{$id}/confirm")->assertOk();
        $this->submitWork($id);

        $file = SubmissionFile::query()->firstOrFail();
        $stranger = $this->makeUser('anna', Role::EMPLOYEE);

        $this->as($this->hayko)->get("/api/submission-files/{$file->id}/download")->assertOk();
        $this->as($this->admin)->get("/api/submission-files/{$file->id}/download")->assertOk();
        $this->as($stranger)->get("/api/submission-files/{$file->id}/download")->assertForbidden();

        // Submitted work is not even listed for other employees.
        $this->as($stranger)->getJson("/api/deadlines/{$id}")->assertOk()->assertJsonMissingPath('data.submissions');

        // The stored file is not web accessible and uses a random name.
        Storage::disk('private')->assertExists($file->path);
        $this->assertStringNotContainsString('site', basename($file->path));
    }

    public function test_dangerous_file_types_are_rejected(): void
    {
        $id = $this->createDeadline();
        $this->as($this->hayko)->postJson("/api/deadlines/{$id}/confirm")->assertOk();

        $this->as($this->hayko)->post("/api/deadlines/{$id}/submissions", [
            'files' => [UploadedFile::fake()->create('shell.php', 1)],
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_login_validation_disabled_accounts_and_admin_protection(): void
    {
        $origin = ['Origin' => 'http://localhost:5173'];

        $this->withHeaders($origin)->postJson('/api/login', ['username' => 'hayko', 'password' => 'wrong'])->assertStatus(422);
        $this->withHeaders($origin)->postJson('/api/login', ['username' => 'hayko', 'password' => 'Secret12345'])->assertOk()->assertJsonPath('user.username', 'hayko');
        $this->assertNotNull($this->hayko->fresh()->last_login_at);

        $this->hayko->update(['status' => User::DISABLED]);
        $this->withHeaders($origin)->postJson('/api/login', ['username' => 'hayko', 'password' => 'Secret12345'])->assertStatus(422);

        // The last active administrator can neither be disabled nor deleted.
        $this->as($this->admin)->postJson("/api/users/{$this->admin->id}/disable")->assertStatus(409);
        $this->as($this->admin)->deleteJson("/api/users/{$this->admin->id}")->assertStatus(409);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/deadlines')->assertUnauthorized();
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }
}
