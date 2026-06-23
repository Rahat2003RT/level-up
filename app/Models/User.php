<?php

namespace App\Models;

use App\Enums\UserPlan;
use App\Enums\UserRole;
use App\Notifications\CustomResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 * * @property int $id
 * @property int|null $leader_id
 * @property string|null $name
 * @property string|null $nickname
 * @property string|null $surname
 * @property string|null $avatar
 * @property string|null $token
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property string|null $avatar_path
 * @property string|null $country
 * @property string|null $city
 * @property string|null $company_name
 * @property string $locale
 * @property string $timezone
 * @property UserRole $role
 * @property UserPlan $plan
 * @property bool $is_onboarded
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $last_activity_at
 * @property Carbon|null $blocked_at
 * @property string|null $block_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * * @property-read User|null $leader
 * @property-read Collection<int, User> $players
 * @property-read Collection<int, UserDevice> $deviceTokens
 * @mixin Builder
 * @property-read Collection<int, UserNotification> $notifications
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'leader_id',
        'name',
        'nickname',
        'surname',
        'email',
        'phone',
        'password',
        'avatar_path',
        'country',
        'city',
        'company_name',
        'timezone',
        'role',
        'is_onboarded',
        'last_activity_at',
        'blocked_at',
        'block_reason',
        'token',
        'plan',
        'account_id',
        'date_of_birth',
        'locale',
        'notifications_enabled',
    ];
    protected $hidden = [
        'password',
        'remember_token'
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'blocked_at' => 'datetime',
            'password' => 'hashed',
            'is_onboarded' => 'boolean',
            'role' => UserRole::class,
            'plan' => UserPlan::class,
        ];
    }

    // --- ОТНОШЕНИЯ (RELATIONS) ---

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(User::class, 'leader_id');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    // --- МЕТОДЫ ПРОВЕРКИ РОЛЕЙ ---

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isLeader(): bool
    {
        return $this->role === UserRole::LEADER;
    }

    public function isElite(): bool
    {
        return $this->role === UserRole::ELITE;
    }

    public function isPlayer(): bool
    {
        return $this->role === UserRole::PLAYER;
    }

    protected static function booted(): void
    {
        static::updating(function (User $user) {
            if ($user->isDirty('role') && !empty($user->getOriginal('role'))) {
                $user->role = $user->getOriginal('role');
            }
        });
        static::creating(function ($user) {
            if (!$user->account_id) {
                $user->account_id = static::generateUniqueAccountId();
            }
        });
    }
    public static function generateUniqueAccountId(): string
    {
        do {
            $account_id = Str::random(10);
        } while (static::where('account_id', $account_id)->exists());

        return $account_id;
    }


    /**
     * Отправка уведомления о сбросе пароля.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $locale = request()->header('X-Locale', request()->input('lang', 'en'));

        $this->notify(new CustomResetPasswordNotification($token, $locale));
    }
}
