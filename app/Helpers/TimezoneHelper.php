<?php

namespace App\Helpers;

final class TimezoneHelper
{
    private static array $countryTimezones = [
        'russia'     => 'Europe/Moscow',
        'kazakhstan' => 'Asia/Almaty',
        'belarus'    => 'Europe/Minsk',
        'uzbekistan' => 'Asia/Tashkent',
        'kyrgyzstan' => 'Asia/Bishkek',
        'ukraine'    => 'Europe/Kyiv',
    ];

    public static function getByCountry(?string $country): string
    {
        if (!$country) {
            return 'UTC';
        }

        $cleanCountry = mb_strtolower(trim($country));

        return self::$countryTimezones[$cleanCountry] ?? 'UTC';
    }
}
