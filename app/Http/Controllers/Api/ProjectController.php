<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Deadline;
use App\Models\Project;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    private function rules(?Project $project = null): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120', Rule::unique('projects', 'name')->ignore($project?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ];
    }

    public function index()
    {
        $projects = Project::query()
            ->withCount(['deadlines', 'deadlines as active_deadlines_count' => fn ($q) => $q->whereIn('status', Deadline::ACTIVE_STATUSES)])
            ->orderBy('name')
            ->get();

        return ProjectResource::collection($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $project = Project::query()->create($data + ['created_by' => $request->user()->id, 'status' => $data['status'] ?? 'active']);

        ActivityLogger::log($request->user(), 'project.created', $project, "Ստեղծվեց նախագիծ՝ {$project->name}");

        return (new ProjectResource($project))->response()->setStatusCode(201);
    }

    public function update(Request $request, Project $project): ProjectResource
    {
        $project->update($request->validate($this->rules($project)));
        ActivityLogger::log($request->user(), 'project.updated', $project, "Խմբագրվեց նախագիծ՝ {$project->name}");

        return new ProjectResource($project);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $name = $project->name;
        // Deadlines of a deleted project are kept (project_id becomes NULL via the foreign key).
        $project->delete();
        ActivityLogger::log($request->user(), 'project.deleted', null, "Ջնջվեց նախագիծ՝ {$name}");

        return response()->json(['message' => 'Նախագիծը ջնջվեց։']);
    }
}
