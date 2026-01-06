<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExchangeRateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_access_returns_401(): void
    {
        $response = $this->getJson('/api/exchange-rate');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_authenticated_user_can_get_latest_rate(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        ExchangeRate::create([
            'type' => 'parallel',
            'buy' => 3.50,
            'sell' => 3.60,
            'source' => 'test',
        ]);

        $response = $this->getJson('/api/exchange-rate');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'source',
                    'type',
                    'type_label',
                    'buy',
                    'sell',
                    'updated_at',
                    'time_ago',
                ],
            ])
            ->assertJsonPath('data.buy', 3.50)
            ->assertJsonPath('data.type', 'parallel');
    }

    public function test_can_filter_by_type_sunat(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        ExchangeRate::create([
            'type' => 'sunat',
            'buy' => 3.35,
            'sell' => 3.45,
            'source' => 'test',
        ]);

        $response = $this->getJson('/api/exchange-rate?type=sunat');

        $response->assertStatus(200)
            ->assertJsonPath('data.type', 'sunat')
            ->assertJsonPath('data.type_label', 'Sunat')
            ->assertJsonPath('data.buy', 3.35);
    }

    public function test_returns_404_when_no_data_available(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // No data created

        $response = $this->getJson('/api/exchange-rate');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No data available',
            ]);
    }
}
