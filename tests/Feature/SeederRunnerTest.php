<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PaymentMode;
use App\Models\ShopSetting;
use App\Models\User;
use Database\Seeders\ProductionBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_installer_page_can_run_all_pending_seeders_once(): void
    {
        $this->get(route('seeders.index'))
            ->assertOk()
            ->assertSee('Seeder Installation')
            ->assertSee('Run all seeders')
            ->assertSee('Pending');

        $this->post(route('seeders.run'))
            ->assertRedirect(route('seeders.index'))
            ->assertSessionHas('success');

        $this->assertTrue(Branch::query()->where('branch_code', 'MAIN')->exists());
        $this->assertSame(5, PaymentMode::query()->count());
        $this->assertSame(26, ShopSetting::query()->count());
        $this->assertTrue(User::query()->where('email', 'admin@example.com')->firstOrFail()->hasRole('Admin'));

        $this->get(route('seeders.index'))
            ->assertOk()
            ->assertSee('Seeder already completed')
            ->assertDontSee('Run all seeders');
    }

    public function test_completed_installation_does_not_run_again_or_reset_existing_user_data(): void
    {
        $this->seed(ProductionBootstrapSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $admin->forceFill([
            'name' => 'Custom Admin',
            'password' => Hash::make('custom-secret'),
        ])->save();

        $this->post(route('seeders.run'))
            ->assertRedirect(route('seeders.index'))
            ->assertSessionHas('error', 'Seeder installation is already completed.');

        $admin->refresh();

        $this->assertSame('Custom Admin', $admin->name);
        $this->assertTrue(Hash::check('custom-secret', $admin->password));
    }
}
