<?php

namespace Tests\Unit\Enums;

use App\Enums\NotificationPriority;
use PHPUnit\Framework\TestCase;

class NotificationPriorityTest extends TestCase
{
    public function test_has_high_case(): void
    {
        $this->assertSame('high', NotificationPriority::HIGH->value);
    }

    public function test_has_normal_case(): void
    {
        $this->assertSame('normal', NotificationPriority::NORMAL->value);
    }

    public function test_has_low_case(): void
    {
        $this->assertSame('low', NotificationPriority::LOW->value);
    }

    public function test_can_be_created_from_value(): void
    {
        $this->assertSame(NotificationPriority::HIGH, NotificationPriority::from('high'));
        $this->assertSame(NotificationPriority::NORMAL, NotificationPriority::from('normal'));
        $this->assertSame(NotificationPriority::LOW, NotificationPriority::from('low'));
    }

    public function test_returns_null_for_invalid_value(): void
    {
        $this->assertNull(NotificationPriority::tryFrom('invalid'));
    }

    public function test_has_exactly_three_cases(): void
    {
        $this->assertCount(3, NotificationPriority::cases());
    }

    public function test_high_queue_name(): void
    {
        $this->assertSame('notifications-high', NotificationPriority::HIGH->queueName());
    }

    public function test_normal_queue_name(): void
    {
        $this->assertSame('notifications-normal', NotificationPriority::NORMAL->queueName());
    }

    public function test_low_queue_name(): void
    {
        $this->assertSame('notifications-low', NotificationPriority::LOW->queueName());
    }
}
