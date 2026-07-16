<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Chat
 *
 * --- Свойства (Поля в БД) ---
 * @property int $id
 * @property int $elite_id
 * @property int $leader_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * --- Отношения (Relations как свойства) ---
 * @property-read User $elite
 * @property-read User $leader
 * @property-read Collection<int, Message> $messages
 * @property-read Message|null $lastMessage
 *
 * --- Хелперы для IDE / Query Builder ---
 * @method static Builder|Chat query()
 * @method static Builder|Chat newModelQuery()
 * @method static Builder|Chat newQuery()
 * @method static Chat create(array $attributes = [])
 * @method static Chat updateOrCreate(array $attributes, array $values = [])
 * @method static Builder|Chat where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Chat|null find($id, $columns = ['*'])
 * @method static Chat|null first($columns = ['*'])
 * @method static Chat firstOrFail($columns = ['*'])
 */
final class Chat extends Model
{
    protected $fillable = [
        'elite_id',
        'leader_id',
    ];

    public function elite(): BelongsTo
    {
        return $this->belongsTo(User::class, 'elite_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany('created_at');
    }
}
