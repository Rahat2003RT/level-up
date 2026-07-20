<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\CustomResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * @property int $id
 * @property int|null $leader_id
 * @property int|null $tariff_id
 * @property string|null $account_id
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
 * @property string|null $date_of_birth
 * @property UserRole $role
 * @property bool $is_onboarded
 * @property bool $notifications_enabled
 * @property Carbon|null $trial_started_at
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $subscription_ends_at
 * @property bool $auto_renew
 * @property bool plan_paused
 * @property string|null $payment_recurrent_id
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $last_activity_at
 * @property Carbon|null $blocked_at
 * @property string|null $block_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read User|null $leader
 * @property-read Tariff|null $tariff
 * @property-read Collection<int, User> $players
 * @property-read Collection<int, UserDevice> $deviceTokens
 * @property-read Collection<int, UserNotification> $notifications
 * @property-read UserGoal|null $goal
 * @property-read Collection<int, DailyChecklist> $checklists
 * @property-read Collection<int, LeadershipChecklist> $leadershipChecklists
 * @property-read Collection<int, Contact> $contacts
 * @property-read Chat|null $leaderChat
 *
 * @method static Builder|User query()
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User onlyTrashed()
 * @method static Builder|User withTrashed()
 * @method static User create(array $attributes = [])
 * @method static User updateOrCreate(array $attributes, array $values = [])
 * @method static Builder|User where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static User|null find($id, $columns = ['*'])
 * @method static User|null first($columns = ['*'])
 * @method static User firstOrFail($columns = ['*'])
 * @method static Builder|User whereAccountId($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereRole($value)
 *
 * @mixin Builder
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'leader_id',
        'tariff_id',
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
        'trial_started_at',
        'trial_ends_at',
        'subscription_ends_at',
        'auto_renew',
        'payment_recurrent_id',
        'last_activity_at',
        'blocked_at',
        'block_reason',
        'token',
        'account_id',
        'date_of_birth',
        'locale',
        'notifications_enabled',
        'plan_paused'
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
            'email_verified_at'    => 'datetime',
            'last_activity_at'     => 'datetime',
            'blocked_at'           => 'datetime',
            'trial_started_at'     => 'datetime',
            'trial_ends_at'        => 'datetime',
            'subscription_ends_at' => 'datetime',
            'auto_renew'           => 'boolean',
            'password'             => 'hashed',
            'role'                 => UserRole::class,
            'plan_paused'          => 'boolean',
        ];
    }

    // --- ОТНОШЕНИЯ (RELATIONS) ---

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
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

    public function goal(): HasOne
    {
        return $this->hasOne(UserGoal::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(DailyChecklist::class, 'user_id');
    }

    public function leadershipChecklists(): HasMany
    {
        return $this->hasMany(LeadershipChecklist::class, 'user_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function leaderChat(): HasOne
    {
        return $this->hasOne(Chat::class, 'leader_id', 'id');
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

    // --- УПРАВЛЕНИЕ ПРОБНЫМ ПЕРИОДОМ (TRIAL) ---

    /**
     * Проверить, активен ли пробный период прямо сейчас.
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Проверить, имеет ли пользователь активный доступ.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->onTrial() || ($this->tariff_id && $this->subscription_ends_at?->isFuture());
    }

    /**
     * Проверить, отменил ли пользователь продление.
     */
    public function isSubscriptionCancelled(): bool
    {
        return $this->tariff_id && !$this->auto_renew;
    }

    /**
     * Запустить или обновить пробный период.
     */
    public function startTrial(int $days = 7): void
    {
        $this->update([
            'trial_started_at' => now(),
            'trial_ends_at'    => now()->addDays($days),
        ]);
    }

    /**
     * Отменить или завершить пробный период.
     */
    public function cancelTrial(): void
    {
        $this->update([
            'trial_ends_at' => null,
        ]);
    }

    public function planPauses(): HasMany
    {
        return $this->hasMany(PlanPause::class);
    }

    // --- ЖИЗНЕННЫЙ ЦИКЛ МОДЕЛИ (BOOTED) ---

    protected static function booted(): void
    {
        static::updating(function (User $user) {
            if ($user->isDirty('role') && !empty($user->getOriginal('role'))) {
                $user->role = $user->getOriginal('role');
            }
            if ($user->isDirty('leader_id') && $user->isLeader() && $user->leader_id) {
                $newLeaderBoss = User::find($user->leader_id);

                if ($newLeaderBoss && $newLeaderBoss->isElite()) {
                    Chat::firstOrCreate([
                        'elite_id'  => $newLeaderBoss->id,
                        'leader_id' => $user->id,
                    ]);
                }
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
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $locale = request()->header('X-Locale', request()->input('lang', 'en'));

        $this->notify(new CustomResetPasswordNotification($token, $locale));
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->avatar_path)) {
                    return null;
                }
                if (filter_var($this->avatar_path, FILTER_VALIDATE_URL)) {
                    return $this->avatar_path;
                }
                return Storage::disk('public')->url($this->avatar_path);
            }
        );
    }
}
