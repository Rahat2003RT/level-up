<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserGoal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserGoal
 */
final class UserGoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'target_clients_count' => $this->target_clients_count,
            'target_partners_count' => $this->target_partners_count,
            'target_sales_volume' => $this->target_sales_volume,
        ];
    }
}
