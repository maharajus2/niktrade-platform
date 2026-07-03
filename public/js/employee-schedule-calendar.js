(function () {
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
                `https://cdn.jsdelivr.net/npm/@fullcalendar/core@${fullCalendarVersion}/locales-all.global.min.js`,
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
})()
