<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyChecklistResource extends JsonResource
{
    /**
     * Преобразует ресурс в массив.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
            'plans_for_the_day'          => (string) ($this['plans_for_the_day'] ?? $this->plans_for_the_day ?? ''),
            'results_for_the_day'        => (string) ($this['results_for_the_day'] ?? $this->results_for_the_day ?? ''),
            'notes_for_the_day'          => (string) ($this['notes_for_the_day'] ?? $this->notes_for_the_day ?? ''),
        ];
    }
}
