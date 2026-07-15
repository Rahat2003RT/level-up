<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ChatService
{
    /**
     * @param User $user
     * @param array{query?: string, per_page?: int} $filters
     * @return LengthAwarePaginator|Collection
     */
    public function getChatsForUser(User $user, array $filters = []): LengthAwarePaginator|Collection
    {
        if ($user->isLeader()) {
            $chat = Chat::query()
                ->where('leader_id', $user->id)
                ->with(['elite', 'lastMessage'])
                ->first();

            return $chat ? collect([$chat]) : collect();
        }

        if ($user->isElite()) {
            $searchString = $filters['query'] ?? null;
            $perPage = (int)($filters['per_page'] ?? 20);

            return Chat::query()
                ->where('elite_id', $user->id)
                ->with(['leader', 'lastMessage'])
                ->when($searchString, function ($query, string $search) {
                    $query->whereHas('leader', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%")
                            ->orWhere('surname', 'like', "%$search%")
                            ->orWhere('nickname', 'like', "%$search%");
                    });
                })
                ->latest('updated_at')
                ->paginate($perPage);
        }

        return collect();
    }
}
