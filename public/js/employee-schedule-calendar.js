(function () {
    window.niktradeEmployeeScheduleCalendar = function (element, wire, options) {
        const fullCalendarVersion = '6.1.21'
        const canUpdate = Boolean(options.canUpdate)
        const canEditPast = Boolean(options.canEditPast)
        const typeLabels = {
            shift: 'Смена',
            day_off: 'Выходной',
            vacation: 'Отпуск',
            sick_leave: 'Больничный',
            custom: 'Другое событие',
        }

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

        const isPastDate = (date) => {
            return date < new Date(new Date().toDateString())
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

        const ensureModal = () => {
            let modal = document.querySelector('[data-nt-schedule-modal]')

            if (modal) {
                return modal
            }

            modal = document.createElement('div')
            modal.dataset.ntScheduleModal = 'true'
            modal.className = 'nt-schedule-modal is-hidden'
            modal.innerHTML = `
                <div class="nt-schedule-modal__backdrop" data-nt-schedule-close></div>
                <div class="nt-schedule-modal__panel" role="dialog" aria-modal="true" aria-labelledby="nt-schedule-modal-title">
                    <div class="nt-schedule-modal__header">
                        <div>
                            <h3 id="nt-schedule-modal-title">Событие графика</h3>
                            <p>Ночные смены через полночь пока оформляются двумя событиями.</p>
                        </div>
                        <button type="button" class="nt-schedule-modal__icon-button" data-nt-schedule-close aria-label="Закрыть">×</button>
                    </div>

                    <form class="nt-schedule-form">
                        <input type="hidden" name="id">

                        <label>
                            <span>Тип события</span>
                            <select name="type"></select>
                        </label>

                        <label data-nt-custom-title>
                            <span>Название</span>
                            <input type="text" name="title" maxlength="255" placeholder="Например: обучение">
                        </label>

                        <label>
                            <span>Дата</span>
                            <input type="date" name="date" required>
                        </label>

                        <label class="nt-schedule-checkbox">
                            <input type="checkbox" name="is_all_day">
                            <span>Весь день</span>
                        </label>

                        <div class="nt-schedule-form__time-grid" data-nt-time-fields>
                            <label>
                                <span>Время начала</span>
                                <input type="time" name="starts_at" step="60">
                            </label>

                            <label>
                                <span>Время окончания</span>
                                <input type="time" name="ends_at" step="60">
                            </label>
                        </div>

                        <label>
                            <span>Комментарий</span>
                            <textarea name="comment" rows="3"></textarea>
                        </label>

                        <div class="nt-schedule-modal__error" data-nt-schedule-error></div>

                        <div class="nt-schedule-modal__actions">
                            <button type="button" class="nt-schedule-button nt-schedule-button--danger" data-nt-schedule-delete>Удалить</button>
                            <div class="nt-schedule-modal__action-group">
                                <button type="button" class="nt-schedule-button nt-schedule-button--secondary" data-nt-schedule-close>Отмена</button>
                                <button type="submit" class="nt-schedule-button nt-schedule-button--primary">Сохранить</button>
                            </div>
                        </div>
                    </form>
                </div>
            `

            document.body.appendChild(modal)

            const typeSelect = modal.querySelector('[name="type"]')
            Object.entries(typeLabels).forEach(([value, label]) => {
                const option = document.createElement('option')
                option.value = value
                option.textContent = label
                typeSelect.appendChild(option)
            })

            modal.querySelectorAll('[data-nt-schedule-close]').forEach((closeButton) => {
                closeButton.addEventListener('click', () => closeModal(modal))
            })

            modal.querySelector('[name="type"]').addEventListener('change', () => updateModalVisibility(modal, true))
            modal.querySelector('[name="is_all_day"]').addEventListener('change', () => updateModalVisibility(modal, false))

            return modal
        }

        const updateModalVisibility = (modal, typeChanged) => {
            const type = modal.querySelector('[name="type"]').value
            const titleWrapper = modal.querySelector('[data-nt-custom-title]')
            const allDayInput = modal.querySelector('[name="is_all_day"]')
            const timeFields = modal.querySelector('[data-nt-time-fields]')
            const startsAt = modal.querySelector('[name="starts_at"]')
            const endsAt = modal.querySelector('[name="ends_at"]')
            const forceAllDayTypes = ['day_off', 'vacation', 'sick_leave']

            titleWrapper.hidden = type !== 'custom'

            if (typeChanged) {
                if (forceAllDayTypes.includes(type)) {
                    allDayInput.checked = true
                } else if (type === 'shift') {
                    allDayInput.checked = false
                    startsAt.value = startsAt.value || '09:00'
                    endsAt.value = endsAt.value || '18:00'
                }
            }

            allDayInput.disabled = type === 'shift' || forceAllDayTypes.includes(type)
            timeFields.hidden = allDayInput.checked
            startsAt.required = ! allDayInput.checked
            endsAt.required = ! allDayInput.checked
        }

        const closeModal = (modal) => {
            modal.classList.add('is-hidden')
        }

        const modalPayload = (modal) => {
            return {
                type: modal.querySelector('[name="type"]').value,
                title: modal.querySelector('[name="title"]').value,
                date: modal.querySelector('[name="date"]').value,
                is_all_day: modal.querySelector('[name="is_all_day"]').checked,
                starts_at: modal.querySelector('[name="starts_at"]').value,
                ends_at: modal.querySelector('[name="ends_at"]').value,
                comment: modal.querySelector('[name="comment"]').value,
            }
        }

        const openModal = (mode, data) => {
            const modal = ensureModal()
            const form = modal.querySelector('form')
            const error = modal.querySelector('[data-nt-schedule-error]')
            const deleteButton = modal.querySelector('[data-nt-schedule-delete]')

            form.reset()
            error.textContent = ''
            modal.querySelector('#nt-schedule-modal-title').textContent = mode === 'create' ? 'Новое событие' : 'Редактирование события'
            modal.querySelector('[name="id"]').value = data.id || ''
            modal.querySelector('[name="type"]').value = data.type || 'shift'
            modal.querySelector('[name="title"]').value = data.title || ''
            modal.querySelector('[name="date"]').value = data.date || formatDate(new Date())
            modal.querySelector('[name="is_all_day"]').checked = Boolean(data.is_all_day)
            modal.querySelector('[name="starts_at"]').value = data.starts_at || '09:00'
            modal.querySelector('[name="ends_at"]').value = data.ends_at || '18:00'
            modal.querySelector('[name="comment"]').value = data.comment || ''
            deleteButton.hidden = mode === 'create' || ! data.editable
            updateModalVisibility(modal, false)

            form.onsubmit = (event) => {
                event.preventDefault()
                error.textContent = ''

                const payload = modalPayload(modal)
                const request = mode === 'create'
                    ? wire.createCalendarEntry(payload)
                    : wire.updateCalendarEntry(Number(modal.querySelector('[name="id"]').value), payload)

                request
                    .then(() => {
                        closeModal(modal)
                        refetch()
                    })
                    .catch((requestError) => {
                        error.textContent = errorMessage(requestError)
                    })
            }

            deleteButton.onclick = () => {
                if (! confirm('Удалить событие?')) {
                    return
                }

                error.textContent = ''
                wire.deleteCalendarEntry(Number(modal.querySelector('[name="id"]').value))
                    .then(() => {
                        closeModal(modal)
                        refetch()
                    })
                    .catch((requestError) => {
                        error.textContent = errorMessage(requestError)
                    })
            }

            modal.classList.remove('is-hidden')
            modal.querySelector('[name="type"]').focus()
        }

        const eventMovePayload = (event) => {
            const allDay = Boolean(event.allDay)

            return {
                date: formatDate(event.start),
                is_all_day: allDay,
                starts_at: allDay ? null : formatTime(event.start),
                ends_at: allDay ? null : formatTime(event.end || addMinutes(event.start, 60)),
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
                    allDaySlot: true,
                    slotMinTime: '00:00:00',
                    slotMaxTime: '24:00:00',
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

                        if (! canEditPast && isPastDate(clickedDate)) {
                            alert('Нельзя изменять прошедшие события.')

                            return
                        }

                        openModal('create', {
                            type: 'shift',
                            date: formatDate(clickedDate),
                            is_all_day: false,
                            starts_at: info.date.getHours() === 0 && info.date.getMinutes() === 0 ? '09:00' : formatTime(info.date),
                            ends_at: info.date.getHours() === 0 && info.date.getMinutes() === 0 ? '18:00' : formatTime(addMinutes(info.date, 60)),
                            editable: true,
                        })
                    },
                    eventClick: (info) => {
                        const event = info.event
                        const props = event.extendedProps || {}

                        if (! canUpdate || ! props.editable) {
                            alert(`${event.title}`)

                            return
                        }

                        openModal('edit', {
                            id: event.id,
                            type: props.type || 'shift',
                            title: props.title || '',
                            date: props.date || formatDate(event.start),
                            is_all_day: Boolean(props.is_all_day),
                            starts_at: props.starts_at || '',
                            ends_at: props.ends_at || '',
                            comment: props.comment || '',
                            editable: props.editable,
                        })
                    },
                    eventDrop: (info) => {
                        const event = info.event

                        wire.moveCalendarEntry(Number(event.id), eventMovePayload(event))
                            .then(refetch)
                            .catch((error) => {
                                info.revert()
                                alert(errorMessage(error))
                            })
                    },
                    eventResize: (info) => {
                        const event = info.event

                        wire.moveCalendarEntry(Number(event.id), eventMovePayload(event))
                            .then(refetch)
                            .catch((error) => {
                                info.revert()
                                alert(errorMessage(error))
                            })
                    },
                    eventAllow: (dropInfo) => {
                        return canUpdate && (canEditPast || ! isPastDate(dropInfo.start))
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
