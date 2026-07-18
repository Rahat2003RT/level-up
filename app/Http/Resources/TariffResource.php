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
        $locale = $request->header('Accept-Language') ? $request->getPreferredLanguage(['ru', 'en', 'es', 'fr', 'de', 'pt']) : 'en';

        if (!in_array($locale, ['ru', 'en', 'es', 'fr', 'de', 'pt'], true)) {
            $locale = 'en';
        }

        return [
            'id'   => $this->id,
            'role' => $this->role,

            'name'        => $this->name[$locale] ?? $this->name['en'] ?? head($this->name) ?? '',
            'description' => $this->description[$locale] ?? $this->description['en'] ?? head($this->description) ?? null,

            'price'      => $this->price,
            'period'     => $this->period->value,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
