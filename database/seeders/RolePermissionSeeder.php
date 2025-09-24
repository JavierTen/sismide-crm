<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener los roles
        $adminRole = Role::where('name', 'Admin')->first();
        $managerRole = Role::where('name', 'Manager')->first();

        // Admin tiene todos los permisos
        $allPermissions = Permission::all();
        if ($adminRole) {
            $adminRole->syncPermissions($allPermissions);
        }

        // Manager solo tiene permisos de Emprendedores
        $managerPermissions = Permission::whereIn('name', [
            'createEntrepreneur',
            'editEntrepreneur',
            'listEntrepreneurs',
            'deleteEntrepreneur',
        ])->get();

        if ($managerRole) {
            $managerRole->syncPermissions($managerPermissions);
        }
    }
}
