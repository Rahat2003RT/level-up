<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final readonly class UserService
{
    public function getUsers(array $filters): LengthAwarePaginator
    {
        $query = User::query()->where('role', '!=', UserRole::ADMIN->value);

        if (($filters['status'] ?? null) === 'deleted') {
            $query->onlyTrashed();
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        return $this->applyFiltersAndPaginate($query, $filters);
    }
    public function createUser(array $data): User
    {
        $data['password'] = bcrypt($data['password']);

        return User::create($data);
    }

    public function changeRole(User $user, string $roleValue): User
    {
        $role = UserRole::tryFrom($roleValue);

        if (!$role) {
            throw ValidationException::withMessages([
                'role' => ['Invalid role provided.']
            ]);
        }

        $user->update(['role' => $role]);

        return $user;
    }

    public function changeUser(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function block(User $user, string $reason): User
    {
        $user->update([
            'blocked_at' => now(),
            'block_reason' => $reason,
        ]);

        return $user;
    }

    public function unblock(User $user): User
    {
        $user->update([
            'blocked_at' => null,
            'block_reason' => null,
        ]);

        return $user;
    }

    public function destroy(User $user): void
    {
        $user->delete();
    }

    public function restore(User $user): User
    {
        $user->restore();
        return $user;
    }
    public function forceDelete(User $user): void
    {
        $user->forceDelete();
    }

    private function applyFiltersAndPaginate(Builder $query, array $filters): LengthAwarePaginator
    {
        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }
        if (!empty($filters['query'])) {
            $search = $filters['query'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'ilike', "%$search%")
                    ->orWhere('email', 'ilike', "%$search%")
                    ->orWhere('account_id', 'ilike', "%$search%");
            });
        }
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderSort = $filters['order_sort'] ?? 'desc';
        if ($orderBy === 'date_register') {
            $orderBy = 'created_at';
        }
        $query->orderBy($orderBy, $orderSort);
        return $query->paginate($filters['limit'] ?? 20);
    }
}
