<?php

namespace App\Enums;

enum DeliveryAttemptStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case RATE_LIMITED = 'rate_limited';
}
