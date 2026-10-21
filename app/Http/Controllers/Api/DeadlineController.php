<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeadlineResource;
use App\Models\Deadline;
use App\Rules\WorkFile;
use App\Services\DeadlineWorkflow;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeadlineController extends Controller
{
    public function __construct(private readonly DeadlineWorkflow $workflow)
    {
    }

    private function utc(mixed $value): ?Carbon
    {
        return $value ? Carbon::parse($value)->utc() : null;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Deadline::class);

        $request->validate([
            'filter' => ['nullable', Rule::in(['all', 'mine', 'assigned_by_me', 'active', 'pending_confirmation', 'in_progress', 'submitted', 'completed', 'overdue'])],
            'priority' => ['nullable', Rule::in(Deadline::PRIORITIES)],
            'user' => ['nullable', 'integer'],
            'project' => ['nullable', 'string', 'max:20'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $me = $request->user();
        $filter = $request->input('filter', 'all');

        $query = Deadline::query()
            ->with(['project', 'assignee', 'creator', 'pendingChangeRequest'])
            ->withCount(['attachments', 'submissions']);

        match ($filter) {
            'mine' => $query->where('assigned_to', $me->id),
            'assigned_by_me' => $query->where('created_by', $me->id),
            'active' => $query->active(),
            'pending_confirmation' => $query->whereIn('status', [Deadline::NEW, Deadline::PENDING_CONFIRMATION]),
            'in_progress' => $query->whereIn('status', [Deadline::CONFIRMED, Deadline::IN_PROGRESS]),
            'submitted' => $query->whereIn('status', Deadline::REVIEW_STATUSES),
            'completed' => $query->where('status', Deadline::COMPLETED),
            'overdue' => $query->overdue(),
            default => null,
        };

        if ($request->filled('user')) {
            $query->where('assigned_to', (int) $request->input('user'));
        }
        if ($request->filled('project')) {
            $request->input('project') === 'none'
                ? $query->whereNull('project_id')
                : $query->where('project_id', (int) $request->input('project'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        if ($from = $this->utc($request->input('date_from'))) {
            $query->where('due_at', '>=', $from);
        }
        if ($to = $this->utc($request->input('date_to'))) {
            $query->where('due_at', '<=', $to);
        }
        if ($request->filled('q')) {
            $term = addcslashes((string) $request->input('q'), '%_\\');
            $query->where('title', 'like', "%{$term}%");
        }

        if ($filter === 'completed') {
            $query->orderByDesc('completed_at');
        } else {
            // Open work first, soonest due date on top; finished work sinks to the bottom.
            $query->orderByRaw('CASE WHEN status = ? THEN 1 ELSE 0 END', [Deadline::COMPLETED])->orderBy('due_at')->orderBy('id');
        }

        return DeadlineResource::collection($query->paginate((int) $request->input('per_page', 20)));
    }

    public function show(Request $request, Deadline $deadline): DeadlineResource
    {
        $this->authorize('view', $deadline);

        return new DeadlineResource($this->loadDetail($request, $deadline));
    }

    private function loadDetail(Request $request, Deadline $deadline): Deadline
    {
        $deadline->load([
            'project', 'assignee', 'creator', 'attachments', 'pendingChangeRequest',
            'changeRequests.requestedBy', 'changeRequests.decidedBy',
            'events.user',
        ])->loadCount(['attachments', 'submissions']);

        if ($request->user()->can('viewSubmissions', $deadline)) {
            $deadline->load(['submissions.user', 'submissions.reviewer', 'submissions.files']);
        }

        return $deadline;
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Deadline::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('status', 'active')->whereNull('deleted_at'))],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'starts_at' => ['nullable', 'date', 'before:due_at'],
            'due_at' => ['required', 'date', 'after:now'],
            'priority' => ['nullable', Rule::in(Deadline::PRIORITIES)],
            'attachments' => ['nullable', 'array', 'max:'.config('magical.uploads.max_files')],
            'attachments.*' => [new WorkFile],
        ]);

        $data['due_at'] = $this->utc($data['due_at']);
        $data['starts_at'] = $this->utc($data['starts_at'] ?? null);

        $deadline = $this->workflow->create($request->user(), $data, $request->file('attachments', []));

        return (new DeadlineResource($this->loadDetail($request, $deadline)))->response()->setStatusCode(201);
    }

    public function update(Request $request, Deadline $deadline): DeadlineResource
    {
        $this->authorize('update', $deadline);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('status', 'active')->whereNull('deleted_at'))],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'starts_at' => ['nullable', 'date', 'before:due_at'],
            'due_at' => ['required', 'date'],
            'priority' => ['required', Rule::in(Deadline::PRIORITIES)],
        ]);

        $data['due_at'] = $this->utc($data['due_at']);
        $data['starts_at'] = $this->utc($data['starts_at'] ?? null);

        $updated = $this->workflow->update($deadline, $request->user(), $data);

        return new DeadlineResource($this->loadDetail($request, $updated->refresh()));
    }

    public function destroy(Request $request, Deadline $deadline): JsonResponse
    {
        $this->authorize('delete', $deadline);
        $this->workflow->delete($deadline, $request->user());

        return response()->json(['message' => 'Դեդլայնը ջնջվեց։']);
    }
}
