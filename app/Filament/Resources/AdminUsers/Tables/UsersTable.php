<?php

namespace App\Filament\Resources\AdminUsers\Tables;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\User;
use App\Support\AdminRoles;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->select('users.*')
                ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
                ->with(['roles', 'manager', 'department'])
                ->orderByRaw('departments.name asc nulls last')
                ->orderByRaw('users.position asc nulls last')
                ->orderBy('users.name'))
            ->groups([
                Group::make('department_id')
                    ->label('Отдел')
                    ->getTitleFromRecordUsing(fn (User $record): string => $record->department?->name ?? 'Без отдела')
                    ->getKeyFromRecordUsing(fn (User $record): string => $record->department_id ? (string) $record->department_id : 'none')
                    ->scopeQueryByKeyUsing(function (Builder $query, ?string $key): Builder {
                        return $key === 'none'
                            ? $query->whereNull('users.department_id')
                            : $query->where('users.department_id', $key);
                    })
                    ->orderQueryUsing(fn (Builder $query, string $direction): Builder => $query
                        ->orderByRaw("departments.name {$direction} nulls last"))
                    ->collapsible(),
            ])
            ->defaultGroup('department_id')
            ->groupingSettingsHidden()
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('Фото')
                    ->disk('public')
                    ->imageWidth(40)
                    ->imageHeight(52)
                    ->extraImgAttributes([
                        'style' => 'background: #ffffff; border: 1px solid #d1d5db; border-radius: 6px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12); object-fit: cover;',
                    ]),

                TextColumn::make('name')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('position')
                    ->label('Должность')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AdminRoles::label($state))
                    ->icon(fn (string $state): Heroicon => AdminRoles::icon($state))
                    ->color(fn (string $state): string => AdminRoles::color($state))
                    ->separator(', '),

                TextColumn::make('employment_status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->getEmploymentStatusLabel())
                    ->color(fn (?string $state, User $record): string => User::employeeStatusColor($record->employment_status ?? $record->employee_status))
                    ->sortable(),

                TextColumn::make('employment_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->getEmploymentTypeLabel())
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('manager.name')
                    ->label('Руководитель')
                    ->url(fn (User $record): ?string => $record->manager
                        ? UserResource::getUrl('view', ['record' => $record->manager])
                        : null)
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('department.name')
                    ->label('Отдел')
                    ->url(fn (User $record): ?string => $record->department
                        ? DepartmentResource::getUrl('view', ['record' => $record->department])
                        : null)
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('documents_status')
                    ->label('Документы')
                    ->badge()
                    ->state(fn (User $record): string => UserResource::documentStatusLabel($record))
                    ->color(fn (User $record): string => UserResource::documentStatusColor($record))
                    ->visible(fn (): bool => UserResource::canViewEmployeeDocuments())
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Отдел')
                    ->placeholder('Все отделы')
                    ->relationship('department', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name'))
                    ->searchable()
                    ->preload(),

                Filter::make('management')
                    ->label('Руководящий состав')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                        $query
                            ->whereExists(function ($query): void {
                                $query
                                    ->selectRaw('1')
                                    ->from('departments as managed_departments')
                                    ->whereColumn('managed_departments.manager_id', 'users.id');
                            })
                            ->orWhereExists(function ($query): void {
                                $query
                                    ->selectRaw('1')
                                    ->from('departments as acting_departments')
                                    ->whereColumn('acting_departments.acting_manager_id', 'users.id');
                            })
                            ->orWhereExists(function ($query): void {
                                $query
                                    ->selectRaw('1')
                                    ->from('users as direct_reports')
                                    ->whereColumn('direct_reports.manager_id', 'users.id');
                            });
                    })),

                TernaryFilter::make('archived_at')
                    ->label('Показывать архив')
                    ->placeholder('Активные')
                    ->trueLabel('Все сотрудники')
                    ->falseLabel('Только активные')
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query): Builder => $query,
                        false: fn (Builder $query): Builder => $query
                            ->whereNull('archived_at')
                            ->where(function (Builder $query): void {
                                $query
                                    ->whereNull('employment_status')
                                    ->orWhere('employment_status', '!=', User::STATUS_ARCHIVED);
                            }),
                        blank: fn (Builder $query): Builder => $query
                            ->whereNull('archived_at')
                            ->where(function (Builder $query): void {
                                $query
                                    ->whereNull('employment_status')
                                    ->orWhere('employment_status', '!=', User::STATUS_ARCHIVED);
                            }),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                UserResource::archiveAction(),
            ]);
    }
}
