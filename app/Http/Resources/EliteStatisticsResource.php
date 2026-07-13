<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $total_leaders
 * @property-read int $active_leaders
 * @property-read float $total_team_volume
 */
final class EliteStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_leaders' => (int) $this->resource['total_leaders'],
            'active_leaders' => (int) $this->resource['active_leaders'],
            'total_team_volume' => (float) $this->resource['total_team_volume'],
        ];
    }
}
