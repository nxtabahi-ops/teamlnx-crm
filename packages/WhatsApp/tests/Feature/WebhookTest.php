<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\WhatsApp\Models\WhatsAppAccount;
use Tests\TestCase;

final class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_verification_returns_challenge(): void
    {
        $team = Team::factory()->create();
        $account = WhatsAppAccount::create([
            'team_id' => $team->id,
            'phone_number_id' => '123456789',
            'waba_id' => '987654321',
            'phone_number' => '+15551234567',
            'display_name' => 'Test Account',
            'access_token' => 'secret-token',
            'verify_token' => 'my-verify-token',
            'is_active' => true,
        ]);

        $response = $this->get('/api/whatsapp/webhook/' . $account->id . '?hub_mode=subscribe&hub_verify_token=my-verify-token&hub_challenge=11223344');

        $response->assertStatus(200);
        $response->assertSee('11223344');
    }
}
