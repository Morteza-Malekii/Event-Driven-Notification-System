<?php

namespace Tests\Feature\Api;

use App\Models\NotificationBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationBatchControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(int $count = 2): array
    {
        $notifications = [];
        for ($i = 0; $i < $count; $i++) {
            $notifications[] = [
                'channel'   => 'sms',
                'recipient' => '+9891234567' . $i,
                'content'   => "Message {$i}",
                'priority'  => 'normal',
            ];
        }

        return ['name' => 'Test Batch', 'notifications' => $notifications];
    }

    public function test_can_create_batch(): void
    {
        Event::fake();

        $this->postJson('/api/batches', $this->validPayload(2))
             ->assertStatus(201)
             ->assertJsonPath('success', true)
             ->assertJsonPath('data.created', 2)
             ->assertJsonPath('data.failed', 0);
    }

    public function test_batch_response_contains_batch_resource(): void
    {
        Event::fake();

        $this->postJson('/api/batches', $this->validPayload(1))
             ->assertStatus(201)
             ->assertJsonStructure(['data' => ['batch' => ['id', 'status', 'total_count']]]);
    }

    public function test_notifications_are_persisted_with_batch_id(): void
    {
        Event::fake();

        $response = $this->postJson('/api/batches', $this->validPayload(3))
                         ->assertStatus(201);

        $batchId = $response->json('data.batch.id');
        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseHas('notifications', ['batch_id' => $batchId]);
    }

    public function test_validation_requires_notifications_field(): void
    {
        $this->postJson('/api/batches', [])
             ->assertStatus(422);
    }

    public function test_validation_rejects_empty_notifications_array(): void
    {
        $this->postJson('/api/batches', ['notifications' => []])
             ->assertStatus(422);
    }

    public function test_validation_rejects_more_than_1000_notifications(): void
    {
        $notifications = array_fill(0, 1001, [
            'channel' => 'sms', 'recipient' => '+98', 'content' => 'msg',
        ]);

        $this->postJson('/api/batches', ['notifications' => $notifications])
             ->assertStatus(422);
    }

    public function test_can_get_batch_by_id(): void
    {
        $batch = NotificationBatch::factory()->create(['total_count' => 5]);

        $this->getJson("/api/batches/{$batch->id}")
             ->assertStatus(200)
             ->assertJsonPath('success', true)
             ->assertJsonPath('data.id', $batch->id)
             ->assertJsonPath('data.total_count', 5);
    }

    public function test_returns_404_for_unknown_batch(): void
    {
        $this->getJson('/api/batches/00000000-0000-0000-0000-000000000000')
             ->assertStatus(404);
    }

    public function test_notifications_inherit_correlation_id_from_request(): void
    {
        Event::fake();
        $correlationId = 'test-correlation-id-123';

        $this->withHeaders(['X-Correlation-ID' => $correlationId])
             ->postJson('/api/batches', $this->validPayload(2))
             ->assertStatus(201);

        $this->assertDatabaseHas('notifications', ['correlation_id' => $correlationId]);
    }

    public function test_same_idempotency_key_returns_cached_batch(): void
    {
        Event::fake();

        $payload = array_merge($this->validPayload(2), ['idempotency_key' => 'batch-key-123']);

        $first  = $this->postJson('/api/batches', $payload)->assertStatus(201);
        $second = $this->postJson('/api/batches', $payload)->assertStatus(200);

        $this->assertEquals($first->json('data.batch.id'), $second->json('data.batch.id'));
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_different_payload_with_same_idempotency_key_returns_409(): void
    {
        Event::fake();

        $this->postJson('/api/batches', array_merge($this->validPayload(2), [
            'idempotency_key' => 'batch-key-conflict',
        ]))->assertStatus(201);

        $this->postJson('/api/batches', array_merge($this->validPayload(3), [
            'idempotency_key' => 'batch-key-conflict',
        ]))->assertStatus(409);
    }

    public function test_batch_without_name_is_accepted(): void
    {
        Event::fake();

        $this->postJson('/api/batches', [
            'notifications' => [
                ['channel' => 'email', 'recipient' => 'a@b.com', 'content' => 'msg'],
            ],
        ])->assertStatus(201);
    }
}
