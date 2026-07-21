<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Support\EmployeeRequiredDocuments;
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
    'last_name',
    'first_name',
    'patronymic',
    'no_patronymic',
    'email',
    'password',
    'avatar_path',
    'phone',
    'date_of_birth',
    'telegram_username',
    'emergency_contact',
    'position',
    'employment_type',
    'employee_status',
    'employment_status',
    'manager_id',
    'department_id',
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
    'working_days',
    'work_starts_at',
    'work_ends_at',
    'lunch_starts_at',
    'lunch_ends_at',
    'citizenship_type',
    'citizenship_country',
    'arrival_country',
    'arrived_at',
    'foreign_legal_status',
    'archived_at',
    'last_login_at',
    'dashboard_preference',
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

    public const CITIZENSHIP_RUSSIAN = 'russian';

    public const CITIZENSHIP_FOREIGN = 'foreign';

    public const CITIZENSHIP_STATELESS = 'stateless';

    public const FOREIGN_STATUS_EAEU = 'eaeu';

    public const FOREIGN_STATUS_PATENT = 'patent';

    public const FOREIGN_STATUS_WORK_PERMIT = 'work_permit';

    public const FOREIGN_STATUS_TEMPORARY_RESIDENCE = 'temporary_residence';

    public const FOREIGN_STATUS_RESIDENCE_PERMIT = 'residence_permit';

    public const FOREIGN_STATUS_VISA = 'visa';

    public const FOREIGN_STATUS_OTHER = 'other';

    protected string $guard_name = 'web';

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            $user->last_name = self::normalizeNamePart($user->last_name);
            $user->first_name = self::normalizeNamePart($user->first_name);
            $user->patronymic = self::normalizeNamePart($user->patronymic);

            if (blank($user->last_name) && blank($user->first_name) && filled($user->name)) {
                $parts = self::splitFullName((string) $user->name);

                $user->last_name = $parts['last_name'];
                $user->first_name = $parts['first_name'];
                $user->patronymic = $parts['patronymic'];
            }

            $user->no_patronymic = (bool) $user->no_patronymic || blank($user->patronymic);

            if ($user->no_patronymic) {
                $user->patronymic = null;
            }

            $fullName = self::buildFullName(
                $user->last_name,
                $user->first_name,
                $user->patronymic,
            );

            if (filled($fullName)) {
                $user->name = $fullName;
            }
        });
    }

    public static function buildFullName(?string $lastName, ?string $firstName, ?string $patronymic = null): string
    {
        return collect([
            self::normalizeNamePart($lastName),
            self::normalizeNamePart($firstName),
            self::normalizeNamePart($patronymic),
        ])->filter(fn (?string $part): bool => filled($part))->implode(' ');
    }

    /**
     * @return array{last_name: ?string, first_name: ?string, patronymic: ?string}
     */
    public static function splitFullName(string $name): array
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $normalized = mb_strtolower(implode(' ', $words));

        if (in_array($normalized, ['елена алёшина', 'елена алешина'], true)) {
            return [
                'last_name' => $words[1] ?? null,
                'first_name' => $words[0] ?? null,
                'patronymic' => null,
            ];
        }

        if (count($words) === 1) {
            return [
                'last_name' => null,
                'first_name' => $words[0],
                'patronymic' => null,
            ];
        }

        return [
            'last_name' => $words[0] ?? null,
            'first_name' => $words[1] ?? null,
            'patronymic' => count($words) >= 3 ? implode(' ', array_slice($words, 2)) : null,
        ];
    }

    public function getGreetingNameAttribute(): string
    {
        return $this->first_name ?: (self::splitFullName((string) $this->name)['first_name'] ?: $this->name);
    }

    public function getInitialsAttribute(): string
    {
        $parts = [
            $this->last_name,
            $this->first_name,
        ];

        if (blank($this->last_name) && blank($this->first_name)) {
            $parts = preg_split('/\s+/u', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return collect($parts)
            ->filter(fn (?string $part): bool => filled($part))
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->join('') ?: 'N';
    }

    private static function normalizeNamePart(mixed $value): ?string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        return $value === '' ? null : $value;
    }

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

    public static function citizenshipTypeOptions(): array
    {
        return [
            self::CITIZENSHIP_RUSSIAN => 'Гражданин РФ',
            self::CITIZENSHIP_FOREIGN => 'Иностранный гражданин',
            self::CITIZENSHIP_STATELESS => 'Лицо без гражданства',
        ];
    }

    public static function foreignLegalStatusOptions(): array
    {
        return [
            self::FOREIGN_STATUS_EAEU => 'ЕАЭС',
            self::FOREIGN_STATUS_PATENT => 'Патент',
            self::FOREIGN_STATUS_WORK_PERMIT => 'Разрешение на работу',
            self::FOREIGN_STATUS_TEMPORARY_RESIDENCE => 'РВП',
            self::FOREIGN_STATUS_RESIDENCE_PERMIT => 'ВНЖ',
            self::FOREIGN_STATUS_VISA => 'Виза',
            self::FOREIGN_STATUS_OTHER => 'Другое',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }

    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(EmployeeScheduleEntry::class, 'employee_id');
    }

    public function scheduleRequests(): HasMany
    {
        return $this->hasMany(EmployeeScheduleRequest::class, 'employee_id');
    }

    public function assignedApprovalWorkflows(): HasMany
    {
        return $this->hasMany(ApprovalWorkflow::class, 'current_approver_id');
    }

    public function ownedTaskBoards(): HasMany
    {
        return $this->hasMany(TaskBoard::class, 'owner_id');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'creator_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function delegatedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by_id');
    }

    public function taskParticipations(): HasMany
    {
        return $this->hasMany(TaskParticipant::class);
    }

    public function conversationParticipations(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function futureScheduleEntries(): HasMany
    {
        return $this->scheduleEntries()
            ->active()
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('starts_at');
    }

    public function activeDocuments(): HasMany
    {
        return $this->documents()
            ->whereNull('archived_at')
            ->whereNull('replaced_by_id');
    }

    public function probationCancelledBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'probation_cancelled_by');
    }

    public function getEffectiveManager(): ?self
    {
        return $this->manager
            ?: $this->department?->actingManager
            ?: $this->department?->manager;
    }

    public function getEmploymentTypeLabel(): string
    {
        return self::employmentTypeOptions()[$this->employment_type] ?? 'Не указан';
    }

    public function missingRequiredDocuments(): array
    {
        return EmployeeRequiredDocuments::missingFor($this);
    }

    public function hasRequiredDocuments(): bool
    {
        return EmployeeRequiredDocuments::isComplete($this);
    }

    public function documentCompletenessPercent(): int
    {
        $required = EmployeeRequiredDocuments::requiredFor($this);

        if ($required === []) {
            return 100;
        }

        $missing = $this->missingRequiredDocuments();

        return (int) round(((count($required) - count($missing)) / count($required)) * 100);
    }

    public function expiringDocuments(int $days = 30)
    {
        return $this->activeDocuments()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', today()->addDays($days))
            ->orderBy('expires_at')
            ->get();
    }

    public function getEmploymentStatusLabel(): string
    {
        return self::employeeStatusOptions()[$this->employment_status ?? $this->employee_status] ?? 'Не указан';
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getAgeLabelAttribute(): string
    {
        if ($this->age === null) {
            return 'Не указан';
        }

        $age = $this->age;
        $lastTwo = $age % 100;
        $last = $age % 10;

        $suffix = match (true) {
            $lastTwo >= 11 && $lastTwo <= 14 => 'лет',
            $last === 1 => 'год',
            $last >= 2 && $last <= 4 => 'года',
            default => 'лет',
        };

        return "{$age} {$suffix}";
    }

    public function getScheduleTypeLabel(): string
    {
        return self::scheduleTypeOptions()[$this->schedule_type] ?? 'Не указан';
    }

    public function isIndividualSchedule(): bool
    {
        return $this->schedule_type === self::SCHEDULE_INDIVIDUAL;
    }

    public function scheduleForMonth(int $year, int $month)
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return $this->scheduleEntries()
            ->active()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (EmployeeScheduleEntry $entry): string => $entry->date->toDateString());
    }

    public function getCitizenshipTypeLabel(): string
    {
        return self::citizenshipTypeOptions()[$this->citizenship_type] ?? 'Гражданин РФ';
    }

    public function getForeignLegalStatusLabel(): string
    {
        return self::foreignLegalStatusOptions()[$this->foreign_legal_status] ?? 'Не указан';
    }

    public function requiresMigrationProfile(): bool
    {
        return in_array($this->citizenship_type, [
            self::CITIZENSHIP_FOREIGN,
            self::CITIZENSHIP_STATELESS,
        ], true);
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
            'no_patronymic' => 'boolean',
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'dismissal_date' => 'date',
            'probation_enabled' => 'boolean',
            'probation_started_at' => 'date',
            'probation_ends_at' => 'date',
            'probation_cancelled_at' => 'datetime',
            'salary_amount' => 'decimal:2',
            'working_days' => 'array',
            'arrived_at' => 'date',
            'archived_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }
}
