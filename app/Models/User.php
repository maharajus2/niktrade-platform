<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'avatar_path',
    'phone',
    'date_of_birth',
    'telegram_username',
    'emergency_contact',
    'employee_status',
    'hire_date',
    'dismissal_date',
    'schedule_type',
    'working_days',
    'work_starts_at',
    'work_ends_at',
    'archived_at',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const STATUS_WORKING = 'working';

    public const STATUS_DAY_OFF = 'day_off';

    public const STATUS_VACATION = 'vacation';

    public const STATUS_SICK_LEAVE = 'sick_leave';

    public const STATUS_DISMISSED = 'dismissed';

    public const SCHEDULE_FIVE_TWO = 'five_two';

    public const SCHEDULE_TWO_TWO = 'two_two';

    public const SCHEDULE_FLEXIBLE = 'flexible';

    public const SCHEDULE_INDIVIDUAL = 'individual';

    protected string $guard_name = 'web';

    public static function employeeStatusOptions(): array
    {
        return [
            self::STATUS_WORKING => 'Работает',
            self::STATUS_DAY_OFF => 'На выходном',
            self::STATUS_VACATION => 'В отпуске',
            self::STATUS_SICK_LEAVE => 'На больничном',
            self::STATUS_DISMISSED => 'Уволен',
        ];
    }

    public static function employeeStatusColor(?string $status): string
    {
        return match ($status) {
            self::STATUS_WORKING => 'success',
            self::STATUS_DAY_OFF => 'gray',
            self::STATUS_VACATION => 'info',
            self::STATUS_SICK_LEAVE => 'warning',
            self::STATUS_DISMISSED => 'danger',
            default => 'gray',
        };
    }

    public static function scheduleTypeOptions(): array
    {
        return [
            self::SCHEDULE_FIVE_TWO => '5/2',
            self::SCHEDULE_TWO_TWO => '2/2',
            self::SCHEDULE_FLEXIBLE => 'Свободный график',
            self::SCHEDULE_INDIVIDUAL => 'Индивидуальный',
        ];
    }

    public static function workingDayOptions(): array
    {
        return [
            'monday' => 'Понедельник',
            'tuesday' => 'Вторник',
            'wednesday' => 'Среда',
            'thursday' => 'Четверг',
            'friday' => 'Пятница',
            'saturday' => 'Суббота',
            'sunday' => 'Воскресенье',
        ];
    }

    public function adminDocuments(): HasMany
    {
        return $this->hasMany(AdminUserDocument::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->archived_at === null;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function canBeArchived(): bool
    {
        return $this->employee_status === self::STATUS_DISMISSED && ! $this->isArchived();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'dismissal_date' => 'date',
            'working_days' => 'array',
            'archived_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }
}
