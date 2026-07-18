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

        // Защита от строк: если пришла строка (например, из-за особенностей SQLite в тестах),
        // превращаем её в массив, чтобы head() и isset не бросали исключения.
        $name = is_array($this->name) ? $this->name : ['en' => $this->name];
        $description = is_array($this->description) ? $this->description : ['en' => $this->description];

        return [
            'id'   => $this->id,
            'role' => $this->role,

            'name'        => $name[$locale] ?? $name['en'] ?? head($name) ?? '',
            'description' => $description[$locale] ?? $description['en'] ?? head($description) ?? null,

            'price'      => $this->price,
            'period'     => $this->period->value,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
