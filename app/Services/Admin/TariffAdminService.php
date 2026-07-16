<?php

namespace App\Services\Admin;

use App\Models\Tariff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TariffAdminService
{
    /**
     * Получить список тарифов с сортировкой и пагинацией.
     *
     * @param array{
     *     order_by?: string,
     *     order_sort?: string,
     *     per_page?: int
     * } $params
     * @return LengthAwarePaginator|Collection
     */
    public function list(array $params = []): LengthAwarePaginator|Collection
    {
        $query = Tariff::query();
        $allowedSortFields = ['id', 'role', 'price', 'period', 'is_active'];

        $orderBy = $params['order_by'] ?? 'role';
        $orderSort = $params['order_sort'] ?? 'asc';

        if (in_array($orderBy, $allowedSortFields, true)) {
            $query->orderBy($orderBy, $orderSort === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('role', 'asc');
        }

        if (isset($params['per_page'])) {
            return $query->paginate((int) $params['per_page']);
        }

        return $query->get();
    }

    /**
     * Создать новый тариф.
     *
     * @param array{
     *     role?: string|null,
     *     name: string,
     *     description?: string|null,
     *     price: float,
     *     period: string,
     *     is_active?: bool
     * } $data
     * @return Tariff
     */
    public function create(array $data): Tariff
    {
        return Tariff::create($data);
    }

    /**
     * Обновить существующий тариф.
     *
     * @param Tariff $tariff
     * @param array{
     *     role?: string|null,
     *     name?: string,
     *     description?: string|null,
     *     price?: float,
     *     period?: string,
     *     is_active?: bool
     * } $data
     * @return Tariff
     */
    public function update(Tariff $tariff, array $data): Tariff
    {
        $tariff->update($data);
        return $tariff;
    }

    /**
     * Удалить тариф.
     *
     * @param Tariff $tariff
     * @return bool|null
     */
    public function delete(Tariff $tariff): ?bool
    {
        return $tariff->delete();
    }
}
