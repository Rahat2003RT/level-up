<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProgressResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'current_day_number' => $this['current_day_number'] ?? null,
            'plan_start_date'    => $this['plan_start_date'] ?? null,

            'current_streak'     => $this->when(array_key_exists('current_streak', $this->resource), fn() => $this['current_streak']),
            'wins'               => $this->when(array_key_exists('wins', $this->resource), fn() => $this['wins']),
            'misses'             => $this->when(array_key_exists('misses', $this->resource), fn() => $this['misses']),
            'percentage'         => $this->when(array_key_exists('percentage', $this->resource), fn() => $this['percentage']),

            'players_count'      => $this->when(array_key_exists('players_count', $this->resource), fn() => $this['players_count']),
            'active_count'       => $this->when(array_key_exists('active_count', $this->resource), fn() => $this['active_count']),
            'average_progress'   => $this->when(array_key_exists('average_progress', $this->resource), fn() => $this['average_progress']),
        ];
    }
}
