<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's users.
     */
    public function run(): void
    {
        // Default Super Admin.
        User::updateOrCreate(
            ['email' => 'admin@xflow.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('Password'),
                'role'     => User::ROLE_SUPER_ADMIN,
            ],
        );

        // Demo account per remaining role (handy for testing role-based access).
        $demoUsers = [
            ['name' => 'Leads Admin', 'email' => 'leads@xflow.com',     'role' => User::ROLE_LEADS_ADMIN],
            ['name' => 'Sales Rep',   'email' => 'sales@xflow.com',     'role' => User::ROLE_SALES],
            ['name' => 'Developer',   'email' => 'developer@xflow.com', 'role' => User::ROLE_DEVELOPER],
        ];

        foreach ($demoUsers as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name'     => $user['name'],
                    'password' => Hash::make('Password'),
                    'role'     => $user['role'],
                ],
            );
        }

        // Sample users for User Management testing (includes one inactive account).
        $sampleUsers = [
            ['name' => 'Sara Sales',     'email' => 'sara@xflow.com',   'role' => User::ROLE_SALES,       'is_active' => true],
            ['name' => 'Dev Dana',       'email' => 'dana@xflow.com',   'role' => User::ROLE_DEVELOPER,   'is_active' => true],
            ['name' => 'Leo Leads',      'email' => 'leo@xflow.com',    'role' => User::ROLE_LEADS_ADMIN, 'is_active' => true],
            ['name' => 'Ina Inactive',   'email' => 'ina@xflow.com',    'role' => User::ROLE_SALES,       'is_active' => false],
        ];

        foreach ($sampleUsers as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name'      => $user['name'],
                    'password'  => Hash::make('Password'),
                    'role'      => $user['role'],
                    'is_active' => $user['is_active'],
                ],
            );
        }
    }
}
