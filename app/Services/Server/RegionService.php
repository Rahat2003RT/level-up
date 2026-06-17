<?php

namespace App\Services\Server;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

final class RegionService
{
    private string $username;

    public function __construct()
    {
        $this->username = config('services.geonames.username', 'demo');
    }

    public function getCountries(array $data): array
    {
        $locale = $data['locale'] ?? 'en';

        return Cache::remember("external_countries_{$locale}", now()->addDay(), function () use ($locale) {
            $response = Http::get('http://api.geonames.org/countryInfoJSON', [
                'lang' => $locale,
                'username' => $this->username,
            ]);

            if ($response->failed()) {
                return [];
            }

            return collect($response->json()['geonames'] ?? [])
                ->mapWithKeys(fn($item) => [$item['countryCode'] => $item['countryName']])
                ->toArray();
        });
    }

    public function getCities(array $data): array
    {
        $locale = $data['locale'] ?? 'en';
        $countryCode = $data['country_code'] ?? 'US';

        return Cache::remember("external_cities_{$countryCode}_{$locale}", now()->addDay(), function () use ($countryCode, $locale) {
            // Ищем города для конкретной страны
            $response = Http::get('http://api.geonames.org/searchJSON', [
                'country' => $countryCode,
                'featureClass' => 'P', // 'P' означает города/населенные пункты в GeoNames
                'lang' => $locale,
                'maxRows' => 1000,     // Ограничение на количество городов
                'username' => $this->username,
            ]);

            if ($response->failed()) {
                return [];
            }

            return collect($response->json()['geonames'] ?? [])
                ->mapWithKeys(fn($item) => [$item['geonameId'] => $item['name']])
                ->toArray();
        });
    }
}
