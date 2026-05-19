<?php

namespace Tests\Feature;

use App\Models\IntegrationTransaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseSevenIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'integrations.mode' => 'sandbox',
            'integrations.simulate_sandbox' => true,
            'integrations.whatsapp.default' => 'twilio',
            'integrations.sms.default' => 'msg91',
            'integrations.payments.default' => 'razorpay',
            'integrations.payments.providers.upi_qr.vpa' => 'shop@upi',
            'integrations.payments.providers.upi_qr.payee_name' => 'Jewellery Chit',
        ]);

        $this->seed(DatabaseSeeder::class);
    }

    public function test_whatsapp_and_sms_sandbox_delivery_are_logged(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/messages/whatsapp', [
            'mobile' => '919999999999',
            'message_type' => 'general',
            'message' => 'Sandbox WhatsApp delivery test.',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.log.status', 'sent');

        $this->postJson('/api/messages/sms', [
            'mobile' => '919999999999',
            'message_type' => 'general',
            'message' => 'Sandbox SMS delivery test.',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.log.status', 'sent');

        $this->assertDatabaseHas('integration_transactions', [
            'gateway_type' => 'message',
            'provider' => 'twilio',
            'mode' => 'sandbox',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('integration_transactions', [
            'gateway_type' => 'message',
            'provider' => 'msg91',
            'mode' => 'sandbox',
            'status' => 'success',
        ]);
    }

    public function test_payment_gateway_order_and_webhook_flow(): void
    {
        Sanctum::actingAs($this->admin());

        $response = $this->postJson('/api/payments/gateway/order', [
            'provider' => 'razorpay',
            'amount' => 1250,
            'currency' => 'INR',
            'local_reference' => 'TEST-RAZORPAY-001',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction.provider', 'razorpay')
            ->assertJsonPath('data.transaction.status', 'pending');

        $externalId = $response->json('data.transaction.external_id');

        $this->postJson('/api/webhooks/payments/razorpay', [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'order_id' => $externalId,
                        'id' => 'pay_test_001',
                    ],
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction.status', 'success');

        $this->assertDatabaseHas('integration_transactions', [
            'provider' => 'razorpay',
            'external_id' => $externalId,
            'status' => 'success',
        ]);
    }

    public function test_upi_qr_order_returns_upi_uri(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/payments/gateway/order', [
            'provider' => 'upi_qr',
            'amount' => 500,
            'currency' => 'INR',
            'local_reference' => 'UPI-TEST-001',
            'description' => 'Test UPI QR payment',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction.provider', 'upi_qr')
            ->assertJsonPath('data.transaction.response_payload.upi_uri', fn (string $uri): bool => str_starts_with($uri, 'upi://pay?'));
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }
}
