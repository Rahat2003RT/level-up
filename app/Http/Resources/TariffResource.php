<?php

namespace App\Http\Resources;

use App\Models\Tariff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tariff
 */
final class TariffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $nameArray = is_array($this->name) ? $this->name : json_decode((string)$this->name, true) ?? [];
        $descriptionArray = is_array($this->description) ? $this->description : json_decode((string)$this->description, true) ?? [];

        if ($request->is('api/v1/admin/*') || $request->is('api/admin/*')) {
            $nameOutput = $nameArray;
            $descriptionOutput = $descriptionArray;
        } else {
            $locale = $request->header('Accept-Language')
                ? $request->getPreferredLanguage(['ru', 'en', 'es', 'fr', 'de', 'pt'])
                : 'en';

            if (!in_array($locale, ['ru', 'en', 'es', 'fr', 'de', 'pt'], true)) {
                $locale = 'en';
            }

            $nameOutput = $nameArray[$locale] ?? $nameArray['en'] ?? head($nameArray) ?? '';
            $descriptionOutput = $descriptionArray[$locale] ?? $descriptionArray['en'] ?? head($descriptionArray) ?? null;
        }

        return [
            'id'   => $this->id,
            'role' => $this->role?->value ?? $this->role,

            'name'        => $nameOutput,
            'description' => $descriptionOutput,

            'price'      => $this->price,
            'period'     => $this->period->value ?? $this->period,
            'is_active'  => $this->is_active,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
