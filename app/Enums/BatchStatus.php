<?php

namespace App\Enums;

enum BatchStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case PARTIAL_FAILED = 'partial_failed';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
}
