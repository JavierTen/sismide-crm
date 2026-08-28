<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'listBusinessCanvases',
            'createBusinessCanvas',
            'editBusinessCanvas',
            'deleteBusinessCanvas',
            'viewBusinessCanvas',
            'listStudentCanvases',
            'createStudentCanvas',
            'editStudentCanvas',
            'deleteStudentCanvas',
            'viewStudentCanvas',
        ];

        $created = [];
        foreach ($permissions as $name) {
            $created[] = Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($created);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissions = [
            'listBusinessCanvases',
            'createBusinessCanvas',
            'editBusinessCanvas',
            'deleteBusinessCanvas',
            'viewBusinessCanvas',
            'listStudentCanvases',
            'createStudentCanvas',
            'editStudentCanvas',
            'deleteStudentCanvas',
            'viewStudentCanvas',
        ];

        foreach ($permissions as $name) {
            $p = Permission::where('name', $name)->where('guard_name', 'web')->first();
            if ($p) {
                $p->roles()->detach();
                $p->delete();
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
