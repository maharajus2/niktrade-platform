<div class="space-y-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">График работы</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Индивидуальные рабочие дни и смены сотрудника.
                </p>
            </div>

            @if ($employee->isIndividualSchedule())
                <div class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-medium text-primary-700 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-200">
                    FullCalendar
                </div>
            @endif
        </div>

        @if (! $employee->isIndividualSchedule())
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                Для сотрудника выбран тип графика: <span class="font-semibold">{{ $employee->getScheduleTypeLabel() }}</span>.
                Календарь используется только для индивидуального графика.
            </div>
        @else
            <div
                wire:ignore
                x-data
                x-init="
                    (async () => {
                        const loadAsset = (selector, createElement) => new Promise((resolve, reject) => {
                            const existing = document.querySelector(selector)

                            if (existing) {
                                if (existing.dataset.loaded === 'true' || existing.tagName === 'LINK') {
                                    resolve()

                                    return
                                }

                                existing.addEventListener('load', () => resolve(), { once: true })
                                existing.addEventListener('error', () => reject(new Error('Calendar asset failed to load.')), { once: true })

                                return
                            }

                            const element = createElement()
                            element.onload = () => {
                                element.dataset.loaded = 'true'
                                resolve()
                            }
                            element.onerror = () => reject(new Error('Calendar asset failed to load.'))
                            document.head.appendChild(element)
                        })

                        await loadAsset('link[data-nt-employee-schedule-css]', () => {
                            const link = document.createElement('link')
                            link.rel = 'stylesheet'
                            link.href = '{{ asset('css/employee-schedule-calendar.css') }}?v=20260703-ranges'
                            link.dataset.ntEmployeeScheduleCss = 'true'

                            return link
                        })

                        await loadAsset('script[data-nt-employee-schedule-js]', () => {
                            const script = document.createElement('script')
                            script.src = '{{ asset('js/employee-schedule-calendar.js') }}?v=20260703-ranges'
                            script.dataset.ntEmployeeScheduleJs = 'true'

                            return script
                        })

                        window.niktradeEmployeeScheduleCalendar($el, $wire, {
                            canUpdate: @js($canUpdate),
                            canEditPast: @js($canEditPast),
                        })
                    })()
                "
                class="nt-fullcalendar min-h-[680px]"
            ></div>
        @endif
    </div>
</div>
