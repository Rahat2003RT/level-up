<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DailyChecklistResource extends JsonResource
{
    /**
     * Преобразует ресурс в массив.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $progressData = data_get($this->resource, 'progress', [
            'current_streak' => 0,
            'wins' => 0,
            'misses' => 0,
            'percentage' => 0,
        ]);

        return [
            'date'                       => $this['date'] ?? $this->date,
            'day_number'                 => (int) ($this['day_number'] ?? $this->day_number),
            'is_completed'               => (bool) ($this['is_completed'] ?? $this->is_completed),
            'is_day_off'                 => (bool) ($this['is_day_off'] ?? $this->is_day_off),
            'scheduled_meetings'         => (int) ($this['scheduled_meetings'] ?? $this->scheduled_meetings),
            'completed_meetings'         => (int) ($this['completed_meetings'] ?? $this->completed_meetings),
            'new_clients'                => (int) ($this['new_clients'] ?? $this->new_clients),
            'new_partners'               => (int) ($this['new_partners'] ?? $this->new_partners),
            'business_conversations'     => (int) ($this['business_conversations'] ?? $this->business_conversations),
            'presentations'              => (int) ($this['presentations'] ?? $this->presentations),
            'sales'                      => (int) ($this['sales'] ?? $this->sales),
            'daily_income'               => (float) ($this['daily_income'] ?? $this->daily_income),
            'social_media_activity'      => (bool) ($this['social_media_activity'] ?? $this->social_media_activity),
            'communication_with_sponsor' => (bool) ($this['communication_with_sponsor'] ?? $this->communication_with_sponsor),
            'notes_for_the_day'          => (string) ($this['notes_for_the_day'] ?? $this->notes_for_the_day ?? ''),
            'progress' => [
                'current_streak'   => (int) data_get($progressData, 'current_streak', 0),
                'wins'             => (int) data_get($progressData, 'wins', 0),
                'misses'            => (int) data_get($progressData, 'loses', 0),
                'percentage'       => (float) data_get($progressData, 'percentage', 0.0),
            ],
            'is_editable' => is_array($this->resource)
                ? $this->resource['is_editable']
                : $this->isEditable(),
        ];
    }
}
