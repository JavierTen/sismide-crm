<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private array $permissions = [
        'listStudentTrainings',
        'createStudentTraining',
        'editStudentTraining',
        'deleteStudentTraining',
        'viewStudentTraining',
        'listStudentTrainingSessions',
        'createStudentTrainingSession',
        'editStudentTrainingSession',
        'deleteStudentTrainingSession',
        'viewStudentTrainingSession',
    ];

    public function up(): void
    {
        $created = [];
        foreach ($this->permissions as $name) {
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
        foreach ($this->permissions as $name) {
            $p = Permission::where('name', $name)->where('guard_name', 'web')->first();
            if ($p) {
                $p->roles()->detach();
                $p->delete();
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
