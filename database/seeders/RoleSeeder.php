<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::query()->updateOrCreate(['slug' => Role::ADMIN], ['label' => 'Ադմինիստրատոր']);
        Role::query()->updateOrCreate(['slug' => Role::EMPLOYEE], ['label' => 'Աշխատակից']);
    }
}
