<?php

declare(strict_types=1);

namespace App\Enums;

enum UserPlan: string
{
    case STARTER = 'starter';
    case PRO = 'pro';
    case MAX = 'max';
}
