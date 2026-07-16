<?php

namespace App\Models;

use App\Enums\Period;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Tariff
 *
 * @package App\Models
 *
 * @property int $id Уникальный идентификатор тарифа
 * @property string|null $role Роль, к которой привязан тариф (например, 'admin', 'user')
 * @property string $name Название тарифа
 * @property string|null $description Описание тарифа и его преимуществ
 * @property float $price Стоимость тарифа
 * @property Period $period Период действия тарифа (enum класс)
 * @property bool $is_active Статус активности тарифа (активирован/деактивирован)
 * @property Carbon|null $created_at Дата и время создания записи
 * @property Carbon|null $updated_at Дата и время последнего обновления записи
 *
 * @method static Builder|Tariff newModelQuery()
 * @method static Builder|Tariff newQuery()
 * @method static Builder|Tariff query()
 * @method static Builder|Tariff active() Фильтр только активных тарифов
 * @method static Builder|Tariff whereId($value)
 * @method static Builder|Tariff whereRole($value)
 * @method static Builder|Tariff whereName($value)
 * @method static Builder|Tariff whereDescription($value)
 * @method static Builder|Tariff wherePrice($value)
 * @method static Builder|Tariff wherePeriod($value)
 * @method static Builder|Tariff whereIsActive($value)
 * @method static Builder|Tariff whereCreatedAt($value)
 * @method static Builder|Tariff whereUpdatedAt($value)
 */
class Tariff extends Model
{
    /**
     * Атрибуты, для которых разрешено массовое заполнение.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role',
        'name',
        'description',
        'price',
        'period',
        'is_active',
    ];

    /**
     * Атрибуты, которые должны быть приведены к типам PHP.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'period' => Period::class,
        'is_active' => 'boolean',
    ];

    /**
     * Локальный Scope для фильтрации только активных тарифов.
     *
     * Использование: Tariff::active()->get();
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
