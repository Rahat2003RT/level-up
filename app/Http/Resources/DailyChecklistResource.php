<?php

namespace App\Http\Resources;

use App\Models\DailyChecklist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DailyChecklist
 */
final class DailyChecklistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date'                       => $this->date,
            'day_number'                 => $this->day_number,
            'is_completed'               => $this->is_completed,
            'is_day_off'                 => $this->is_day_off,
            'scheduled_meetings'         => $this->scheduled_meetings,
            'completed_meetings'         => $this->completed_meetings,
            'new_clients'                => $this->new_clients,
            'new_partners'               => $this->new_partners,
            'business_conversations'     => $this->business_conversations,
            'presentations'              => $this->presentations,
            'sales'                      => $this->sales,
            'daily_income'               => (float) $this->daily_income,
            'social_media_activity'      => $this->social_media_activity,
            'communication_with_sponsor' => $this->communication_with_sponsor,
            'notes_for_the_day'          => $this->notes_for_the_day ?? '',
            'is_editable'                => $this->is_editable ?? $this->isEditable(),
        ];
    }
}
