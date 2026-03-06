<?php

namespace Tests\Unit\Enums;

use App\Enums\NotificationStatus;
use PHPUnit\Framework\TestCase;

class NotificationStatusTest extends TestCase
{
    public function test_has_exactly_eight_cases(): void
    {
        $this->assertCount(8, NotificationStatus::cases());
    }

    public function test_case_values(): void
    {
        $this->assertSame('pending', NotificationStatus::PENDING->value);
        $this->assertSame('scheduled', NotificationStatus::SCHEDULED->value);
        $this->assertSame('queued', NotificationStatus::QUEUED->value);
        $this->assertSame('processing', NotificationStatus::PROCESSING->value);
        $this->assertSame('sent', NotificationStatus::SENT->value);
        $this->assertSame('delivered', NotificationStatus::DELIVERED->value);
        $this->assertSame('failed', NotificationStatus::FAILED->value);
        $this->assertSame('canceled', NotificationStatus::CANCELED->value);
    }

    public function test_terminal_statuses(): void
    {
        $this->assertTrue(NotificationStatus::SENT->isTerminal());
        $this->assertTrue(NotificationStatus::DELIVERED->isTerminal());
        $this->assertTrue(NotificationStatus::FAILED->isTerminal());
        $this->assertTrue(NotificationStatus::CANCELED->isTerminal());
    }

    public function test_non_terminal_statuses(): void
    {
        $this->assertFalse(NotificationStatus::PENDING->isTerminal());
        $this->assertFalse(NotificationStatus::SCHEDULED->isTerminal());
        $this->assertFalse(NotificationStatus::QUEUED->isTerminal());
        $this->assertFalse(NotificationStatus::PROCESSING->isTerminal());
    }

    public function test_cancelable_statuses(): void
    {
        $this->assertTrue(NotificationStatus::PENDING->isCancelable());
        $this->assertTrue(NotificationStatus::SCHEDULED->isCancelable());
        $this->assertTrue(NotificationStatus::QUEUED->isCancelable());
    }

    public function test_non_cancelable_statuses(): void
    {
        $this->assertFalse(NotificationStatus::PROCESSING->isCancelable());
        $this->assertFalse(NotificationStatus::SENT->isCancelable());
        $this->assertFalse(NotificationStatus::DELIVERED->isCancelable());
        $this->assertFalse(NotificationStatus::FAILED->isCancelable());
        $this->assertFalse(NotificationStatus::CANCELED->isCancelable());
    }

    public function test_can_be_created_from_value(): void
    {
        $this->assertSame(NotificationStatus::PENDING, NotificationStatus::from('pending'));
        $this->assertSame(NotificationStatus::FAILED, NotificationStatus::from('failed'));
    }

    public function test_returns_null_for_invalid_value(): void
    {
        $this->assertNull(NotificationStatus::tryFrom('invalid'));
    }
}
