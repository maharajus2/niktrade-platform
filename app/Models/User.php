<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
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
    'employment_type',
    'employee_status',
    'employment_status',
    'manager_id',
    'hire_date',
    'dismissal_date',
    'probation_enabled',
    'probation_started_at',
    'probation_ends_at',
    'probation_cancelled_at',
    'probation_cancelled_by',
    'salary_amount',
    'salary_currency',
    'schedule_type',
    'archived_at',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const EMPLOYMENT_TYPE_STAFF = 'staff';

    public const EMPLOYMENT_TYPE_CIVIL_CONTRACT = 'civil_contract';

    public const EMPLOYMENT_TYPE_SELF_EMPLOYED = 'self_employed';

    public const EMPLOYMENT_TYPE_PART_TIME = 'part_time';

    public const EMPLOYMENT_TYPE_MINOR = 'minor';

    public const EMPLOYMENT_TYPE_NOT_EMPLOYED = 'not_employed';

    public const STATUS_WORKING = 'working';

    public const STATUS_VACATION = 'vacation';

    public const STATUS_SICK_LEAVE = 'sick_leave';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_ARCHIVED = 'archived';

    public const SCHEDULE_FIVE_TWO = 'five_two';

    public const SCHEDULE_TWO_TWO = 'two_two';

    public const SCHEDULE_FLEXIBLE = 'flexible';

    public const SCHEDULE_INDIVIDUAL = 'individual';

    protected string $guard_name = 'web';

    public static function employmentTypeOptions(): array
    {
        return [
            self::EMPLOYMENT_TYPE_STAFF => 'Штат',
            self::EMPLOYMENT_TYPE_CIVIL_CONTRACT => 'ГПХ',
            self::EMPLOYMENT_TYPE_SELF_EMPLOYED => 'Самозанятость',
            self::EMPLOYMENT_TYPE_PART_TIME => 'Подработка',
            self::EMPLOYMENT_TYPE_MINOR => 'Несовершеннолетний',
            self::EMPLOYMENT_TYPE_NOT_EMPLOYED => 'Не трудоустроен',
        ];
    }

    public static function employeeStatusOptions(): array
    {
        return [
            self::STATUS_WORKING => 'Работает',
            self::STATUS_VACATION => 'В отпуске',
            self::STATUS_SICK_LEAVE => 'На больничном',
            self::STATUS_DISMISSED => 'Уволен',
            self::STATUS_ARCHIVED => 'Архив',
        ];
    }

    public static function employeeStatusColor(?string $status): string
    {
        return match ($status) {
            self::STATUS_WORKING => 'success',
            self::STATUS_VACATION => 'info',
            self::STATUS_SICK_LEAVE => 'warning',
            self::STATUS_DISMISSED => 'danger',
            self::STATUS_ARCHIVED => 'gray',
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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function probationCancelledBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'probation_cancelled_by');
    }

    public function getEmploymentTypeLabel(): string
    {
        return self::employmentTypeOptions()[$this->employment_type] ?? 'Не указан';
    }

    public function getEmploymentStatusLabel(): string
    {
        return self::employeeStatusOptions()[$this->employment_status ?? $this->employee_status] ?? 'Не указан';
    }

    public function getScheduleTypeLabel(): string
    {
        return self::scheduleTypeOptions()[$this->schedule_type] ?? 'Не указан';
    }

    public function getTenureLabel(): string
    {
        if (! $this->hire_date) {
            return 'Не указан';
        }

        $start = Carbon::parse($this->hire_date);
        $end = $this->dismissal_date ? Carbon::parse($this->dismissal_date) : now();
        $parts = $start->diffAsCarbonInterval($end)->cascade();

        $years = $parts->years;
        $months = $parts->months;

        return match (true) {
            $years > 0 && $months > 0 => "{$years} г. {$months} мес.",
            $years > 0 => "{$years} г.",
            $months > 0 => "{$months} мес.",
            default => 'Менее месяца',
        };
    }

    public function isOnProbation(): bool
    {
        return (bool) $this->probation_enabled
            && $this->probation_cancelled_at === null
            && ($this->probation_ends_at === null || Carbon::parse($this->probation_ends_at)->endOfDay()->isFuture());
    }

    public function getProbationLabel(): string
    {
        if ($this->probation_cancelled_at) {
            return 'Испытательный срок отменён';
        }

        if (! $this->probation_enabled) {
            return 'Нет испытательного срока';
        }

        if ($this->probation_ends_at) {
            return 'Испытательный срок до ' . Carbon::parse($this->probation_ends_at)->format('d.m.Y');
        }

        return 'Испытательный срок';
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null || ($this->employment_status ?? $this->employee_status) === self::STATUS_ARCHIVED;
    }

    public function canBeArchived(): bool
    {
        return ($this->employment_status ?? $this->employee_status) === self::STATUS_DISMISSED && ! $this->isArchived();
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
            'probation_enabled' => 'boolean',
            'probation_started_at' => 'date',
            'probation_ends_at' => 'date',
            'probation_cancelled_at' => 'datetime',
            'salary_amount' => 'decimal:2',
            'archived_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }
}
