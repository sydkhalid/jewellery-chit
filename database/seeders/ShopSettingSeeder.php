<?php

namespace Database\Seeders;

use App\Models\ShopSetting;
use Illuminate\Database\Seeder;

class ShopSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'shop_name', 'value' => 'Jewellery Chit', 'type' => 'text', 'group_name' => 'shop'],
            ['key' => 'shop_logo', 'value' => null, 'type' => 'file', 'group_name' => 'shop'],
            ['key' => 'shop_address', 'value' => null, 'type' => 'text', 'group_name' => 'shop'],
            ['key' => 'shop_mobile', 'value' => null, 'type' => 'text', 'group_name' => 'shop'],
            ['key' => 'shop_email', 'value' => null, 'type' => 'text', 'group_name' => 'shop'],
            ['key' => 'gstin', 'value' => null, 'type' => 'text', 'group_name' => 'tax'],
            ['key' => 'receipt_prefix', 'value' => 'RCPT', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'chit_number_prefix', 'value' => 'CHIT', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'payment_number_prefix', 'value' => 'PAY', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'closure_number_prefix', 'value' => 'CLS', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'refund_number_prefix', 'value' => 'REF', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'invoice_number_prefix', 'value' => 'INV', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'handover_number_prefix', 'value' => 'HND', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'financial_year', 'value' => now()->format('Y').'-'.now()->addYear()->format('y'), 'type' => 'text', 'group_name' => 'shop'],
            ['key' => 'terms_and_conditions', 'value' => null, 'type' => 'text', 'group_name' => 'documents'],
            ['key' => 'whatsapp_enabled', 'value' => '0', 'type' => 'boolean', 'group_name' => 'whatsapp'],
            ['key' => 'whatsapp_api_url', 'value' => null, 'type' => 'text', 'group_name' => 'whatsapp'],
            ['key' => 'whatsapp_api_key', 'value' => null, 'type' => 'text', 'group_name' => 'whatsapp'],
            ['key' => 'sms_enabled', 'value' => '0', 'type' => 'boolean', 'group_name' => 'sms'],
            ['key' => 'sms_api_url', 'value' => null, 'type' => 'text', 'group_name' => 'sms'],
            ['key' => 'sms_api_key', 'value' => null, 'type' => 'text', 'group_name' => 'sms'],
            ['key' => 'backup_enabled', 'value' => '1', 'type' => 'boolean', 'group_name' => 'backup'],
            ['key' => 'backup_disk', 'value' => 'local', 'type' => 'text', 'group_name' => 'backup'],
            ['key' => 'default_grace_period_days', 'value' => '0', 'type' => 'number', 'group_name' => 'chit'],
            ['key' => 'default_late_fee_type', 'value' => 'none', 'type' => 'text', 'group_name' => 'chit'],
            ['key' => 'default_late_fee_value', 'value' => '0', 'type' => 'number', 'group_name' => 'chit'],
        ];

        foreach ($settings as $setting) {
            ShopSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group_name' => $setting['group_name'],
                ]
            );
        }
    }
}
