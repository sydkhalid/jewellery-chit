<?php

namespace Database\Seeders;

use App\Services\BootstrapSeederManager;
use Illuminate\Database\Seeder;

class ProductionBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        app(BootstrapSeederManager::class)->runAllPending();
    }
}
