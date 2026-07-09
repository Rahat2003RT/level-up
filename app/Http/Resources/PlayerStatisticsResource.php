<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PlayerStatisticsResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'period_days' => (int) $this['period_days'],
            'meetings' => [
                'total' => (int) $this['total_meetings'],
                'avg'   => round($this['avg_meetings'], 1),
            ],
            'clients' => [
                'total' => (int) $this['total_clients'],
                'avg'   => round($this['avg_clients'], 1),
            ],
            'partners' => [
                'total' => (int) $this['total_partners'],
                'avg'   => round($this['avg_partners'], 2),
            ],
            'sales' => [
                'total' => (int) $this['total_sales'],
                'avg'   => round($this['avg_sales'], 1),
            ],
            'income' => [
                'total' => (float) $this['total_income'],
                'avg'   => round($this['avg_income']),
            ],
            'active_days' => [
                'total' => (int) $this['active_days_count'],
                'percentage' => round($this['active_days_percentage']),
            ],
            'total_volume' => (float) $this['total_volume'],
        ];
    }
}
