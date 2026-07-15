<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $period_days
 * @property-read int $players_count
 * @property-read int $active_players_count
 * @property-read float $total_volume
 */
final class LeaderTeamStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'period_days' => (int) $this->resource['period_days'],
            'players_count' => $this->resource['players_count'],
            'active_players_count' => $this->resource['active_players_count'],
            'total_volume' => $this->resource['total_volume'],
        ];
    }
}
