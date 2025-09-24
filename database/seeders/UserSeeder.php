<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'administrador SISMIDE',
                'email' => 'adminsismide@incubadoramide.com',
                'password' => Hash::make('admin123'),
                'role' => 'Admin',
            ],
            [
                'name' => 'juanito perez',
                'email' => 'juanitoperez@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'Manager',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
            ]);

            // Asignar rol al usuario
            $role = Role::where('name', $userData['role'])->first();
            if ($role) {
                $user->assignRole($role);
            }
        }
    }
}
