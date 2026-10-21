<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AppNotification;
use App\Models\Deadline;
use App\Models\DeadlineChangeRequest;
use App\Models\Role;
use App\Models\SubmissionFile;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeadlineWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('private');
    }

    public function test_complete_deadline_lifecycle(): void
    {
        $admin = $this->makeUser('boss', Role::ADMIN);
        $hayko = $this->makeUser('hayko');
        $other = $this->makeUser('other');

        // 2-4: admin creates a project and a deadline assigned to the employee
        $projectId = $this->actingAs($admin)->postJson('/api/projects', ['name' => 'Magical Website'])->assertCreated()->json('data.id');

        $id = $this->actingAs($admin)->postJson('/api/deadlines', [
            'title' => 'Finish Magical website',
            'description' => 'Everything',
            'assigned_to' => $hayko->id,
            'project_id' => $projectId,
            'due_at' => now()->addDays(5)->toISOString(),
            'priority' => 'high',
        ])->assertCreated()->assertJsonPath('data.status', 'new')->json('data.id');

        // 6-7: employee sees it and may answer it
        $this->actingAs($hayko)->getJson("/api/deadlines/{$id}")
            ->assertOk()
            ->assertJsonPath('data.can.confirm', true)
            ->assertJsonPath('data.can.submit', false);
        $this->assertTrue(AppNotification::query()->where('user_id', $hayko->id)->where('type', 'deadline.assigned')->exists());

        // everybody can see the team's deadline, but nobody else can act on it
        $this->actingAs($other)->getJson("/api/deadlines/{$id}")->assertOk()->assertJsonPath('data.can.confirm', false);
        $this->actingAs($other)->postJson("/api/deadlines/{$id}/confirm")->assertForbidden();

        // 8: confirm
        $this->actingAs($hayko)->postJson("/api/deadlines/{$id}/confirm")->assertOk()->assertJsonPath('data.status', 'confirmed');

        // 9-11: change request, accepted by admin
        $proposed = now()->addDays(9)->startOfSecond();
        $this->actingAs($hayko)->postJson("/api/deadlines/{$id}/change-requests", [
            'proposed_due_at' => $proposed->toISOString(),
            'reason' => 'Need more time',
        ])->assertOk()->assertJsonPath('data.status', 'pending_confirmation');
        $this->assertTrue(AppNotification::query()->where('user_id', $admin->id)->where('type', 'deadline.change_requested')->exists());

        $request = DeadlineChangeRequest::query()->firstOrFail();
        $this->actingAs($hayko)->postJson("/api/change-requests/{$request->id}/decision", ['decision' => 'accept'])->assertForbidden();
        $this->actingAs($admin)->postJson("/api/change-requests/{$request->id}/decision", ['decision' => 'accept', 'comment' => 'ok'])
            ->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->assertEquals($proposed->timestamp, Deadline::query()->find($id)->due_at->timestamp);

        // 12-14: employee submits text + a file
        $this->actingAs($hayko)->postJson("/api/deadlines/{$id}/submissions", ['text' => ''])->assertStatus(422); // nothing to submit
        $this->actingAs($hayko)->post("/api/deadlines/{$id}/submissions", [
            'text' => 'First version',
            'files' => [UploadedFile::fake()->create('report.pdf', 50, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.status', 'submitted');

        $file = SubmissionFile::query()->firstOrFail();
        Storage::disk('private')->assertExists($file->path);

        // private file access: owner + admin yes, anybody else no
        $this->actingAs($hayko)->get("/api/submission-files/{$file->id}/download")->assertOk();
        $this->actingAs($admin)->get("/api/submission-files/{$file->id}/download")->assertOk();
        $this->actingAs($other)->get("/api/submission-files/{$file->id}/download")->assertForbidden();
        // other employees do not even receive the submission content
        $this->actingAs($other)->getJson("/api/deadlines/{$id}")->assertJsonMissingPath('data.submissions');

        // 15-16: admin marks it incomplete - feedback is mandatory
        $submissionId = $this->actingAs($admin)->getJson("/api/deadlines/{$id}")->json('data.submissions.0.id');
        $this->actingAs($admin)->postJson("/api/submissions/{$submissionId}/review", ['decision' => 'needs_revision'])->assertStatus(422);
        $this->actingAs($admin)->postJson("/api/submissions/{$submissionId}/review", [
            'decision' => 'needs_revision',
            'feedback' => 'Mobile version is not finished',
        ])->assertOk()->assertJsonPath('data.status', 'needs_revision');

        $this->actingAs($hayko)->getJson("/api/deadlines/{$id}")
            ->assertJsonPath('data.submissions.0.feedback', 'Mobile version is not finished')
            ->assertJsonPath('data.can.submit', true);

        // 17: re-submission is a NEW numbered entry; history is preserved
        $this->actingAs($hayko)->postJson("/api/deadlines/{$id}/submissions", ['text' => 'Second version'])->assertOk();
        $detail = $this->actingAs($admin)->getJson("/api/deadlines/{$id}")->assertJsonCount(2, 'data.submissions');
        $this->assertEquals([1, 2], collect($detail->json('data.submissions'))->pluck('number')->all());
        $secondId = $detail->json('data.submissions.1.id');

        // 18-19: final approval
        $this->actingAs($admin)->postJson("/api/submissions/{$secondId}/review", ['decision' => 'approve'])
            ->assertOk()->assertJsonPath('data.status', 'completed');
        // the first (already reviewed) submission cannot be re-reviewed
        $this->actingAs($admin)->postJson("/api/submissions/{$submissionId}/review", ['decision' => 'approve'])->assertStatus(409);

        // 20: audit trail
        foreach ([
            'deadline.created', 'deadline.assigned', 'deadline.confirmed', 'deadline.change_requested',
            'deadline.change_accepted', 'submission.created', 'submission.needs_revision', 'submission.approved', 'project.created',
        ] as $action) {
            $this->assertTrue(ActivityLog::query()->where('action', $action)->exists(), "missing activity: {$action}");
        }

        // 21: notifications reached the right people
        foreach (['deadline.assigned', 'deadline.change_accepted', 'submission.needs_revision', 'submission.approved'] as $type) {
            $this->assertTrue(AppNotification::query()->where('user_id', $hayko->id)->where('type', $type)->exists(), "hayko missing {$type}");
        }
        $this->assertTrue(AppNotification::query()->where('user_id', $admin->id)->where('type', 'submission.created')->exists());

        // history was recorded
        $types = $this->actingAs($admin)->getJson("/api/deadlines/{$id}")->json('data.events.*.type');
        foreach (['created', 'confirmed', 'change_requested', 'change_accepted', 'submitted', 'revision_requested', 'approved'] as $type) {
            $this->assertContains($type, $types);
        }
    }

    public function test_admin_can_assign_a_deadline_to_himself(): void
    {
        $admin = $this->makeUser('boss', Role::ADMIN);

        $id = $this->actingAs($admin)->postJson('/api/deadlines', [
            'title' => 'Prepare October financial plan',
            'assigned_to' => $admin->id,
            'due_at' => now()->addDays(2)->toISOString(),
        ])->assertCreated()->assertJsonPath('data.status', 'confirmed')->json('data.id');

        $this->actingAs($admin)->postJson("/api/deadlines/{$id}/start")->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->actingAs($admin)->postJson("/api/deadlines/{$id}/submissions", ['text' => 'Plan done'])->assertOk();
        $this->assertEquals('submitted', Deadline::query()->find($id)->status);
    }

    public function test_rejecting_a_change_request_restores_previous_state(): void
    {
        $admin = $this->makeUser('boss', Role::ADMIN);
        $hayko = $this->makeUser('hayko');

        $id = $this->actingAs($admin)->postJson('/api/deadlines', [
            'title' => 'Task', 'assigned_to' => $hayko->id, 'due_at' => now()->addDays(3)->toISOString(),
        ])->json('data.id');
        $originalDue = Deadline::query()->find($id)->due_at->timestamp;

        $this->actingAs($hayko)->postJson("/api/deadlines/{$id}/change-requests", [
            'proposed_due_at' => now()->addDays(10)->toISOString(), 'reason' => 'Busy',
        ])->assertOk();

        $request = DeadlineChangeRequest::query()->firstOrFail();
        $this->actingAs($admin)->postJson("/api/change-requests/{$request->id}/decision", ['decision' => 'reject', 'comment' => 'No'])
            ->assertOk()->assertJsonPath('data.status', 'new');

        $this->assertEquals($originalDue, Deadline::query()->find($id)->due_at->timestamp);
        $this->assertTrue(AppNotification::query()->where('user_id', $hayko->id)->where('type', 'deadline.change_rejected')->exists());
    }

    public function test_due_date_is_overdue_by_server_clock_and_filterable(): void
    {
        $admin = $this->makeUser('boss', Role::ADMIN);
        $hayko = $this->makeUser('hayko');

        $deadline = Deadline::query()->create([
            'title' => 'Late', 'assigned_to' => $hayko->id, 'created_by' => $admin->id,
            'due_at' => Carbon::now()->subHours(3), 'status' => Deadline::IN_PROGRESS, 'priority' => 'normal',
        ]);

        $this->actingAs($hayko)->getJson("/api/deadlines/{$deadline->id}")
            ->assertJsonPath('data.is_overdue', true)
            ->assertJsonPath('data.display_status', 'overdue');

        $this->actingAs($hayko)->getJson('/api/deadlines?filter=overdue')->assertJsonCount(1, 'data');
        $this->actingAs($hayko)->getJson('/api/deadlines?filter=completed')->assertJsonCount(0, 'data');
    }

    public function test_scheduler_flags_overdue_and_sends_reminders(): void
    {
        $admin = $this->makeUser('boss', Role::ADMIN);
        $hayko = $this->makeUser('hayko');

        Deadline::query()->create(['title' => 'Late', 'assigned_to' => $hayko->id, 'created_by' => $admin->id, 'due_at' => now()->subHour(), 'status' => 'in_progress', 'priority' => 'normal']);
        Deadline::query()->create(['title' => 'Soon', 'assigned_to' => $hayko->id, 'created_by' => $admin->id, 'due_at' => now()->addHours(5), 'status' => 'confirmed', 'priority' => 'normal']);

        $this->artisan('magical:process')->assertSuccessful();
        $this->artisan('magical:process')->assertSuccessful(); // idempotent

        $this->assertEquals(1, AppNotification::query()->where('user_id', $hayko->id)->where('type', 'deadline.overdue')->count());
        $this->assertEquals(1, AppNotification::query()->where('user_id', $hayko->id)->where('type', 'deadline.approaching')->count());
        $this->assertEquals(1, AppNotification::query()->where('user_id', $admin->id)->where('type', 'deadline.overdue')->count());
    }

    public function test_uploads_reject_dangerous_file_types(): void
    {
        $admin = $this->makeUser('boss', Role::ADMIN);
        $hayko = $this->makeUser('hayko');
        $id = $this->actingAs($admin)->postJson('/api/deadlines', [
            'title' => 'Task', 'assigned_to' => $admin->id, 'due_at' => now()->addDay()->toISOString(),
        ])->json('data.id');

        foreach (['shell.php', 'run.exe', 'page.html', 'icon.svg'] as $name) {
            $this->actingAs($admin)->post("/api/deadlines/{$id}/submissions", [
                'files' => [UploadedFile::fake()->create($name, 5)],
            ], ['Accept' => 'application/json'])->assertStatus(422);
        }
        $this->assertEquals(0, SubmissionFile::query()->count());
        unset($hayko);
    }
}
