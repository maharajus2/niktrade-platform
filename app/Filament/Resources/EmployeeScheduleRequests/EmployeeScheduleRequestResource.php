<?php

namespace App\Filament\Resources\EmployeeScheduleRequests;

use App\Filament\Resources\EmployeeScheduleRequests\Pages\CreateEmployeeScheduleRequest;
use App\Filament\Resources\EmployeeScheduleRequests\Pages\ListEmployeeScheduleRequests;
use App\Models\ApprovalWorkflow;
use App\Models\Department;
use App\Models\EmployeeScheduleRequest;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeScheduleRequestResource extends Resource
{
    protected static ?string $model = EmployeeScheduleRequest::class;

    protected static ?string $navigationLabel = 'Мои заявки';

    protected static ?string $modelLabel = 'Заявка на график';

    protected static ?string $pluralModelLabel = 'Заявки на график';

    protected static string|\UnitEnum|null $navigationGroup = '🏢 Организация';

    protected static ?int $navigationSort = 105;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function getNavigationLabel(): string
    {
        return static::canReviewAny() ? 'Заявки сотрудников' : 'Мои заявки';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('employee_id')
                    ->label('Сотрудник')
                    ->options(fn (): array => static::employeeOptionsForCurrentUser())
                    ->default(fn (): ?int => auth()->id())
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

                Select::make('type')
                    ->label('Тип заявки')
                    ->options(EmployeeScheduleRequest::typeOptions())
                    ->default(EmployeeScheduleRequest::TYPE_DAY_OFF)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, callable $set): void {
                        if ($state === EmployeeScheduleRequest::TYPE_SHIFT) {
                            $set('is_all_day', false);

                            return;
                        }

                        if (in_array($state, [
                            EmployeeScheduleRequest::TYPE_DAY_OFF,
                            EmployeeScheduleRequest::TYPE_VACATION,
                            EmployeeScheduleRequest::TYPE_SICK_LEAVE,
                        ], true)) {
                            $set('is_all_day', true);
                        }
                    }),

                TextInput::make('title')
                    ->label('Название')
                    ->maxLength(255)
                    ->required(fn (callable $get): bool => $get('type') === EmployeeScheduleRequest::TYPE_CUSTOM)
                    ->visible(fn (callable $get): bool => $get('type') === EmployeeScheduleRequest::TYPE_CUSTOM),

                Select::make('request_reason_type')
                    ->label('Причина')
                    ->options(EmployeeScheduleRequest::reasonOptions())
                    ->default(EmployeeScheduleRequest::REASON_TIME_OFF)
                    ->required(fn (callable $get): bool => $get('type') === EmployeeScheduleRequest::TYPE_DAY_OFF)
                    ->visible(fn (callable $get): bool => $get('type') === EmployeeScheduleRequest::TYPE_DAY_OFF),

                Toggle::make('vacation_without_pay')
                    ->label('Без сохранения')
                    ->helperText('Такой отпуск будет отмечен как неоплачиваемый.')
                    ->default(false)
                    ->visible(fn (callable $get): bool => $get('type') === EmployeeScheduleRequest::TYPE_VACATION),

                DatePicker::make('start_date')
                    ->label('Дата начала')
                    ->native(false)
                    ->required()
                    ->default(today()),

                DatePicker::make('end_date')
                    ->label('Дата окончания')
                    ->native(false)
                    ->required()
                    ->default(today()),

                Toggle::make('is_all_day')
                    ->label('Весь день')
                    ->default(true)
                    ->live()
                    ->disabled(fn (callable $get): bool => in_array($get('type'), [
                        EmployeeScheduleRequest::TYPE_VACATION,
                        EmployeeScheduleRequest::TYPE_SICK_LEAVE,
                        EmployeeScheduleRequest::TYPE_SHIFT,
                    ], true)),

                TextInput::make('starts_at')
                    ->label('Время начала')
                    ->type('time')
                    ->required(fn (callable $get): bool => $get('type') === EmployeeScheduleRequest::TYPE_SHIFT || ! $get('is_all_day'))
                    ->visible(fn (callable $get): bool => $get('type') === EmployeeScheduleRequest::TYPE_SHIFT || ! $get('is_all_day')),

                TextInput::make('ends_at')
                    ->label('Время окончания')
                    ->type('time')
                    ->required(fn (callable $get): bool => $get('type') === EmployeeScheduleRequest::TYPE_SHIFT || ! $get('is_all_day'))
                    ->visible(fn (callable $get): bool => $get('type') === EmployeeScheduleRequest::TYPE_SHIFT || ! $get('is_all_day')),

                Textarea::make('reason')
                    ->label('Причина / комментарий')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'employee.department',
                'requestedBy',
                'reviewedBy',
                'approvalWorkflow.currentApprover.roles',
            ]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Сотрудник')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.department.name')
                    ->label('Отдел')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, EmployeeScheduleRequest $record): string => $record->getTypeLabel())
                    ->color('info'),

                TextColumn::make('reason_label')
                    ->label('Причина')
                    ->state(fn (EmployeeScheduleRequest $record): ?string => $record->getReasonLabel())
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('date_range')
                    ->label('Период')
                    ->state(fn (EmployeeScheduleRequest $record): string => $record->getDateRangeLabel()),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, EmployeeScheduleRequest $record): string => $record->getStatusLabel())
                    ->color(fn (EmployeeScheduleRequest $record): string => $record->getStatusColor()),

                TextColumn::make('approvalWorkflow.currentApprover.name')
                    ->label('Согласующий')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('reason')
                    ->label('Причина')
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(EmployeeScheduleRequest::statusOptions()),

                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(EmployeeScheduleRequest::typeOptions()),

                SelectFilter::make('department')
                    ->label('Отдел')
                    ->options(fn (): array => Department::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('employee', fn (Builder $query): Builder => $query->where('department_id', $data['value']))
                        : $query)
                    ->searchable()
                    ->preload(),

                SelectFilter::make('employee_id')
                    ->label('Сотрудник')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('approval_queue')
                    ->label('Очередь')
                    ->options([
                        'my_decision' => 'Требуют моего решения',
                        'hr_decision' => 'Требуют решения HR',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === 'my_decision' && auth()->id()) {
                            return $query
                                ->whereIn('status', [
                                    EmployeeScheduleRequest::STATUS_PENDING,
                                    EmployeeScheduleRequest::STATUS_IN_REVIEW,
                                    EmployeeScheduleRequest::STATUS_FORWARDED,
                                ])
                                ->whereHas('approvalWorkflow', fn (Builder $query): Builder => $query->where('current_approver_id', auth()->id()));
                        }

                        if ($value === 'hr_decision') {
                            return $query
                                ->whereIn('status', [
                                    EmployeeScheduleRequest::STATUS_PENDING,
                                    EmployeeScheduleRequest::STATUS_IN_REVIEW,
                                    EmployeeScheduleRequest::STATUS_FORWARDED,
                                ])
                                ->whereHas('approvalWorkflow.currentApprover.roles', fn (Builder $query): Builder => $query->where('name', 'hr'));
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Одобрить окончательно')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (EmployeeScheduleRequest $record): bool => static::canApprove($record))
                    ->form([
                        Textarea::make('manager_comment')
                            ->label('Комментарий')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, EmployeeScheduleRequest $record): void {
                        /** @var User $reviewer */
                        $reviewer = auth()->user();
                        $created = $record->approve($reviewer, (string) $data['manager_comment']);

                        Notification::make()
                            ->title("Заявка одобрена. Создано событий: {$created}.")
                            ->success()
                            ->send();
                    }),

                Action::make('forward')
                    ->label('Передать')
                    ->icon(Heroicon::OutlinedArrowRight)
                    ->color('info')
                    ->visible(fn (EmployeeScheduleRequest $record): bool => static::canApprove($record))
                    ->form([
                        Select::make('next_approver_id')
                            ->label('Следующий согласующий')
                            ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->required()
                            ->searchable()
                            ->preload(),
                        Textarea::make('manager_comment')
                            ->label('Комментарий')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, EmployeeScheduleRequest $record): void {
                        /** @var User $reviewer */
                        $reviewer = auth()->user();
                        $nextApprover = User::query()->findOrFail($data['next_approver_id']);

                        $record->forwardTo($reviewer, $nextApprover, (string) $data['manager_comment']);

                        Notification::make()
                            ->title('Заявка передана на согласование.')
                            ->success()
                            ->send();
                    }),

                Action::make('return')
                    ->label('Вернуть')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('warning')
                    ->visible(fn (EmployeeScheduleRequest $record): bool => static::canApprove($record))
                    ->form([
                        Textarea::make('manager_comment')
                            ->label('Комментарий')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, EmployeeScheduleRequest $record): void {
                        /** @var User $reviewer */
                        $reviewer = auth()->user();
                        $record->returnToRequester($reviewer, (string) $data['manager_comment']);

                        Notification::make()
                            ->title('Заявка возвращена сотруднику.')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Отклонить')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (EmployeeScheduleRequest $record): bool => static::canReject($record))
                    ->form([
                        Textarea::make('manager_comment')
                            ->label('Комментарий руководителя')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, EmployeeScheduleRequest $record): void {
                        /** @var User $reviewer */
                        $reviewer = auth()->user();
                        $record->reject($reviewer, (string) $data['manager_comment']);

                        Notification::make()
                            ->title('Заявка отклонена.')
                            ->success()
                            ->send();
                    }),

                Action::make('cancel')
                    ->label('Отменить')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (EmployeeScheduleRequest $record): bool => static::canCancel($record))
                    ->action(function (EmployeeScheduleRequest $record): void {
                        /** @var User $reviewer */
                        $reviewer = auth()->user();
                        $record->cancel($reviewer);

                        Notification::make()
                            ->title('Заявка отменена.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user instanceof User ? $query->visibleTo($user) : $query->whereRaw('1 = 0');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->check();
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canApprove(EmployeeScheduleRequest $record): bool
    {
        return $record->canBeReviewed() && static::canReview($record);
    }

    public static function canReject(EmployeeScheduleRequest $record): bool
    {
        return $record->canBeReviewed() && static::canReview($record);
    }

    public static function canCancel(EmployeeScheduleRequest $record): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $record->canBeCancelled()) {
            return false;
        }

        return (int) $record->employee_id === (int) $user->getKey()
            || $user->hasRole('super_admin')
            || $user->hasRole('hr')
            || $user->can('employees.schedule_requests.cancel');
    }

    public static function canReview(EmployeeScheduleRequest $record): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        $workflow = $record->approvalWorkflow;

        if ($workflow instanceof ApprovalWorkflow && $workflow->current_approver_id !== null) {
            return (int) $workflow->current_approver_id === (int) $user->getKey()
                || $user->hasRole('super_admin')
                || $user->hasRole('hr')
                || $user->can('employees.schedule_requests.approve');
        }

        return $user->hasRole('super_admin')
            || $user->hasRole('hr')
            || $user->can('employees.schedule_requests.approve')
            || (int) $record->employee?->manager_id === (int) $user->getKey();
    }

    public static function canReviewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && (
                $user->hasRole('super_admin')
                || $user->hasRole('hr')
                || $user->can('employees.schedule_requests.approve')
                || $user->assignedApprovalWorkflows()->whereIn('status', [
                    ApprovalWorkflow::STATUS_PENDING,
                    ApprovalWorkflow::STATUS_IN_REVIEW,
                    ApprovalWorkflow::STATUS_FORWARDED,
                ])->exists()
                || $user->directReports()->exists()
            );
    }

    public static function employeeOptionsForCurrentUser(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        if ($user->hasRole('super_admin') || $user->hasRole('hr') || $user->can('employees.schedule_requests.create')) {
            return User::query()->orderBy('name')->pluck('name', 'id')->all();
        }

        return User::query()
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->whereKey($user->getKey())
                    ->orWhere('manager_id', $user->getKey());
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeScheduleRequests::route('/'),
            'create' => CreateEmployeeScheduleRequest::route('/create'),
        ];
    }
}
