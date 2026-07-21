<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin',   'guard_name' => 'web'],
            ['name' => 'Manager', 'guard_name' => 'web'],
            ['name' => 'Viewer',  'guard_name' => 'web'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name'], 'guard_name' => $role['guard_name']]);
        }
    }
}
