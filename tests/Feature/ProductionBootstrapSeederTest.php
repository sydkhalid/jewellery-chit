<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PaymentMode;
use App\Models\ShopSetting;
use App\Models\User;
use Database\Seeders\ProductionBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductionBootstrapSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_bootstraps_defaults_without_overwriting_existing_production_data(): void
    {
        $this->seed(ProductionBootstrapSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $admin->forceFill([
            'name' => 'Existing Owner',
            'password' => Hash::make('custom-secret'),
        ])->save();

        ShopSetting::updateByKey('shop_name', 'Custom Jewellery', 'text', 'shop');
        Branch::query()->where('branch_code', 'MAIN')->firstOrFail()->update(['name' => 'Custom Branch']);
        PaymentMode::query()->where('code', 'cash')->firstOrFail()->update(['status' => 'inactive']);

        $this->seed(ProductionBootstrapSeeder::class);

        $admin->refresh();

        $this->assertSame('Existing Owner', $admin->name);
        $this->assertTrue(Hash::check('custom-secret', $admin->password));
        $this->assertTrue($admin->hasRole('Admin'));
        $this->assertTrue($admin->can('dashboard.view'));

        $this->assertSame('Custom Jewellery', ShopSetting::query()->where('key', 'shop_name')->value('value'));
        $this->assertSame('Custom Branch', Branch::query()->where('branch_code', 'MAIN')->value('name'));
        $this->assertSame('inactive', PaymentMode::query()->where('code', 'cash')->value('status'));

        $this->assertSame(85, Permission::query()->count());
        $this->assertSame(26, ShopSetting::query()->count());
        $this->assertSame(5, PaymentMode::query()->count());
    }
}
