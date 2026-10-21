<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function makeUser(string $username, string $role = Role::EMPLOYEE, string $status = User::ACTIVE): User
    {
        return User::query()->create([
            'name' => ucfirst($username),
            'username' => $username,
            'password' => 'Password12345',
            'role_id' => Role::query()->where('slug', $role)->value('id'),
            'status' => $status,
        ]);
    }
}
