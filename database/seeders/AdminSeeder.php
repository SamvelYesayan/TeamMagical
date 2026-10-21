<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $username = (string) config('magical.admin.username');
        $role = Role::query()->where('slug', Role::ADMIN)->firstOrFail();

        if (User::withTrashed()->where('username', $username)->exists()) {
            $this->command?->info("Administrator '{$username}' already exists. Password left unchanged.");

            return;
        }

        $password = config('magical.admin.password');
        $generated = false;

        if (blank($password)) {
            $password = Str::password(20, symbols: false);
            $generated = true;
        } else {
            Validator::make(['password' => $password], ['password' => [Password::min(10)->letters()->numbers()]])
                ->validate();
        }

        User::query()->create([
            'name' => config('magical.admin.name'),
            'username' => $username,
            'password' => $password,
            'role_id' => $role->id,
            'position' => 'Ադմինիստրատոր',
            'status' => User::ACTIVE,
            // A generated password is shown once; force replacement at first login.
            'must_change_password' => $generated,
        ]);

        $this->command?->info("Administrator '{$username}' created.");

        if ($generated) {
            $this->command?->warn("Generated one-time password: {$password}");
            $this->command?->warn('Store it safely now. It is not saved anywhere and must be changed at first login.');
        }
    }
}
