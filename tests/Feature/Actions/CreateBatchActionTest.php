<?php

namespace Tests\Feature\Actions;

use App\Actions\CreateBatchAction;
use App\Events\NotificationCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateBatchActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_batch_with_correct_count(): void
    {
        Event::fake();
        $result = app(CreateBatchAction::class)->execute([
            'name' => 'Test Batch',
            'notifications' => [
                ['channel' => 'sms',   'recipient' => '+98',     'content' => 'msg1', 'priority' => 'high'],
                ['channel' => 'email', 'recipient' => 'a@b.com', 'content' => 'msg2', 'priority' => 'normal'],
            ],
        ]);

        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['failed']);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_fires_notification_created_for_each_notification(): void
    {
        Event::fake([NotificationCreated::class]);
        app(CreateBatchAction::class)->execute([
            'notifications' => [
                ['channel' => 'sms', 'recipient' => '+98', 'content' => 'msg1', 'priority' => 'high'],
                ['channel' => 'sms', 'recipient' => '+99', 'content' => 'msg2', 'priority' => 'high'],
            ],
        ]);

        Event::assertDispatchedTimes(NotificationCreated::class, 2);
    }

    public function test_all_notifications_have_batch_id(): void
    {
        Event::fake();
        $result = app(CreateBatchAction::class)->execute([
            'notifications' => [
                ['channel' => 'sms', 'recipient' => '+98', 'content' => 'msg', 'priority' => 'normal'],
            ],
        ]);

        $batchId = $result['batch']->id;
        $this->assertDatabaseHas('notifications', ['batch_id' => $batchId]);
    }
}
