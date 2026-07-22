<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserStatisticsResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'period_days'            => $this['period_days'] ?? null,
            'total_meetings'         => $this['total_meetings'] ?? 0,
            'avg_meetings'           => $this['avg_meetings'] ?? 0.0,
            'total_clients'          => $this['total_clients'] ?? 0,
            'avg_clients'            => $this['avg_clients'] ?? 0.0,
            'total_partners'         => $this['total_partners'] ?? 0,
            'avg_partners'           => $this['avg_partners'] ?? 0.0,
            'total_sales'            => $this['total_sales'] ?? 0,
            'avg_sales'              => $this['avg_sales'] ?? 0.0,
            'total_income'           => $this['total_income'] ?? 0.0,
            'avg_income'             => $this['avg_income'] ?? 0.0,
            'active_days_count'      => $this['active_days_count'] ?? 0,
            'active_days_percentage' => $this['active_days_percentage'] ?? 0,
            'total_volume'           => $this['total_volume'] ?? 0.0,

            // Если роль - Elite, эти поля закроют блок глобальной статистики:
            'total_leaders'          => $this['total_leaders'] ?? null,
            'active_leaders'         => $this['active_leaders'] ?? null,
            'total_team_volume'      => $this['total_team_volume'] ?? null,
        ];
    }
}
