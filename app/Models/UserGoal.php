<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Class UserGoal
 * @package App\Models
 * @property int $id
 * @property int $user_id
 * @property int $target_clients_count
 * @property int $target_partners_count
 * @property int $target_sales_volume
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * * @property-read User $user
 */
class UserGoal extends Model
{
    protected $fillable = [
        'user_id',
        'target_clients_count',
        'target_partners_count',
        'target_sales_volume',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'target_clients_count' => 'integer',
            'target_partners_count' => 'integer',
            'target_sales_volume' => 'integer',
        ];
    }

    /**
     * Пользователь, которому принадлежат цели.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
