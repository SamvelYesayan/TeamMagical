<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\ChangeRequestResource;
use App\Http\Resources\DeadlineResource;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Deadline;
use App\Models\DeadlineChangeRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private function deadlines()
    {
        return Deadline::query()->with(['project', 'assignee', 'creator', 'pendingChangeRequest'])->withCount(['attachments', 'submissions']);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return $user->isAdmin() ? $this->admin($user) : $this->employee($user);
    }

    private function admin(User $user): JsonResponse
    {
        $pendingRequests = DeadlineChangeRequest::query()
            ->with(['deadline', 'requestedBy'])
            ->where('status', 'pending')
            ->whereHas('deadline')
            ->orderBy('id')
            ->limit(8)
            ->get();

        return response()->json([
            'role' => 'admin',
            'stats' => [
                'total_users' => User::query()->count(),
                'active_deadlines' => Deadline::query()->active()->count(),
                'completed_deadlines' => Deadline::query()->where('status', Deadline::COMPLETED)->count(),
                'pending_submissions' => Deadline::query()->whereIn('status', Deadline::REVIEW_STATUSES)->count(),
                'overdue_deadlines' => Deadline::query()->overdue()->count(),
                'pending_change_requests' => Deadline::query()->where('status', Deadline::PENDING_CONFIRMATION)->count(),
            ],
            'awaiting_review' => DeadlineResource::collection(
                $this->deadlines()->whereIn('status', Deadline::REVIEW_STATUSES)->orderBy('submitted_at')->limit(6)->get()
            ),
            'pending_requests' => ChangeRequestResource::collection($pendingRequests),
            'my_deadlines' => DeadlineResource::collection(
                $this->deadlines()->where('assigned_to', $user->id)->active()->orderBy('due_at')->limit(6)->get()
            ),
            'recent_announcements' => AnnouncementResource::collection(
                Announcement::query()->with(['author', 'attachments'])->visible()->orderByDesc('published_at')->limit(4)->get()
            ),
            'recent_activity' => ActivityLogResource::collection(
                ActivityLog::query()->with('user')->orderByDesc('id')->limit(10)->get()
            ),
        ]);
    }

    private function employee(User $user): JsonResponse
    {
        $mine = fn () => Deadline::query()->where('assigned_to', $user->id);

        return response()->json([
            'role' => 'employee',
            'stats' => [
                'awaiting_confirmation' => $mine()->where('status', Deadline::NEW)->count(),
                'active' => $mine()->active()->count(),
                'needs_revision' => $mine()->where('status', Deadline::NEEDS_REVISION)->count(),
                'under_review' => $mine()->whereIn('status', Deadline::REVIEW_STATUSES)->count(),
                'completed' => $mine()->where('status', Deadline::COMPLETED)->count(),
                'overdue' => $mine()->overdue()->count(),
            ],
            // What the employee must DO right now: answer new deadlines and fix rejected work.
            'action_required' => DeadlineResource::collection(
                $this->deadlines()->where('assigned_to', $user->id)->whereIn('status', [Deadline::NEW, Deadline::NEEDS_REVISION])->orderBy('due_at')->limit(10)->get()
            ),
            'upcoming' => DeadlineResource::collection(
                $this->deadlines()->where('assigned_to', $user->id)->whereIn('status', [Deadline::PENDING_CONFIRMATION, Deadline::CONFIRMED, Deadline::IN_PROGRESS])->orderBy('due_at')->limit(8)->get()
            ),
            'recent_announcements' => AnnouncementResource::collection(
                Announcement::query()->with(['author', 'attachments'])->visible()->orderByDesc('published_at')->limit(4)->get()
            ),
        ]);
    }
}
