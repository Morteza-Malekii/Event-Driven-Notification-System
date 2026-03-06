<?php

namespace Tests\Unit\Enums;

use App\Enums\NotificationChannel;
use PHPUnit\Framework\TestCase;

class NotificationChannelTest extends TestCase
{
    public function test_has_sms_case(): void
    {
        $this->assertSame('sms', NotificationChannel::SMS->value);
    }

    public function test_has_email_case(): void
    {
        $this->assertSame('email', NotificationChannel::EMAIL->value);
    }

    public function test_has_push_case(): void
    {
        $this->assertSame('push', NotificationChannel::PUSH->value);
    }

    public function test_can_be_created_from_value(): void
    {
        $this->assertSame(NotificationChannel::SMS, NotificationChannel::from('sms'));
        $this->assertSame(NotificationChannel::EMAIL, NotificationChannel::from('email'));
        $this->assertSame(NotificationChannel::PUSH, NotificationChannel::from('push'));
    }

    public function test_returns_null_for_invalid_value(): void
    {
        $this->assertNull(NotificationChannel::tryFrom('invalid'));
    }

    public function test_has_exactly_three_cases(): void
    {
        $this->assertCount(3, NotificationChannel::cases());
    }
}
