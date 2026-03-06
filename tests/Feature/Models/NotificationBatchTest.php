<?php

namespace Tests\Feature\Models;

use App\Enums\BatchStatus;
use App\Models\NotificationBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationBatchTest extends TestCase
{
    use RefreshDatabase;

    private function createBatch(array $attributes = []): NotificationBatch
    {
        return NotificationBatch::create(array_merge([
            'status'         => BatchStatus::PENDING,
            'total_count'    => 0,
            'pending_count'  => 0,
            'sent_count'     => 0,
            'failed_count'   => 0,
            'canceled_count' => 0,
        ], $attributes));
    }

    public function test_increment_counter(): void
    {
        $batch = $this->createBatch(['sent_count' => 2]);

        $batch->incrementCounter('sent_count');

        $this->assertEquals(3, $batch->fresh()->sent_count);
    }

    public function test_decrement_counter(): void
    {
        $batch = $this->createBatch(['pending_count' => 5]);

        $batch->decrementCounter('pending_count');

        $this->assertEquals(4, $batch->fresh()->pending_count);
    }

    public function test_recalculate_status_completed(): void
    {
        $batch = $this->createBatch([
            'total_count'    => 10,
            'pending_count'  => 0,
            'sent_count'     => 10,
            'failed_count'   => 0,
            'canceled_count' => 0,
        ]);

        $batch->recalculateStatus();

        $this->assertEquals(BatchStatus::COMPLETED, $batch->fresh()->status);
    }

    public function test_recalculate_status_failed(): void
    {
        $batch = $this->createBatch([
            'total_count'    => 10,
            'pending_count'  => 0,
            'sent_count'     => 0,
            'failed_count'   => 10,
            'canceled_count' => 0,
        ]);

        $batch->recalculateStatus();

        $this->assertEquals(BatchStatus::FAILED, $batch->fresh()->status);
    }

    public function test_recalculate_status_partial_failed(): void
    {
        $batch = $this->createBatch([
            'total_count'    => 10,
            'pending_count'  => 0,
            'sent_count'     => 7,
            'failed_count'   => 3,
            'canceled_count' => 0,
        ]);

        $batch->recalculateStatus();

        $this->assertEquals(BatchStatus::PARTIAL_FAILED, $batch->fresh()->status);
    }

    public function test_recalculate_status_processing(): void
    {
        $batch = $this->createBatch([
            'total_count'   => 10,
            'pending_count' => 5,
            'sent_count'    => 5,
            'failed_count'  => 0,
        ]);

        $batch->recalculateStatus();

        $this->assertEquals(BatchStatus::PROCESSING, $batch->fresh()->status);
    }

    public function test_has_many_notifications(): void
    {
        $batch = $this->createBatch();

        $this->assertCount(0, $batch->notifications);
    }
}
