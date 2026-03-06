<?php

namespace Tests\Unit\Enums;

use App\Enums\DeliveryAttemptStatus;
use PHPUnit\Framework\TestCase;

class DeliveryAttemptStatusTest extends TestCase
{
    public function test_has_exactly_three_cases(): void
    {
        $this->assertCount(3, DeliveryAttemptStatus::cases());
    }

    public function test_case_values(): void
    {
        $this->assertSame('success', DeliveryAttemptStatus::SUCCESS->value);
        $this->assertSame('failed', DeliveryAttemptStatus::FAILED->value);
        $this->assertSame('rate_limited', DeliveryAttemptStatus::RATE_LIMITED->value);
    }

    public function test_can_be_created_from_value(): void
    {
        $this->assertSame(DeliveryAttemptStatus::SUCCESS, DeliveryAttemptStatus::from('success'));
        $this->assertSame(DeliveryAttemptStatus::FAILED, DeliveryAttemptStatus::from('failed'));
        $this->assertSame(DeliveryAttemptStatus::RATE_LIMITED, DeliveryAttemptStatus::from('rate_limited'));
    }

    public function test_returns_null_for_invalid_value(): void
    {
        $this->assertNull(DeliveryAttemptStatus::tryFrom('invalid'));
    }
}
