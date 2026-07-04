<x-filament-panels::page>
    <style>
        .nt-workplace {
            margin: -1rem;
            padding: 1.5rem;
            background: #f5f7fa;
            border-radius: 24px;
        }

        .nt-workplace__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .nt-workplace__title {
            font-size: 1.65rem;
            line-height: 2rem;
            font-weight: 700;
            color: #111827;
        }

        .nt-workplace__date {
            margin-top: .35rem;
            color: #6b7280;
            font-size: .95rem;
        }

        .nt-workplace__tools {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .nt-workplace__search {
            min-width: 260px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #fff;
            padding: .6rem 1rem;
            color: #9ca3af;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .nt-workplace__icon,
        .nt-workplace__avatar {
            display: inline-flex;
            width: 42px;
            height: 42px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #374151;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .nt-workplace__avatar {
            background: #0ea5e9;
            color: #fff;
            font-weight: 700;
        }

        .nt-workplace__grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 1.25rem;
        }

        .nt-workplace__area {
            min-width: 0;
        }

        .nt-workplace__area--large {
            grid-column: span 6 / span 6;
        }

        .nt-workplace__area--medium {
            grid-column: span 4 / span 4;
        }

        .nt-workplace__area--small {
            grid-column: span 2 / span 2;
        }

        .nt-workplace__area--half {
            grid-column: span 6 / span 6;
        }

        .nt-workplace__area--full {
            grid-column: span 12 / span 12;
        }

        .nt-workplace .fi-wi-widget > div,
        .nt-workplace .fi-wi-widget > section {
            border-radius: 18px !important;
            box-shadow: 0 14px 35px rgba(15, 23, 42, .06) !important;
        }

        @media (max-width: 1180px) {
            .nt-workplace__area--large,
            .nt-workplace__area--medium,
            .nt-workplace__area--small,
            .nt-workplace__area--half {
                grid-column: span 6 / span 6;
            }
        }

        @media (max-width: 760px) {
            .nt-workplace {
                margin: -.75rem;
                padding: 1rem;
                border-radius: 18px;
            }

            .nt-workplace__header {
                align-items: flex-start;
                flex-direction: column;
            }

            .nt-workplace__tools {
                width: 100%;
            }

            .nt-workplace__search {
                min-width: 0;
                flex: 1;
            }

            .nt-workplace__area--large,
            .nt-workplace__area--medium,
            .nt-workplace__area--small,
            .nt-workplace__area--half,
            .nt-workplace__area--full {
                grid-column: span 12 / span 12;
            }
        }
    </style>

    <div class="nt-workplace">
        <div class="nt-workplace__header">
            <div>
                <div class="nt-workplace__title">{{ $this->getGreeting() }}, {{ auth()->user()->name }} 👋</div>
                <div class="nt-workplace__date">{{ now()->translatedFormat('l, d F Y') }}</div>
            </div>

            <div class="nt-workplace__tools" aria-label="Инструменты рабочего пространства">
                <div class="nt-workplace__search">Поиск</div>
                <div class="nt-workplace__icon">🔔</div>
                <div class="nt-workplace__avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
            </div>
        </div>

        @if ($this->getContextKey() === \App\Support\Dashboard\DashboardWidgetRegistry::CONTEXT_EMPLOYEE)
            <div class="nt-workplace__grid">
                <div class="nt-workplace__area nt-workplace__area--large">
                    @livewire(\App\Filament\Widgets\Employee\EmployeeMyDayWidget::class, key('workplace-employee-my-day'))
                </div>

                <div class="nt-workplace__area nt-workplace__area--medium">
                    @livewire(\App\Filament\Widgets\Employee\EmployeeCalendarWidget::class, key('workplace-employee-calendar'))
                </div>

                <div class="nt-workplace__area nt-workplace__area--small">
                    @livewire(\App\Filament\Widgets\Employee\EmployeeAttentionWidget::class, key('workplace-employee-attention'))
                </div>

                <div class="nt-workplace__area nt-workplace__area--half">
                    @livewire(\App\Filament\Widgets\Employee\EmployeeTasksWidget::class, key('workplace-employee-tasks'))
                </div>

                <div class="nt-workplace__area nt-workplace__area--medium">
                    @livewire(\App\Filament\Widgets\Employee\EmployeeQuickActionsWidget::class, key('workplace-employee-actions'))
                </div>

                <div class="nt-workplace__area nt-workplace__area--small">
                    @livewire(\App\Filament\Widgets\Employee\EmployeeMessagesWidget::class, key('workplace-employee-messages'))
                </div>

                <div class="nt-workplace__area nt-workplace__area--half">
                    @livewire(\App\Filament\Widgets\Employee\EmployeeRequestsWidget::class, key('workplace-employee-requests'))
                </div>

                <div class="nt-workplace__area nt-workplace__area--half">
                    @livewire(\App\Filament\Widgets\Employee\EmployeeDocumentsWidget::class, key('workplace-employee-documents'))
                </div>
            </div>
        @elseif ($this->getContextKey() === \App\Support\Dashboard\DashboardWidgetRegistry::CONTEXT_HR)
            <div class="nt-workplace__grid">
                <div class="nt-workplace__area nt-workplace__area--large">
                    @livewire(\App\Filament\Widgets\Hr\HrTodayWidget::class, key('workplace-hr-today'))
                </div>

                <div class="nt-workplace__area nt-workplace__area--medium">
                    @livewire(\App\Filament\Widgets\Hr\HrEmployeeAlertsWidget::class, key('workplace-hr-alerts'))
                </div>

                <div class="nt-workplace__area nt-workplace__area--small">
                    @livewire(\App\Filament\Widgets\Hr\HrWorkflowWidget::class, key('workplace-hr-workflow'))
                </div>

                <div class="nt-workplace__area nt-workplace__area--half">
                    @livewire(\App\Filament\Widgets\Hr\HrUpcomingEventsWidget::class, key('workplace-hr-events'))
                </div>

                <div class="nt-workplace__area nt-workplace__area--half">
                    @livewire(\App\Filament\Widgets\Hr\HrQuickActionsWidget::class, key('workplace-hr-actions'))
                </div>
            </div>
        @else
            <div class="nt-workplace__grid">
                <div class="nt-workplace__area nt-workplace__area--full">
                    @livewire(\App\Filament\Widgets\WorkplacePlaceholderWidget::class, key('workplace-placeholder'))
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
