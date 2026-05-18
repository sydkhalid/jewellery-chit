<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('branch_code', 'MAIN')->firstOrFail();

        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'mobile' => '9999999999',
                'role' => 'Admin',
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@example.com',
                'mobile' => '8888888888',
                'role' => 'Manager',
            ],
            [
                'name' => 'Staff',
                'email' => 'staff@example.com',
                'mobile' => '7777777777',
                'role' => 'Staff',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'mobile' => $userData['mobile'],
                    'password' => Hash::make('password'),
                    'branch_id' => $branch->id,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
