<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Setting;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function serverTime(): JsonResponse
    {
        return response()->json(['now' => now()->toISOString()]);
    }

    /** Shared credential check for the web (cookie) and mobile (token) logins. */
    private function authenticate(Request $request): User
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $username = trim($data['username']);
        $user = User::query()->with('role')->where('username', $username)->first();

        // Always run a hash comparison so response time does not reveal whether the username exists.
        $passwordOk = Hash::check($data['password'], $user?->password ?? Hash::make('magical-dummy-password'));

        if (! $user || ! $passwordOk) {
            ActivityLogger::log($user, 'auth.login_failed', null, "Անհաջող մուտքի փորձ՝ {$username}", ['username' => $username]);
            throw ValidationException::withMessages(['username' => [__('auth.failed')]]);
        }

        if (! $user->isActive()) {
            ActivityLogger::log($user, 'auth.login_blocked', $user, 'Արգելափակված հաշվով մուտքի փորձ');
            throw ValidationException::withMessages(['username' => [__('auth.disabled')]]);
        }

        return $user;
    }

    /** Browser SPA: cookie session + CSRF. */
    public function login(Request $request): JsonResponse
    {
        abort_unless($request->hasSession(), 400, 'Մուտքը հնարավոր է միայն հավելվածի միջոցով։');

        $user = $this->authenticate($request);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('pw_stamp', $user->sessionStamp());

        $user->forceFill(['last_login_at' => now()])->save();
        ActivityLogger::log($user, 'auth.login', $user, 'Մուտք համակարգ');

        return response()->json($this->mePayload($user));
    }

    /** Mobile app: bearer token (Sanctum personal access token). One token per device name. */
    public function mobileLogin(Request $request): JsonResponse
    {
        $request->validate(['device_name' => ['nullable', 'string', 'max:60']]);
        $user = $this->authenticate($request);

        $device = 'mobile-'.preg_replace('/[^A-Za-z0-9._-]/', '', (string) $request->input('device_name', 'app'));
        $user->tokens()->where('name', $device)->delete();
        $token = $user->createToken($device, ['*']);

        $user->forceFill(['last_login_at' => now()])->save();
        ActivityLogger::log($user, 'auth.login', $user, "Մուտք համակարգ (բջջային՝ {$device})");

        return response()->json(['token' => $token->plainTextToken] + $this->mePayload($user));
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        ActivityLogger::log($user, 'auth.logout', $user, 'Ելք համակարգից');

        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            // Mobile: revoke this device's token.
            $token->delete();
        } else {
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return response()->json(['message' => 'Դուք դուրս եկաք համակարգից։']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->mePayload($request->user()));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:120']]);

        $user = $request->user();
        $user->update($data);
        ActivityLogger::log($user, 'user.profile_updated', $user, 'Պրոֆիլի թարմացում');

        return response()->json($this->mePayload($user));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::defaults()],
        ]);

        $user = $request->user();

        // Checked manually (not via the `current_password` rule) so it also works for token requests.
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => [__('validation.current_password')]]);
        }

        $user->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();

        // Every OTHER mobile token of this user is revoked; the current device stays signed in.
        $current = $user->currentAccessToken();
        $user->tokens()->when($current instanceof PersonalAccessToken, fn ($q) => $q->where('id', '!=', $current->id))->delete();

        if ($request->hasSession()) {
            $request->session()->regenerate();
            // Keep THIS session alive; every other session of the user is invalidated by the stamp check.
            $request->session()->put('pw_stamp', $user->fresh()->sessionStamp());
        }

        ActivityLogger::log($user, 'password.changed', $user, 'Գաղտնաբառի փոփոխություն');

        return response()->json(['message' => 'Գաղտնաբառը փոխվեց։', ...$this->mePayload($user->fresh())]);
    }

    private function mePayload(User $user): array
    {
        return [
            'user' => new UserResource($user->loadMissing('role')),
            'settings' => ['company_name' => Setting::get('company_name')],
            'server_time' => now()->toISOString(),
        ];
    }
}
