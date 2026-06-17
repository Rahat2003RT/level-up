<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', config('app.fallback_locale'));

        $title = $this->title[$locale] ?? $this->title[config('app.fallback_locale')] ?? collect($this->title)->first();
        $description = $this->description[$locale] ?? $this->description[config('app.fallback_locale')] ?? collect($this->description)->first();

        return [
            'id' => $this->id,
            'type' => $this->type ?? null,
            'title' => $title,
            'description' => $description,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at,
        ];
    }
}
