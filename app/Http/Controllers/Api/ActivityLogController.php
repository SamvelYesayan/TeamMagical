<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'action' => ['nullable', 'string', 'max:80'],
            'user' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ActivityLog::query()->with('user')->orderByDesc('id');

        if ($request->filled('action')) {
            // "deadline" matches every deadline.* action; "deadline.created" matches exactly.
            $action = (string) $request->input('action');
            str_contains($action, '.') ? $query->where('action', $action) : $query->where('action', 'like', addcslashes($action, '%_\\').'.%');
        }
        if ($request->filled('user')) {
            $query->where('user_id', (int) $request->input('user'));
        }
        if ($request->filled('q')) {
            $query->where('description', 'like', '%'.addcslashes((string) $request->input('q'), '%_\\').'%');
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->input('date_from'))->utc());
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->input('date_to'))->utc());
        }

        return ActivityLogResource::collection($query->paginate((int) $request->input('per_page', 30)));
    }
}
