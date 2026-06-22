<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\User;
use App\Models\DailyChecklist;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ChecklistService
{
    public function checkEditAvailability(User $user, Carbon $date): array
    {
        if ($date->isBefore(Carbon::today($user->timezone))) {
            return ['allowed' => false, 'reason' => 'past_day_editing_forbidden'];
        }

        $checklist = DailyChecklist::where('user_id', $user->id)
            ->where('date', $date->format('Y-m-d'))
            ->first();

        if ($checklist && $checklist->is_completed) {
            return ['allowed' => false, 'reason' => 'day_already_completed'];
        }

        return ['allowed' => true, 'reason' => 'editable'];
    }


    public function saveCurrentChecklist(User $user, array $data): DailyChecklist
    {

        $today = Carbon::today($user->timezone);

        $availability = $this->checkEditAvailability($user, $today);
        if (!$availability['allowed']) {
            throw ValidationException::withMessages([
                'checklist' => ["Editing is blocked: {$availability['reason']}"]
            ]);
        }
        $dayNumber = $user->dailyChecklists()->count() + 1;

        return DailyChecklist::updateOrCreate(
            [
                'user_id' => $user->id,
                'date'    => $today->format('Y-m-d'),
            ],
            array_merge($data, [
                'day_number' => $dayNumber
            ])
        );
    }
}
