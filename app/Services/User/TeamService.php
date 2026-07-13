<?php

namespace App\Services\User;

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\TeamInvitation;
use App\Models\TeamPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class TeamService
{
    /**
     * @throws AccessDeniedHttpException
     */
    public function generateInviteLink(User $user): string
    {
        $role = $user->role;

        if ($role !== UserRole::ELITE && $role !== UserRole::LEADER) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        $invitation = TeamInvitation::updateOrCreate(
            ['leader_id' => $user->id],
            [
                'token' => Str::random(32),
                'expires_at' => Carbon::now()->addHours(3),
            ]
        );

        return config('app.url') . "/team/" . $invitation->token;
    }

    /**
     * @throws ValidationException
     */
    public function getTeamDataByToken(User $user, string $token): array
    {
        $invitation = TeamInvitation::with('leader')->where('token', $token)->first();

        if (!$invitation) {
            throw ValidationException::withMessages(['token' => 'Invitation not found.']);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => 'Link has expired.']);
        }

        $this->validateHierarchyRules($user, $invitation);

        return [
            'name' => $invitation->leader->name,
            'role' => $invitation->leader->role?->value,
        ];
    }

    /**
     * @throws ValidationException
     */
    public function handleInvitation(User $user, string $token, bool $accept): array
    {
        $invitation = TeamInvitation::with('leader')->where('token', $token)->first();

        if (!$invitation || $invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => 'The link is invalid or expired.']);
        }
        $this->validateHierarchyRules($user, $invitation);

        if ($accept) {
            $user->update(['leader_id' => $invitation->leader_id]);

            return [
                'status' => 'accepted',
                'message' => 'You have successfully joined the team.'
            ];
        }

        return [
            'status' => 'declined',
            'message' => 'You declined the invitation.'
        ];
    }

    /**
     * @throws ValidationException
     */
    private function validateHierarchyRules(User $user, TeamInvitation $invitation): void
    {
        $inviter = $invitation->leader;

        if ($user->role === UserRole::ELITE) {
            throw ValidationException::withMessages(['role' => 'Elite users cannot join any team.']);
        }

        if ($user->role === UserRole::PLAYER && $inviter->role !== UserRole::LEADER) {
            throw ValidationException::withMessages(['role' => 'Players can only join a Leader\'s team.']);
        }

        if ($user->role === UserRole::LEADER && $inviter->role !== UserRole::ELITE) {
            throw ValidationException::withMessages(['role' => 'Leaders can only join an Elite\'s team.']);
        }

        if ($invitation->leader_id === $user->id) {
            throw ValidationException::withMessages(['team' => 'You cannot join your own team.']);
        }

        if ($user->leader_id === $invitation->leader_id) {
            throw ValidationException::withMessages(['team' => 'You are already a member of this team.']);
        }

        if (!is_null($user->leader_id)) {
            throw ValidationException::withMessages(['team' => 'You are already a member of a team. Leave your current team first.']);
        }
    }

    public function getTeamMembers(User $user, array $filters): LengthAwarePaginator
    {
        return match ($user->role) {
            UserRole::ELITE => $this->getMembersForElite($user, $filters),
            UserRole::LEADER => $this->getMembersForLeader($user, $filters),
            default => throw new AccessDeniedHttpException('Forbidden.'),
        };
    }

    private function getMembersForLeader(User $leader, array $filters): LengthAwarePaginator
    {
        $todayStr = Carbon::today()->toDateString();
        $perPage = $filters['per_page'] ?? 20;

        $playersPaginator = $leader->players()
            ->where('role', UserRole::PLAYER)
            ->when($filters['query'] ?? null, function ($query, $search) {
                $searchLower = mb_strtolower($search, 'UTF-8');
                $query->whereRaw('LOWER(name) LIKE ?', ["%$searchLower%"]);
            })
            ->withCount([
                'contacts as clients_count' => fn($q) => $q->where('type', 'client'),
                'contacts as partners_count' => fn($q) => $q->where('type', 'partner')
            ])
            ->withSum('contacts as total_volume', 'volume')
            ->with(['checklists' => fn($q) => $q->where('date', $todayStr)])
            ->latest()
            ->paginate($perPage);

        $transformedItems = collect($playersPaginator->items())->map(function ($player) use ($todayStr) {
            $todayChecklist = $player->checklists->first();

            if ($todayChecklist) {
                $currentDayNumber = $todayChecklist->day_number;
                $isCompletedToday = (bool)$todayChecklist->is_completed;
            } else {
                $maxDay = $player->checklists()->max('day_number') ?? 0;
                $currentDayNumber = $maxDay + 1;
                $isCompletedToday = false;
            }

            $currentDayNumber = min(90, $currentDayNumber);
            $progressPercent = round(($currentDayNumber / 90) * 100);

            return [
                'id' => $player->id,
                'name' => $player->name . ' ' . $player->surname,
                'avatar' => $player->avatar_path ?? null,
                'role' => UserRole::PLAYER->value,
                'current_day_number' => $currentDayNumber,
                'progress' => $progressPercent,
                'is_active'            => $isCompletedToday,

                'total_players_count' => 0,
                'active_players_count' => 0,

                'monthly_volume' => $player->total_volume ?? 0,
                'clients_count' => $player->clients_count,
                'partners_count' => $player->partners_count,
            ];
        });

        return new LengthAwarePaginator(
            $transformedItems,
            $playersPaginator->total(),
            $playersPaginator->perPage(),
            $playersPaginator->currentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    private function getMembersForElite(User $elite, array $filters): LengthAwarePaginator
    {
        $todayStr = Carbon::today()->toDateString();
        $perPage = $filters['per_page'] ?? 20;

        $leadersPaginator = $elite->players()
            ->where('role', UserRole::LEADER)
            ->when($filters['query'] ?? null, function ($query, $search) {
                $searchLower = mb_strtolower($search, 'UTF-8');
                $query->whereRaw('LOWER(name) LIKE ?', ["%$searchLower%"]);
            })
            ->with([
                'leadershipChecklists' => fn($q) => $q->latest('date'),
                'players.checklists'
            ])
            ->latest('created_at')
            ->paginate($perPage);

        $transformedItems = collect($leadersPaginator->items())->map(function ($leader) use ($todayStr) {
            $lastLeaderChecklist = $leader->leadershipChecklists->first();
            $todayLeaderChecklist = $leader->leadershipChecklists->first(fn($c) => $c->date->toDateString() === $todayStr);

            $currentDayNumber = $todayLeaderChecklist
                ? $todayLeaderChecklist->day_number
                : ($leader->leadershipChecklists->max('day_number') ?? 0) + 1;

            $currentDayNumber = min(90, $currentDayNumber);
            $progressPercent = round(($currentDayNumber / 90) * 100);

            $isActiveLeader = $lastLeaderChecklist && $lastLeaderChecklist->is_completed && !($lastLeaderChecklist->is_day_off ?? false);

            $players = $leader->players;
            $totalPlayers = $players->count();
            $activePlayersCount = 0;

            foreach ($players as $player) {
                $playerChecklistsCount = $player->checklists->count();
                $playerTodayChecklist = $player->checklists->first(fn($c) => $c->date->toDateString() === $todayStr);
                $pDay = $playerTodayChecklist ? $playerTodayChecklist->day_number : $player->checklists->max('day_number') ?? 0;

                if ($playerChecklistsCount > 0 && $playerChecklistsCount >= $pDay) {
                    $activePlayersCount++;
                }
            }

            $leaderPlayerIds = $players->pluck('id')->toArray();
            $allTeamUserIds = array_merge([$leader->id], $leaderPlayerIds);
            $monthlyVolume = (int)Contact::whereIn('user_id', $allTeamUserIds)->sum('volume');

            return [
                'id' => $leader->id,
                'name' => $leader->name . ' ' . $leader->surname,
                'avatar' => $leader->avatar_path ?? null,
                'role' => UserRole::LEADER->value,
                'current_day_number' => $currentDayNumber,
                'progress' => $progressPercent,
                'is_active'            => $isActiveLeader,

                'total_players_count' => $totalPlayers,
                'active_players_count' => $activePlayersCount,

                'monthly_volume' => $monthlyVolume,
                'clients_count' => 0,
                'partners_count' => 0,
            ];
        });

        return new LengthAwarePaginator(
            $transformedItems,
            $leadersPaginator->total(),
            $leadersPaginator->perPage(),
            $leadersPaginator->currentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    public function getTeamPlan(User $user): Model
    {
        $leaderId = $user->isLeader() ? $user->id : $user->leader_id;

        $defaultPlanValues = [
            'daily_calls' => 0,
            'daily_meetings' => 0,
            'business_conversations' => 0,
            'presentations' => 0,
            'social_media_posts' => 0,
            'new_clients_per_week' => 0,
            'new_partners_per_week' => 0,
            'daily_volume_points' => 0,
        ];

        if (!$leaderId) {
            return new TeamPlan($defaultPlanValues);
        }

        return TeamPlan::firstOrCreate(
            ['user_id' => $leaderId],
            $defaultPlanValues
        );
    }

    /**
     * @throws AccessDeniedHttpException
     */
    public function updateTeamPlan(User $user, array $data): Model
    {
        if (!$user->isLeader()) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        return TeamPlan::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );
    }

    /**
     * @throws ValidationException
     */
    public function leaveCurrentTeam(User $user): bool
    {
        if (is_null($user->leader_id)) {
            throw ValidationException::withMessages([
                'team' => 'You are not a member of any team.'
            ]);
        }

        if ($user->isElite()) {
            throw ValidationException::withMessages([
                'team' => 'Users with Elite status cannot leave the team.'
            ]);
        }

        return $user->update(['leader_id' => null]);
    }

    /**
     * @throws AccessDeniedHttpException|ValidationException
     */
    public function removeMemberFromTeam(User $currentUser, User $member): bool
    {
        if ($currentUser->isPlayer()) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        if ($member->leader_id !== $currentUser->id) {
            throw ValidationException::withMessages([
                'member' => 'This user is not on your team.'
            ]);
        }

        return $member->update(['leader_id' => null]);
    }
}
