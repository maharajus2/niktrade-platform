<x-filament-panels::page>
    @if ($this->getContextKey() === \App\Support\Dashboard\DashboardWidgetRegistry::CONTEXT_EMPLOYEE)
        @include('filament.pages.partials.employee-workplace', ['workspace' => $employeeWorkspace])
    @else
    <style>
        .nt-workplace {
            width: 100%;
            max-width: none;
            margin: -1.25rem;
            min-height: calc(100vh - 5rem);
            padding: 1.75rem;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 26rem),
                linear-gradient(135deg, #f8fbff 0%, #f4f7fb 46%, #f8fafc 100%);
            border-radius: 26px;
            color: #0f172a;
        }

        .nt-workplace *,
        .nt-workplace *::before,
        .nt-workplace *::after {
            box-sizing: border-box;
        }

        .nt-workplace__header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .nt-workplace__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: .45rem;
            color: #2563eb;
            font-size: .82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .nt-workplace__title {
            font-size: clamp(1.55rem, 2.4vw, 2.05rem);
            line-height: 1.15;
            font-weight: 800;
            color: #0f172a;
        }

        .nt-workplace__date {
            margin-top: .45rem;
            color: #64748b;
            font-size: .96rem;
        }

        .nt-workplace__tools {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .nt-workplace__search {
            display: flex;
            min-width: 290px;
            height: 46px;
            align-items: center;
            gap: .65rem;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 14px;
            background: rgba(255, 255, 255, .88);
            padding: 0 .95rem;
            color: #94a3b8;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
            backdrop-filter: blur(12px);
        }

        .nt-workplace__tool,
        .nt-workplace__avatar {
            position: relative;
            display: inline-flex;
            width: 46px;
            height: 46px;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 14px;
            background: rgba(255, 255, 255, .9);
            color: #1e293b;
            font-weight: 700;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
        }

        .nt-workplace__avatar {
            border-radius: 999px;
            background: #0f172a;
            color: #fff;
        }

        .nt-workplace__badge {
            position: absolute;
            top: -6px;
            right: -6px;
            display: inline-flex;
            min-width: 20px;
            height: 20px;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: .72rem;
            line-height: 1;
        }

        .nt-workplace__grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 1.25rem;
            align-items: start;
        }

        .nt-workplace__span-3 { grid-column: span 3 / span 3; }
        .nt-workplace__span-4 { grid-column: span 4 / span 4; }
        .nt-workplace__span-5 { grid-column: span 5 / span 5; }
        .nt-workplace__span-6 { grid-column: span 6 / span 6; }
        .nt-workplace__span-8 { grid-column: span 8 / span 8; }
        .nt-workplace__span-9 { grid-column: span 9 / span 9; }
        .nt-workplace__span-12 { grid-column: span 12 / span 12; }

        .nt-workplace__stack {
            display: grid;
            gap: 1rem;
        }

        .nt-workplace__widget {
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, .22) !important;
            border-radius: 18px !important;
            background: rgba(255, 255, 255, .92) !important;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .07) !important;
        }

        .nt-workplace__placeholder {
            padding: 2rem;
        }

        .nt-workplace__placeholder-title {
            margin-bottom: .45rem;
            font-size: 1.1rem;
            font-weight: 800;
        }

        .nt-workplace__placeholder-text {
            color: #64748b;
        }

        @media (max-width: 1280px) {
            .nt-workplace__span-3,
            .nt-workplace__span-4,
            .nt-workplace__span-5,
            .nt-workplace__span-6 {
                grid-column: span 6 / span 6;
            }

            .nt-workplace__span-8,
            .nt-workplace__span-9 {
                grid-column: span 12 / span 12;
            }
        }

        @media (max-width: 820px) {
            .nt-workplace {
                margin: -.75rem;
                padding: 1rem;
                border-radius: 18px;
            }

            .nt-workplace__header {
                grid-template-columns: 1fr;
            }

            .nt-workplace__tools {
                width: 100%;
            }

            .nt-workplace__search {
                min-width: 0;
                flex: 1;
            }

            .nt-workplace__span-3,
            .nt-workplace__span-4,
            .nt-workplace__span-5,
            .nt-workplace__span-6,
            .nt-workplace__span-8,
            .nt-workplace__span-9,
            .nt-workplace__span-12 {
                grid-column: span 12 / span 12;
            }
        }
    </style>

    <div class="nt-workplace">
        <header class="nt-workplace__header">
            <div>
                <div class="nt-workplace__eyebrow">
                    <span>Рабочее пространство</span>
                    <span>{{ $this->getContextLabel() }}</span>
                </div>

                <div class="nt-workplace__title">
                    {{ $this->getGreeting() }}, {{ auth()->user()->name }}!
                </div>

                <div class="nt-workplace__date">
                    {{ now()->translatedFormat('l, d F Y') }}
                </div>
            </div>

            <div class="nt-workplace__tools" aria-label="Инструменты рабочего пространства">
                <div class="nt-workplace__search" aria-label="Поиск">
                    <span aria-hidden="true">⌕</span>
                    <span>Поиск...</span>
                </div>

                <div class="nt-workplace__tool" aria-label="Уведомления">
                    <span aria-hidden="true">!</span>
                </div>

                <div class="nt-workplace__avatar" aria-label="Профиль">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
            </div>
        </header>

        @if ($this->getContextKey() === \App\Support\Dashboard\DashboardWidgetRegistry::CONTEXT_HR)
            <div class="nt-workplace__grid">
                <div class="nt-workplace__span-6">
                    @livewire(\App\Filament\Widgets\Hr\HrTodayWidget::class, key('workplace-hr-today'))
                </div>

                <div class="nt-workplace__span-3">
                    @livewire(\App\Filament\Widgets\Hr\HrWorkflowWidget::class, key('workplace-hr-workflow'))
                </div>

                <div class="nt-workplace__span-3">
                    @livewire(\App\Filament\Widgets\Hr\HrQuickActionsWidget::class, key('workplace-hr-actions'))
                </div>

                <div class="nt-workplace__span-6">
                    @livewire(\App\Filament\Widgets\Hr\HrEmployeeAlertsWidget::class, key('workplace-hr-alerts'))
                </div>

                <div class="nt-workplace__span-6">
                    @livewire(\App\Filament\Widgets\Hr\HrUpcomingEventsWidget::class, key('workplace-hr-events'))
                </div>
            </div>
        @else
            <div class="nt-workplace__widget nt-workplace__placeholder">
                <div class="nt-workplace__placeholder-title">Рабочее пространство будет добавлено позже.</div>
                <div class="nt-workplace__placeholder-text">
                    Для выбранного контекста пока доступна только базовая навигация.
                </div>
            </div>
        @endif
    </div>
    @endif
</x-filament-panels::page>
