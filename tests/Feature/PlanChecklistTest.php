<?php

namespace Tests\Feature;

use App\Models\DailyChecklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем обычного пользователя для тестов
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_toggle_day_off_on_and_off()
    {
        $date = Carbon::today()->addDay()->toDateString();

        // 1. Включаем выходной
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/plan/checklist/toggle-day-off', ['date' => $date]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'added', 'date' => $date]);

        $this->assertDatabaseHas('daily_checklists', [
            'user_id' => $this->user->id,
            'date' => $date,
            'is_day_off' => true,
            'day_number' => 0
        ]);

        // 2. Выключаем этот же выходной (удаляем)
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/plan/checklist/toggle-day-off', ['date' => $date]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'removed', 'date' => $date]);

        $this->assertDatabaseMissing('daily_checklists', [
            'user_id' => $this->user->id,
            'date' => $date
        ]);
    }

    /** @test */
    public function user_cannot_set_day_off_for_past_date_beyond_earliest_earth_timezone()
    {
        // Дата явно в прошлом для любой точки Земли
        $pastDate = Carbon::today()->subDays(5)->toDateString();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/plan/checklist/toggle-day-off', ['date' => $pastDate]);

        $response->assertStatus(422)
            ->assertJson(['error' => 'You cannot change a day off to a past date.']);
    }

    /** @test */
    public function user_cannot_set_day_off_more_than_120_days_in_advance()
    {
        // Дата далеко в будущем
        $futureDate = Carbon::today()->addDays(125)->toDateString();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/plan/checklist/toggle-day-off', ['date' => $futureDate]);

        $response->assertStatus(422)
            ->assertJson(['error' => 'You cannot schedule a day off more than 120 days in advance.']);
    }

    /** @test */
    public function user_cannot_exceed_30_days_off_limit()
    {
        // Создаем 30 выходных в базе данных
        for ($i = 1; $i <= 30; $i++) {
            DailyChecklist::create([
                'user_id' => $this->user->id,
                'date' => Carbon::today()->addDays($i)->toDateString(),
                'is_day_off' => true,
                'is_completed' => false,
                'day_number' => 0
            ]);
        }

        // Пытаемся добавить 31-й выходной
        $nextDate = Carbon::today()->addDays(31)->toDateString();
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/plan/checklist/toggle-day-off', ['date' => $nextDate]);

        $response->assertStatus(422)
            ->assertJson(['error' => 'The limit of days off (30 days) has been exhausted.']);
    }

    /** @test */
    public function user_cannot_store_checklist_on_a_day_off()
    {
        $today = Carbon::today()->toDateString();

        // Делаем сегодняшний день выходным
        DailyChecklist::create([
            'user_id' => $this->user->id,
            'date' => $today,
            'is_day_off' => true,
            'is_completed' => false,
            'day_number' => 0
        ]);

        // Пытаемся отправить заполненный чек-лист за сегодня
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/plan/checklist', [
                'scheduled_meetings' => 2,
                'completed_meetings' => 1,
                // ... добавьте другие обязательные поля из вашего StoreRequest, если они есть
            ]);

        $response->assertStatus(403); // AuthorizationException возвращает 403 HTTP статус
    }

    /** @test */
    public function user_can_get_all_days_off()
    {
        $date1 = Carbon::today()->addDay()->toDateString();
        $date2 = Carbon::today()->addDays(2)->toDateString();

        DailyChecklist::create([
            'user_id' => $this->user->id,
            'date' => $date1,
            'is_day_off' => true,
            'is_completed' => false,
            'day_number' => 0
        ]);

        DailyChecklist::create([
            'user_id' => $this->user->id,
            'date' => $date2,
            'is_day_off' => true,
            'is_completed' => false,
            'day_number' => 0
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/plan/checklist/days-off');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJson(['data' => [$date1, $date2]]);
    }
}
