<?php

namespace App\Enums;

enum Period: string
{
    case Weak = 'weak';
    case Month = 'month';
    case ThreeMonths = '3month';
    case FourMonths = '4month';
    case SixMonths = '6month';
    case Year = '1year';
}
