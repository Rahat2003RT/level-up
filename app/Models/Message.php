<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class Message
 *
 * --- Свойства (Поля в БД) ---
 * @property string $id
 * @property int $chat_id
 * @property int $sender_id
 * @property string $text
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * --- Отношения (Relations) ---
 * @property-read Chat $chat
 * @property-read User $sender
 */
final class Message extends Model
{
    use SoftDeletes, HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'chat_id',
        'sender_id',
        'text',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        Message::creating(function (Message $message) {
            if (empty($message->id)) {
                $message->id = (string) Str::uuid();
            }
        });
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
