<?php
namespace App\Http\Controllers\Api\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Checklist\StoreDailyChecklistRequest;
use App\Models\DailyChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DailyChecklistController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        $checklist = DailyChecklist::where('user_id', $request->user()->id)
            ->where('date', $date)
            ->first();

        if (!$checklist && $date === Carbon::today()->toDateString()) {
            $nextDayNumber = DailyChecklist::where('user_id', $request->user()->id)->count() + 1;

            return response()->json([
                'data' => [
                    'date' => $date,
                    'day_number' => $nextDayNumber,
                    'is_completed' => false,
                    'is_day_off' => false,
                    'scheduled_meetings' => 0,
                    'completed_meetings' => 0,
                    'new_clients' => 0,
                    'new_partners' => 0,
                    'business_conversations' => 0,
                    'presentations' => 0,
                    'sales' => 0,
                    'daily_income' => 0,
                    'social_media_activity' => false,
                    'communication_with_sponsor' => false,
                    'plans_for_the_day' => '',
                    'results_for_the_day' => '',
                    'notes_for_the_day' => '',
                ]
            ]);
        }

        if (!$checklist) {
            return response()->json(['message' => 'Данные за указанный день отсутствуют.'], 404);
        }

        return response()->json(['data' => $checklist]);
    }

    public function storeOrUpdate(StoreDailyChecklistRequest $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $checklist = DailyChecklist::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($checklist && !$checklist->isEditable()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Редактирование закрытого дня запрещено.'
            ], 403);
        }

        $nextDayNumber = $checklist ? $checklist->day_number : (DailyChecklist::where('user_id', $user->id)->count() + 1);

        $data = $request->validated();

        $checklist = DailyChecklist::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            array_merge($data, ['day_number' => $nextDayNumber])
        );

        return response()->json(['data' => $checklist]);
    }

    public function complete(Request $request): JsonResponse
    {
        $checklist = DailyChecklist::where('user_id', $request->user()->id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        if (!$checklist) {
            return response()->json([
                'status' => 'error',
                'message' => 'Сначала заполните или сохраните данные за сегодня.'], 400);
        }

        if (!$checklist->isEditable()) {
            return response()->json(['message' => 'День уже закрыт или является выходным.'], 403);
        }

        $checklist->update(['is_completed' => true]);

        // TODO: Здесь вызываем Event/Job для пересчета достижений и статистики (за 10/30/90 дней)

        return response()->json(['message' => 'День успешно завершен!', 'data' => $checklist]);
    }

    public function setDayOff(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $checklist = DailyChecklist::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($checklist && !$checklist->isEditable()) {
            return response()->json(['message' => 'Изменение статуса невозможно.'], 403);
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

        return response()->json(['data' => $checklist]);
    }
}
