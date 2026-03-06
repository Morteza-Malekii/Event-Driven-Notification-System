<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case CANCELED = 'canceled';

    public function isTerminal(): bool
    {
        return match($this) {
            self::SENT, self::DELIVERED, self::FAILED, self::CANCELED => true,
            default => false,
        };
    }

    public function isCancelable(): bool
    {
        return match($this) {
            self::PENDING, self::SCHEDULED, self::QUEUED => true,
            default => false,
        };
    }
}
