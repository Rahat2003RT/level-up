<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserAvailableResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'name'   => trim("$this->name $this->surname"),
            'avatar' => $this->avatar_path,
            'role'   => $this->role?->value,
        ];
    }
}
