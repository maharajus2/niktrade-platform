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
                x-init="window.niktradeEmployeeScheduleCalendar($el, $wire, { canUpdate: @js($canUpdate) })"
                class="nt-fullcalendar min-h-[680px]"
            ></div>
        @endif
    </div>

    @once
        <script>
            window.niktradeEmployeeScheduleCalendar = function (element, wire, options) {
                const fullCalendarVersion = '6.1.21'
                const canUpdate = Boolean(options.canUpdate)

                const loadFullCalendar = () => {
                    const loadScript = (src, attributeName) => new Promise((resolve, reject) => {
                        const existingScript = document.querySelector(`[${attributeName}]`)

                        if (existingScript) {
                            if (existingScript.dataset.loaded === 'true') {
                                resolve()

                                return
                            }

                            existingScript.addEventListener('load', () => resolve(), { once: true })
                            existingScript.addEventListener('error', () => reject(new Error('FullCalendar failed to load.')), { once: true })

                            return
                        }

                        const script = document.createElement('script')
                        script.src = src
                        script.setAttribute(attributeName, 'true')
                        script.onload = () => {
                            script.dataset.loaded = 'true'
                            resolve()
                        }
                        script.onerror = () => reject(new Error('FullCalendar failed to load.'))
                        document.head.appendChild(script)
                    })

                    const calendarScript = window.FullCalendar
                        ? Promise.resolve()
                        : loadScript(
                        `https://cdn.jsdelivr.net/npm/fullcalendar@${fullCalendarVersion}/index.global.min.js`,
                        'data-nt-fullcalendar-js',
                    )

                    return calendarScript.then(() => loadScript(
                        `https://cdn.jsdelivr.net/npm/fullcalendar@${fullCalendarVersion}/locales-all.global.min.js`,
                        'data-nt-fullcalendar-locales-js',
                    ))
                }

                const formatDate = (date) => {
                    const year = date.getFullYear()
                    const month = String(date.getMonth() + 1).padStart(2, '0')
                    const day = String(date.getDate()).padStart(2, '0')

                    return `${year}-${month}-${day}`
                }

                const formatTime = (date) => {
                    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`
                }

                const addMinutes = (date, minutes) => {
                    return new Date(date.getTime() + minutes * 60000)
                }

                const defaultTimeRange = (date) => {
                    if (date.getHours() === 0 && date.getMinutes() === 0) {
                        return '09:00-18:00'
                    }

                    return `${formatTime(date)}-${formatTime(addMinutes(date, 60))}`
                }

                const parseTimeRange = (value) => {
                    const match = String(value || '').trim().match(/^(\d{2}:\d{2})\s*[-–]\s*(\d{2}:\d{2})$/)

                    if (! match) {
                        return null
                    }

                    return {
                        startsAt: match[1],
                        endsAt: match[2],
                    }
                }

                const errorMessage = (error) => {
                    const errors = error?.response?.data?.errors

                    if (errors) {
                        const firstKey = Object.keys(errors)[0]

                        if (firstKey && errors[firstKey]?.[0]) {
                            return errors[firstKey][0]
                        }
                    }

                    return error?.message || 'Не удалось сохранить график.'
                }

                const refetch = () => {
                    if (element._ntFullCalendar) {
                        element._ntFullCalendar.refetchEvents()
                    }
                }

                loadFullCalendar()
                    .then(() => {
                        if (element._ntFullCalendar) {
                            element._ntFullCalendar.destroy()
                        }

                        const calendar = new FullCalendar.Calendar(element, {
                            initialView: 'dayGridMonth',
                            locale: 'ru',
                            firstDay: 1,
                            nowIndicator: true,
                            selectable: canUpdate,
                            editable: canUpdate,
                            eventStartEditable: canUpdate,
                            eventDurationEditable: canUpdate,
                            dayMaxEvents: true,
                            height: 'auto',
                            expandRows: true,
                            allDaySlot: false,
                            slotMinTime: '07:00:00',
                            slotMaxTime: '23:00:00',
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay',
                            },
                            buttonText: {
                                today: 'Сегодня',
                                month: 'Месяц',
                                week: 'Неделя',
                                day: 'День',
                            },
                            events: (fetchInfo, successCallback, failureCallback) => {
                                wire.getCalendarEvents(fetchInfo.startStr, fetchInfo.endStr)
                                    .then((events) => successCallback(events))
                                    .catch((error) => {
                                        alert(errorMessage(error))
                                        failureCallback(error)
                                    })
                            },
                            dateClick: (info) => {
                                if (! canUpdate) {
                                    return
                                }

                                const clickedDate = new Date(info.date)

                                if (clickedDate < new Date(new Date().toDateString())) {
                                    alert('Нельзя изменять прошедшие смены.')

                                    return
                                }

                                const range = prompt('Время смены в формате 09:00-18:00', defaultTimeRange(clickedDate))
                                const parsed = parseTimeRange(range)

                                if (! parsed) {
                                    if (range !== null) {
                                        alert('Укажите время в формате 09:00-18:00.')
                                    }

                                    return
                                }

                                const comment = prompt('Комментарий к смене', '') || ''

                                wire.createCalendarEntry(formatDate(clickedDate), parsed.startsAt, parsed.endsAt, comment)
                                    .then(refetch)
                                    .catch((error) => alert(errorMessage(error)))
                            },
                            eventClick: (info) => {
                                const event = info.event
                                const props = event.extendedProps || {}

                                if (! canUpdate || ! props.editable) {
                                    alert(`${event.title}`)

                                    return
                                }

                                const currentRange = `${props.starts_at}-${props.ends_at}`
                                const range = prompt('Измените время смены или введите delete для удаления', currentRange)

                                if (range === null) {
                                    return
                                }

                                if (String(range).trim().toLowerCase() === 'delete') {
                                    if (! confirm('Удалить смену?')) {
                                        return
                                    }

                                    wire.deleteCalendarEntry(Number(event.id))
                                        .then(refetch)
                                        .catch((error) => alert(errorMessage(error)))

                                    return
                                }

                                const parsed = parseTimeRange(range)

                                if (! parsed) {
                                    alert('Укажите время в формате 09:00-18:00.')

                                    return
                                }

                                const comment = prompt('Комментарий к смене', props.comment || '') || ''

                                wire.updateCalendarEntry(Number(event.id), props.date, parsed.startsAt, parsed.endsAt, comment)
                                    .then(refetch)
                                    .catch((error) => alert(errorMessage(error)))
                            },
                            eventDrop: (info) => {
                                const event = info.event

                                wire.moveCalendarEntry(
                                    Number(event.id),
                                    formatDate(event.start),
                                    formatTime(event.start),
                                    formatTime(event.end || addMinutes(event.start, 60)),
                                )
                                    .then(refetch)
                                    .catch((error) => {
                                        info.revert()
                                        alert(errorMessage(error))
                                    })
                            },
                            eventResize: (info) => {
                                const event = info.event

                                wire.moveCalendarEntry(
                                    Number(event.id),
                                    formatDate(event.start),
                                    formatTime(event.start),
                                    formatTime(event.end || addMinutes(event.start, 60)),
                                )
                                    .then(refetch)
                                    .catch((error) => {
                                        info.revert()
                                        alert(errorMessage(error))
                                    })
                            },
                            eventAllow: (dropInfo) => {
                                return canUpdate && dropInfo.start >= new Date(new Date().toDateString())
                            },
                        })

                        calendar.render()
                        element._ntFullCalendar = calendar
                    })
                    .catch((error) => {
                        element.innerHTML = `<div class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-sm text-danger-700">${error.message}</div>`
                    })
            }
        </script>
    @endonce

    <style>
        .nt-fullcalendar .fc {
            --fc-border-color: rgb(229 231 235);
            --fc-button-bg-color: rgb(2 132 199);
            --fc-button-border-color: rgb(2 132 199);
            --fc-button-hover-bg-color: rgb(3 105 161);
            --fc-button-hover-border-color: rgb(3 105 161);
            --fc-button-active-bg-color: rgb(3 105 161);
            --fc-button-active-border-color: rgb(3 105 161);
            color: rgb(17 24 39);
            font-size: 0.875rem;
        }

        .dark .nt-fullcalendar .fc {
            --fc-border-color: rgb(55 65 81);
            color: rgb(243 244 246);
        }

        .nt-fullcalendar .fc-toolbar {
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .nt-fullcalendar .fc-toolbar-title {
            font-size: 1.125rem;
            font-weight: 700;
        }

        .nt-fullcalendar .fc-event {
            border-radius: 0.5rem;
            border: 1px solid rgb(16 185 129);
            background: rgb(209 250 229);
            color: rgb(6 78 59);
            padding: 0.125rem 0.25rem;
        }

        .nt-fullcalendar .nt-schedule-event-past {
            border-color: rgb(209 213 219);
            background: rgb(243 244 246);
            color: rgb(107 114 128);
        }

        .dark .nt-fullcalendar .fc-event {
            border-color: rgb(16 185 129 / 0.45);
            background: rgb(16 185 129 / 0.16);
            color: rgb(209 250 229);
        }

        .dark .nt-fullcalendar .nt-schedule-event-past {
            border-color: rgb(75 85 99);
            background: rgb(31 41 55);
            color: rgb(156 163 175);
        }

        @media (max-width: 768px) {
            .nt-fullcalendar {
                min-height: 620px;
            }

            .nt-fullcalendar .fc-header-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .nt-fullcalendar .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
            }
        }
    </style>
</div>
