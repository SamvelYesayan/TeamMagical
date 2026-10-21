<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class SetAdminPassword extends Command
{
    protected $signature = 'admin:password {username? : Defaults to ADMIN_USERNAME from the environment}';

    protected $description = 'Securely set or reset a user password (prompted, never echoed or stored in shell history)';

    public function handle(): int
    {
        $username = $this->argument('username') ?: config('magical.admin.username');
        $user = User::query()->where('username', $username)->first();

        if (! $user) {
            $this->error("User '{$username}' not found. Run `php artisan db:seed` first.");

            return self::FAILURE;
        }

        $password = (string) $this->secret('New password (min 10 chars, letters + numbers)');
        $confirm = (string) $this->secret('Repeat password');

        if ($password !== $confirm) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $validator = Validator::make(['password' => $password], ['password' => ['required', Password::min(10)->letters()->numbers()]]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user->forceFill(['password' => $password, 'must_change_password' => false])->save();
        ActivityLogger::log(null, 'password.console_reset', $user, "Գաղտնաբառը փոխվեց կոնսոլից՝ {$user->username}");

        $this->info("Password updated for '{$user->username}'.");

        return self::SUCCESS;
    }
}
