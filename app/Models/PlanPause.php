<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PlanPause
 *
 * --- Свойства (Поля в БД) ---
 * @property int $id
 * @property int $user_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * --- Отношения (Relations) ---
 * @property-read User $user
 *
 * @mixin Builder
 */
final class PlanPause extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'started_at',
        'ended_at',
    ];

    /**
     * Касты для правильного приведения типов дат к Carbon.
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at'   => 'date',
        ];
    }

    /**
     * Связь с пользователем.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
