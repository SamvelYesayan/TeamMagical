<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\Deadline;
use App\Models\Role;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Admin-only user management (routes are behind the `admin` middleware), except options(). */
class UserController extends Controller
{
    /** Lightweight list for selects/filters; available to every signed-in user. */
    public function options(): JsonResponse
    {
        $users = User::query()->with('role')->orderBy('name')->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'position' => $u->position,
            'role' => $u->role?->slug,
            'status' => $u->status,
        ]);

        return response()->json(['data' => $users]);
    }

    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in([Role::ADMIN, Role::EMPLOYEE])],
            'status' => ['nullable', Rule::in([User::ACTIVE, User::DISABLED])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::query()->with('role')->orderBy('name');

        if ($request->filled('q')) {
            $term = '%'.addcslashes((string) $request->input('q'), '%_\\').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('username', 'like', $term)->orWhere('position', 'like', $term));
        }
        if ($request->filled('role')) {
            $query->whereHas('role', fn ($q) => $q->where('slug', $request->input('role')));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return UserResource::collection($query->paginate((int) $request->input('per_page', 20)));
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $user->load('role');
        $assigned = Deadline::query()->where('assigned_to', $user->id);

        $stats = [
            'total' => (clone $assigned)->count(),
            'active' => (clone $assigned)->active()->count(),
            'completed' => (clone $assigned)->where('status', Deadline::COMPLETED)->count(),
            'overdue' => (clone $assigned)->overdue()->count(),
            'under_review' => (clone $assigned)->whereIn('status', Deadline::REVIEW_STATUSES)->count(),
        ];

        $activity = ActivityLog::query()->with('user')->where('user_id', $user->id)->orderByDesc('id')->limit(10)->get();

        return response()->json([
            'user' => new UserResource($user),
            'stats' => $stats,
            'recent_activity' => ActivityLogResource::collection($activity),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')],
            'password' => ['required', 'string', Password::defaults()],
            'role' => ['required', Rule::in([Role::ADMIN, Role::EMPLOYEE])],
            'position' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in([User::ACTIVE, User::DISABLED])],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => $data['password'],
            'role_id' => Role::query()->where('slug', $data['role'])->firstOrFail()->id,
            'position' => $data['position'] ?? null,
            'status' => $data['status'] ?? User::ACTIVE,
            // The administrator chose this password, so the employee must replace it at first login.
            'must_change_password' => true,
        ]);

        ActivityLogger::log($request->user(), 'user.created', $user, "Ստեղծվեց օգտատեր՝ {$user->name} ({$user->username})", ['role' => $data['role']]);

        return (new UserResource($user->load('role')))->response()->setStatusCode(201);
    }

    public function update(Request $request, User $user): UserResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'role' => ['required', Rule::in([Role::ADMIN, Role::EMPLOYEE])],
            'position' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in([User::ACTIVE, User::DISABLED])],
        ]);

        $user->loadMissing('role');
        $losesAdmin = $user->isAdmin() && $user->isActive() && ($data['role'] !== Role::ADMIN || $data['status'] !== User::ACTIVE);

        if ($losesAdmin) {
            abort_if($user->id === $request->user()->id, 409, 'Չեք կարող զրկել ինքներդ ձեզ ադմինիստրատորի իրավունքից կամ արգելափակել ձեզ։');
            abort_if(User::activeAdminCount() <= 1, 409, 'Համակարգում պետք է մնա առնվազն մեկ ակտիվ ադմինիստրատոր։');
        }

        $before = ['role' => $user->role?->slug, 'status' => $user->status];

        $user->update([
            'name' => $data['name'],
            'username' => $data['username'],
            'role_id' => Role::query()->where('slug', $data['role'])->firstOrFail()->id,
            'position' => $data['position'] ?? null,
            'status' => $data['status'],
        ]);

        ActivityLogger::log($request->user(), 'user.updated', $user, "Խմբագրվեց օգտատեր՝ {$user->name}", ['before' => $before, 'after' => ['role' => $data['role'], 'status' => $data['status']]]);

        if ($data['status'] === User::DISABLED) {
            $user->tokens()->delete();
        }

        if ($before['status'] !== $data['status']) {
            ActivityLogger::log($request->user(), $data['status'] === User::DISABLED ? 'user.disabled' : 'user.enabled', $user, ($data['status'] === User::DISABLED ? 'Արգելափակվեց՝ ' : 'Ակտիվացվեց՝ ').$user->name);
        }

        return new UserResource($user->fresh('role'));
    }

    public function disable(Request $request, User $user): UserResource
    {
        return $this->changeStatus($request, $user, true);
    }

    public function enable(Request $request, User $user): UserResource
    {
        return $this->changeStatus($request, $user, false);
    }

    private function changeStatus(Request $request, User $user, bool $disable): UserResource
    {
        $user->loadMissing('role');

        if ($disable) {
            abort_if($user->id === $request->user()->id, 409, 'Չեք կարող արգելափակել ինքներդ ձեզ։');
            abort_if($user->isAdmin() && $user->isActive() && User::activeAdminCount() <= 1, 409, 'Համակարգում պետք է մնա առնվազն մեկ ակտիվ ադմինիստրատոր։');
        }

        $user->update(['status' => $disable ? User::DISABLED : User::ACTIVE]);
        if ($disable) {
            $user->tokens()->delete();
        }
        ActivityLogger::log($request->user(), $disable ? 'user.disabled' : 'user.enabled', $user, ($disable ? 'Արգելափակվեց՝ ' : 'Ակտիվացվեց՝ ').$user->name);

        return new UserResource($user);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string', Password::defaults()]]);

        $user->forceFill(['password' => $data['password'], 'must_change_password' => true])->save();
        $user->tokens()->delete();
        ActivityLogger::log($request->user(), 'user.password_reset', $user, "Վերականգնվեց գաղտնաբառը՝ {$user->name}");

        return response()->json(['message' => 'Գաղտնաբառը վերականգնվեց։ Օգտատերը պետք է այն փոխի առաջին մուտքի ժամանակ։']);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $user->loadMissing('role');

        abort_if($user->id === $request->user()->id, 409, 'Չեք կարող ջնջել ինքներդ ձեզ։');
        abort_if($user->isAdmin() && $user->isActive() && User::activeAdminCount() <= 1, 409, 'Համակարգում պետք է մնա առնվազն մեկ ակտիվ ադմինիստրատոր։');
        abort_if(
            Deadline::query()->where('assigned_to', $user->id)->active()->exists(),
            409,
            'Օգտատերն ունի ակտիվ դեդլայններ։ Նախ փոխեք պատասխանատուին կամ արգելափակեք օգտատիրոջը։'
        );

        $name = $user->name;
        // Soft delete keeps submissions, history and the audit log intact; the username is freed for reuse.
        $user->forceFill(['username' => $user->username.'#deleted'.$user->id, 'status' => User::DISABLED])->save();
        $user->tokens()->delete();
        $user->delete();

        ActivityLogger::log($request->user(), 'user.deleted', null, "Ջնջվեց օգտատեր՝ {$name}", ['user_id' => $user->id]);

        return response()->json(['message' => 'Օգտատերը ջնջվեց։']);
    }
}
