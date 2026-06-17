<?php

declare(strict_types=1);

namespace App\Enums;

enum UserPlan: string
{
    case STARTER = '';
    case PRO = 'pro';
    case MAX = 'max';
}
