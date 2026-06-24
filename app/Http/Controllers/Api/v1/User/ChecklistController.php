<?php
namespace App\Http\Controllers\Api\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Checklist\StoreDailyChecklistRequest;
use App\Http\Resources\DailyChecklistResource;
use App\Models\DailyChecklist;
use App\Services\User\ChecklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

final class ChecklistController extends Controller
{

    protected function __construct(
        protected ChecklistService $service
    )
    {

    }
    public function show(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        $userId = $request->user()->id;

        $checklist = DailyChecklist::where('user_id', $userId)
            ->where('date', $date)
            ->first();

        if (!$checklist && $date === Carbon::today()->toDateString()) {
            $nextDayNumber = DailyChecklist::where('user_id', $userId)->count() + 1;

            return new DailyChecklistResource([
                'date' => $date,
                'day_number' => $nextDayNumber,
            ]);
        }

        if (!$checklist) {
            return response()->noContent(404);
        }
        return DailyChecklistResource::make($checklist);
    }

    public function storeOrUpdate(StoreDailyChecklistRequest $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $checklist = DailyChecklist::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // Если редактировать нельзя, отдаем просто 403 статус (без текста)
        if ($checklist && !$checklist->isEditable()) {
            return response()->noContent(403);
        }

        $nextDayNumber = $checklist ? $checklist->day_number : (DailyChecklist::where('user_id', $user->id)->count() + 1);

        $data = $request->validated();

        $checklist = DailyChecklist::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            array_merge($data, ['day_number' => $nextDayNumber])
        );

        return new DailyChecklistResource($checklist);
    }

    public function complete(Request $request)
    {
        $checklist = DailyChecklist::where('user_id', $request->user()->id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        // Сначала нужно создать запись, иначе 400 Bad Request
        if (!$checklist) {
            return response()->noContent(400);
        }

        // Если нельзя редактировать — 403 Forbidden
        if (!$checklist->isEditable()) {
            return response()->noContent(403);
        }

        $checklist->update(['is_completed' => true]);

        // TODO: Здесь вызываем Event/Job для пересчета достижений и статистики (за 10/30/90 дней)

        return DailyChecklistResource::make($checklist);
    }

    public function setDayOff(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $checklist = DailyChecklist::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($checklist && !$checklist->isEditable()) {
            return response()->noContent(403);
        }

        $nextDayNumber = $checklist ? $checklist->day_number : (DailyChecklist::where('user_id', $user->id)->count() + 1);

        $checklist = DailyChecklist::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            [
                'day_number' => $nextDayNumber,
                'is_day_off' => true,
                'is_completed' => false,
                'scheduled_meetings' => 0,
                'completed_meetings' => 0,
                'new_clients' => 0,
                'new_partners' => 0,
                'business_conversations' => 0,
                'presentations' => 0,
                'sales' => 0,
                'daily_income' => 0,
                'social_media_activity' => false,
                'communication_with_sponsor' => false
            ]
        );

        return new DailyChecklistResource($checklist);
    }
}
