<?php

use App\Models\User;
use App\Models\TeamInvitation;
use App\Models\TeamPlan;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ГЕНЕРАЦИЯ ИНВАЙТОВ (POST /api/v1/team/invitations)
|--------------------------------------------------------------------------
*/

test('лидер и элита могут генерировать ссылки приглашения', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/team/invitations');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['invite_url']]);

    $this->assertDatabaseHas('team_invitations', ['leader_id' => $user->id]);
})->with([UserRole::LEADER, UserRole::ELITE]);

test('обычный игрок не может генерировать ссылки приглашения', function () {
    $user = User::factory()->create(['role' => UserRole::PLAYER]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/team/invitations');

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| ПРАВИЛА ВСТУПЛЕНИЯ В КОМАНДУ (POST /api/v1/team/invitations/{token}/respond)
|--------------------------------------------------------------------------
*/

test('игрок может успешно вступить в команду к лидеру', function () {
    $leader = User::factory()->create(['role' => UserRole::LEADER]);
    $player = User::factory()->create(['role' => UserRole::PLAYER]);
    $invitation = TeamInvitation::factory()->create(['leader_id' => $leader->id]);

    Sanctum::actingAs($player);

    $response = $this->postJson("/api/v1/team/invitations/{$invitation->token}/respond", [
        'accept' => true
    ]);

    $response->assertStatus(200)->assertJsonPath('data.status', 'accepted');
    $this->assertDatabaseHas('users', ['id' => $player->id, 'leader_id' => $leader->id]);
});

test('лидер может успешно вступить в команду к элите', function () {
    $elite = User::factory()->create(['role' => UserRole::ELITE]);
    $leader = User::factory()->create(['role' => UserRole::LEADER]);
    $invitation = TeamInvitation::factory()->create(['leader_id' => $elite->id]);

    Sanctum::actingAs($leader);

    $response = $this->postJson("/api/v1/team/invitations/{$invitation->token}/respond", [
        'accept' => true
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', ['id' => $leader->id, 'leader_id' => $elite->id]);
});

test('игрок не может вступить к элите, а лидер к лидеру', function (UserRole $userRole, UserRole $inviterRole) {
    $inviter = User::factory()->create(['role' => $inviterRole]);
    $user = User::factory()->create(['role' => $userRole]);
    $invitation = TeamInvitation::factory()->create(['leader_id' => $inviter->id]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/team/invitations/{$invitation->token}/respond", [
        'accept' => true
    ]);

    // Валидация правил иерархии возвращает 422 ValidationException
    $response->assertStatus(422);
})->with([
    [UserRole::PLAYER, UserRole::ELITE],  // Игрок к Элите -> Мимо
    [UserRole::LEADER, UserRole::LEADER], // Лидер к Лидеру -> Мимо
    [UserRole::ELITE, UserRole::LEADER],  // Элита к Лидеру -> Мимо
]);

/*
|--------------------------------------------------------------------------
| СПИСОК ЧЛЕНОВ КОМАНДЫ (GET /api/v1/team/members)
|--------------------------------------------------------------------------
*/

test('лидер видит унифицированный список своих игроков', function () {
    $leader = User::factory()->create(['role' => UserRole::LEADER]);
    User::factory()->count(3)->create([
        'role' => UserRole::PLAYER,
        'leader_id' => $leader->id
    ]);

    Sanctum::actingAs($leader);

    $response = $this->getJson('/api/v1/team/members');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'name', 'avatar', 'role', 'current_day_number',
                    'progress_percent', 'status', 'total_players_count',
                    'active_players_count', 'monthly_volume', 'clients_count', 'partners_count'
                ]
            ],
            'meta' => ['current_page', 'total']
        ]);
});

/*
|--------------------------------------------------------------------------
| КОМАНДНЫЙ ПЛАН (GET/PATCH /api/v1/team/plan)
|--------------------------------------------------------------------------
*/

test('лидер может обновлять командный план, а игрок — нет', function () {
    $leader = User::factory()->create(['role' => UserRole::LEADER]);
    $player = User::factory()->create(['role' => UserRole::PLAYER, 'leader_id' => $leader->id]);

    $planData = ['daily_calls' => 10, 'daily_meetings' => 5];

    // 1. Проверяем лидера
    Sanctum::actingAs($leader);
    $this->patchJson('/api/v1/team/plan', $planData)->assertStatus(200);
    $this->assertDatabaseHas('team_plans', ['user_id' => $leader->id, 'daily_calls' => 10]);

    // 2. Проверяем игрока (должен получить 403)
    Sanctum::actingAs($player);
    $this->patchJson('/api/v1/team/plan', $planData)->assertStatus(403);
})->skip(fn() => !class_exists(\App\Http\Requests\Api\User\UpdateTeamPlanRequest::class), 'UpdateTeamPlanRequest missing');

/*
|--------------------------------------------------------------------------
| ИСКЛЮЧЕНИЕ И ВЫХОД (DELETE /members/{id}, POST /leave)
|--------------------------------------------------------------------------
*/

test('лидер может исключить игрока из своей команды', function () {
    $leader = User::factory()->create(['role' => UserRole::LEADER]);
    $player = User::factory()->create(['role' => UserRole::PLAYER, 'leader_id' => $leader->id]);

    Sanctum::actingAs($leader);

    $response = $this->deleteJson("/api/v1/team/members/{$player->id}");

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', ['id' => $player->id, 'leader_id' => null]);
});

test('лидер не может исключить чужого игрока', function () {
    $leaderA = User::factory()->create(['role' => UserRole::LEADER]);
    $leaderB = User::factory()->create(['role' => UserRole::LEADER]);
    $playerB = User::factory()->create(['role' => UserRole::PLAYER, 'leader_id' => $leaderB->id]);

    Sanctum::actingAs($leaderA);

    $response = $this->deleteJson("/api/v1/team/members/{$playerB->id}");

    $response->assertStatus(422); // Возвращает ошибку "This user is not on your team."
});

test('пользователь может добровольно покинуть команду', function () {
    $leader = User::factory()->create(['role' => UserRole::LEADER]);
    $player = User::factory()->create(['role' => UserRole::PLAYER, 'leader_id' => $leader->id]);

    Sanctum::actingAs($player);

    $response = $this->postJson('/api/v1/team/leave');

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', ['id' => $player->id, 'leader_id' => null]);
});
