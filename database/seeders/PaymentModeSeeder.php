<?php

namespace Database\Seeders;

use App\Models\PaymentMode;
use Illuminate\Database\Seeder;

class PaymentModeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Cash', 'code' => 'cash'],
            ['name' => 'UPI', 'code' => 'upi'],
            ['name' => 'Card', 'code' => 'card'],
            ['name' => 'Bank Transfer', 'code' => 'bank_transfer'],
            ['name' => 'Cheque', 'code' => 'cheque'],
        ] as $mode) {
            PaymentMode::updateOrCreate(
                ['code' => $mode['code']],
                [
                    'name' => $mode['name'],
                    'status' => 'active',
                ]
            );
        }
    }
}
