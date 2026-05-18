<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::updateOrCreate(
            ['branch_code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'mobile' => null,
                'email' => null,
                'address' => '',
                'city' => '',
                'state' => '',
                'pincode' => '',
                'status' => 'active',
            ]
        );
    }
}
