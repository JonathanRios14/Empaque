<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'SuperAdmin',
            'guard_name' => 'web',
        ]);

        $user = User::updateOrCreate(
            ['email' => 'admin@empaque.com'],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make('Admin123*'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles([$role]);
    }
}