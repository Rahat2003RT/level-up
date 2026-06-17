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
    public function index(array $filters): LengthAwarePaginator
    {
        /** @var Builder|User $query */
        $query = User::query();
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if (!empty($filters['with_deleted'])) {
            $query->withTrashed();
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
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

    public function restore(int $id): User
    {
        /** @var User|null $user */
        $user = User::withTrashed()->find($id);

        if (!$user) {
            throw ValidationException::withMessages([
                'user' => ['User not found.']
            ]);
        }

        $user->restore();

        return $user;
    }

    public function forceDelete(int $id): void
    {
        /** @var User|null $user */
        $user = User::withTrashed()->find($id);

        if (!$user) {
            throw ValidationException::withMessages([
                'user' => ['User not found.']
            ]);
        }

        $user->forceDelete();
    }
}
