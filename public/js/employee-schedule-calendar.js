(function () {
    window.niktradeEmployeeScheduleCalendar = function (element, wire, options) {
        const fullCalendarVersion = '6.1.21'
        const canUpdate = Boolean(options.canUpdate)
        const canEditPast = Boolean(options.canEditPast)
        const canCreateShift = Boolean(options.canCreateShift)
        const typeLabels = options.typeOptions || {}
        const futureTypeLabels = options.futureTypeOptions || {}
        const visibilityLabels = options.visibilityOptions || {}
        const sourceLabels = options.sourceOptions || {}
        const productionCalendar = options.productionCalendar || {}
        const holidayLabels = productionCalendar.holidays || {}
        const forceAllDayTypes = ['day_off', 'vacation', 'sick_leave', 'business_trip']
        const requestOnlyTypes = ['day_off', 'vacation', 'sick_leave']
        const calendarRoot = element.closest('.nik-calendar')
        let selectedDate = null
        let mobileMonthDate = null
        let pendingMobileMonthDate = null
        let rawEventPayloads = []
        const typeDefaults = {
            shift: { allDay: false, visibility: 'manager' },
            day_off: { allDay: true, visibility: 'manager' },
            vacation: { allDay: true, visibility: 'hr' },
            sick_leave: { allDay: true, visibility: 'hr' },
            business_trip: { allDay: true, visibility: 'manager' },
            training: { allDay: false, visibility: 'manager' },
            medical_exam: { allDay: false, visibility: 'hr' },
            document_reminder: { allDay: true, visibility: 'hr' },
            workflow_event: { allDay: true, visibility: 'hr' },
            custom: { allDay: false, visibility: 'private' },
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

        selectedDate = formatDate(new Date())
        mobileMonthDate = new Date(`${selectedDate.slice(0, 7)}-01T12:00:00`)

        const escapeHtml = (value) => String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;')

        const isWeekendDate = (date) => [0, 6].includes(date.getDay())

        const holidayLabel = (dateKey) => holidayLabels[dateKey] || ''

        const isHolidayDate = (dateKey) => Boolean(holidayLabel(dateKey))

        const monthStart = (date) => new Date(date.getFullYear(), date.getMonth(), 1, 12, 0, 0)

        const addMonths = (date, months) => new Date(date.getFullYear(), date.getMonth() + months, 1, 12, 0, 0)

        const isSameMonth = (left, right) => {
            return left
                && right
                && left.getFullYear() === right.getFullYear()
                && left.getMonth() === right.getMonth()
        }

        const monthTitle = (date) => date.toLocaleDateString('ru-RU', {
            month: 'long',
            year: 'numeric',
        })

        const monthButtonLabel = (monthIndex) => new Date(2026, monthIndex, 1, 12, 0, 0)
            .toLocaleDateString('ru-RU', { month: 'short' })
            .replace('.', '')

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

        const isMobile = () => window.matchMedia('(max-width: 820px)').matches

        const refetch = () => {
            if (element._ntFullCalendar) {
                element._ntFullCalendar.refetchEvents()
            }
        }

        const agendaContainer = () => calendarRoot?.querySelector('[data-nt-calendar-agenda]')

        const updateSelectedDayLabel = () => {
            const label = calendarRoot?.querySelector('[data-nt-selected-day-label]')

            if (! label) {
                return
            }

            const date = new Date(`${selectedDate}T12:00:00`)
            label.textContent = date.toLocaleDateString('ru-RU', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
            })

            if (typeof syncMobileStripButtons === 'function') {
                syncMobileStripButtons()
            }
        }

        const filterValues = () => ({
            type: calendarRoot?.querySelector('[data-nt-filter="type"]')?.value || '',
            visibility: calendarRoot?.querySelector('[data-nt-filter="visibility"]')?.value || '',
            source: calendarRoot?.querySelector('[data-nt-filter="source"]')?.value || '',
        })

        const filteredPayloads = (events) => {
            const filters = filterValues()

            return events.filter((event) => {
                const props = event.extendedProps || {}

                return (! filters.type || props.type === filters.type)
                    && (! filters.visibility || props.visibility === filters.visibility)
                    && (! filters.source || props.source === filters.source)
            })
        }

        const renderAgenda = (events) => {
            const container = agendaContainer()

            if (! container) {
                return
            }

            if (isMobile()) {
                const upcoming = events
                    .map((event) => ({
                        title: event.title,
                        start: event.start,
                        allDay: event.allDay,
                        props: event.extendedProps || {},
                    }))
                    .sort((a, b) => String(a.props.date || '').localeCompare(String(b.props.date || ''))
                        || String(a.props.starts_at || '').localeCompare(String(b.props.starts_at || '')))

                if (upcoming.length === 0) {
                    container.innerHTML = '<div class="nik-calendar-empty">В выбранном периоде нет событий.</div>'

                    return
                }

                const groups = upcoming.reduce((carry, event) => {
                    const key = event.props.date || (event.start ? formatDate(event.start) : selectedDate)
                    carry[key] = carry[key] || []
                    carry[key].push(event)

                    return carry
                }, {})

                container.innerHTML = Object.entries(groups).map(([dateKey, group]) => {
                    const date = new Date(`${dateKey}T12:00:00`)
                    const label = date.toLocaleDateString('ru-RU', {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                    })

                    return `
                        <section class="nik-calendar-agenda-group">
                            <h3>${label}</h3>
                            ${group.map((event) => {
                                const time = event.allDay ? 'Весь день' : `${event.props.starts_at || ''}${event.props.ends_at ? `–${event.props.ends_at}` : ''}`
                                const type = event.props.type || 'custom'
                                const typeLabel = event.props.type_label || typeLabels[type] || 'Событие'

                                return `
                                    <article class="nik-calendar-agenda-card is-${type}">
                                        <time>${time}</time>
                                        <div>
                                            <strong>${event.props.display_title || event.title}</strong>
                                            <span>${typeLabel}${event.props.source_label ? ` · ${event.props.source_label}` : ''}</span>
                                        </div>
                                        ${event.props.visibility_label ? `<em>${event.props.visibility_label}</em>` : ''}
                                    </article>
                                `
                            }).join('')}
                        </section>
                    `
                }).join('')

                return
            }

            const todayEvents = events
                .map((event) => ({
                    title: event.title,
                    start: event.start,
                    allDay: event.allDay,
                    props: event.extendedProps || {},
                }))
                .filter((event) => (event.props.date || (event.start ? formatDate(event.start) : null)) === selectedDate)
                .sort((a, b) => String(a.props.starts_at || '').localeCompare(String(b.props.starts_at || '')))

            if (todayEvents.length === 0) {
                container.innerHTML = '<div class="nik-calendar-empty">На выбранный день нет событий.</div>'

                return
            }

            container.innerHTML = todayEvents.map((event) => {
                const time = event.allDay ? 'Весь день' : `${event.props.starts_at || ''}${event.props.ends_at ? `–${event.props.ends_at}` : ''}`
                const type = event.props.type || 'custom'
                const typeLabel = event.props.type_label || typeLabels[type] || 'Событие'
                const visibility = event.props.visibility_label || ''

                return `
                    <article class="nik-calendar-agenda-card is-${type}">
                        <time>${time}</time>
                        <div>
                            <strong>${event.props.display_title || event.title}</strong>
                            <span>${typeLabel}${event.props.source_label ? ` · ${event.props.source_label}` : ''}</span>
                        </div>
                        ${visibility ? `<em>${visibility}</em>` : ''}
                    </article>
                `
            }).join('')
        }

        const updateExternalTitle = (calendar) => {
            const title = calendarRoot?.querySelector('[data-nt-calendar-title]')

            if (title) {
                title.textContent = isMobile() && calendar.view.type === 'dayGridMonth' && mobileMonthDate
                    ? monthTitle(mobileMonthDate)
                    : calendar.view.title
            }
        }

        const syncViewButtons = (viewType) => {
            calendarRoot?.querySelectorAll('[data-nt-calendar-view]').forEach((button) => {
                button.classList.toggle('is-active', button.dataset.ntCalendarView === viewType)
            })

            calendarRoot?.classList.toggle('is-calendar-month', viewType === 'dayGridMonth')
            calendarRoot?.classList.toggle('is-calendar-week', viewType === 'timeGridWeek')
            calendarRoot?.classList.toggle('is-calendar-day', viewType === 'timeGridDay')
            calendarRoot?.classList.toggle('is-calendar-list', viewType === 'listWeek')
        }

        const syncMobileStripButtons = () => {
            calendarRoot?.querySelectorAll('[data-nt-mobile-date]').forEach((button) => {
                button.classList.toggle('is-active', button.dataset.ntMobileDate === selectedDate)
            })
        }

        const renderMobileStrip = (calendar) => {
            const strip = calendarRoot?.querySelector('.nik-calendar-mobile-strip')

            if (! strip || ! calendar || calendar.view.type !== 'dayGridMonth') {
                return
            }

            const currentStart = monthStart(mobileMonthDate || calendar.getDate())
            const currentEnd = addMonths(currentStart, 1)
            const selected = new Date(`${selectedDate}T12:00:00`)
            const selectedInMonth = selected >= currentStart && selected < currentEnd
            const events = calendar.getEvents()
            const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']
            const dates = []

            for (const date = new Date(currentStart); date < currentEnd; date.setDate(date.getDate() + 1)) {
                dates.push(new Date(date))
            }

            if (! selectedInMonth && dates[0]) {
                selectedDate = formatDate(dates[0])
                updateSelectedDayLabel()
            }

            strip.innerHTML = dates.map((date) => {
                const dateKey = formatDate(date)
                const dayEvents = events.filter((event) => {
                    const props = event.extendedProps || {}

                    return (props.date || (event.start ? formatDate(event.start) : null)) === dateKey
                }).slice(0, 3)
                const classes = [
                    date.getMonth() === currentStart.getMonth() ? '' : 'is-muted',
                    dateKey === selectedDate ? 'is-active' : '',
                    isWeekendDate(date) ? 'is-weekend is-non-working' : '',
                    isHolidayDate(dateKey) ? 'is-holiday is-non-working' : '',
                ].filter(Boolean).join(' ')
                const title = holidayLabel(dateKey)
                const dots = dayEvents.length > 0
                    ? `<i>${dayEvents.map((event) => `<b style="background: ${event.backgroundColor || event.borderColor || '#1677ff'}"></b>`).join('')}</i>`
                    : ''

                return `
                    <button type="button" class="${classes}" data-nt-mobile-date="${dateKey}"${title ? ` title="${escapeHtml(title)}"` : ''}>
                        <span>${weekdays[date.getDay() === 0 ? 6 : date.getDay() - 1]}</span>
                        <strong>${date.getDate()}</strong>
                        ${dots}
                    </button>
                `
            }).join('')

            const active = strip.querySelector('.is-active')

            if (active && isMobile()) {
                window.requestAnimationFrame(() => {
                    active.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' })
                })
            }
        }

        const hideHolidayTooltip = () => {
            calendarRoot?.querySelector('.nik-calendar-holiday-tooltip')?.remove()
        }

        const showHolidayTooltip = (button) => {
            const dateKey = button?.dataset.ntMobileDate
            const label = dateKey ? holidayLabel(dateKey) : ''

            hideHolidayTooltip()

            if (! label || ! calendarRoot) {
                return
            }

            const tooltip = document.createElement('div')
            tooltip.className = 'nik-calendar-holiday-tooltip'
            tooltip.textContent = label
            calendarRoot.appendChild(tooltip)

            const rootRect = calendarRoot.getBoundingClientRect()
            const buttonRect = button.getBoundingClientRect()
            const tooltipRect = tooltip.getBoundingClientRect()
            const left = buttonRect.left - rootRect.left + (buttonRect.width / 2) - (tooltipRect.width / 2)
            const top = buttonRect.top - rootRect.top - tooltipRect.height - 10

            tooltip.style.left = `${Math.max(12, Math.min(left, rootRect.width - tooltipRect.width - 12))}px`
            tooltip.style.top = `${Math.max(12, top)}px`

            window.clearTimeout(calendarRoot._ntHolidayTooltipTimer)
            calendarRoot._ntHolidayTooltipTimer = window.setTimeout(hideHolidayTooltip, 3200)
        }

        const refetchWithFilters = () => {
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

                        <div class="nt-schedule-form__date-grid">
                            <label>
                                <span>Дата начала</span>
                                <input type="date" name="start_date" required>
                            </label>

                            <label>
                                <span>Дата окончания</span>
                                <input type="date" name="end_date" required>
                            </label>
                        </div>

                        <div class="nt-schedule-modal__hint" data-nt-edit-range-hint hidden>
                            Сейчас редактируется выбранный день. Редактирование всего периода будет добавлено позже.
                        </div>

                        <div class="nt-schedule-modal__hint" data-nt-request-hint hidden>
                            Для отпуска, больничного или выходного создайте заявку, если у вас нет прав HR/руководителя.
                        </div>

                        <div class="nt-schedule-modal__hint" data-nt-shift-hint hidden>
                            Ручные смены доступны только для индивидуального графика. Для 5/2, 2/2 и гибкого графика смены будут генерироваться позже.
                        </div>

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
                            <span>Видимость</span>
                            <select name="visibility"></select>
                        </label>

                        <label>
                            <span>Источник</span>
                            <select name="source"></select>
                        </label>

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
                if (value === 'shift' && ! canCreateShift) {
                    option.disabled = true
                    option.textContent = `${label} · недоступно`
                }
                typeSelect.appendChild(option)
            })
            Object.entries(futureTypeLabels).forEach(([value, label]) => {
                const option = document.createElement('option')
                option.value = value
                option.textContent = `${label} · скоро`
                option.disabled = true
                typeSelect.appendChild(option)
            })

            const visibilitySelect = modal.querySelector('[name="visibility"]')
            Object.entries(visibilityLabels).forEach(([value, label]) => {
                const option = document.createElement('option')
                option.value = value
                option.textContent = label
                visibilitySelect.appendChild(option)
            })

            const sourceSelect = modal.querySelector('[name="source"]')
            Object.entries(sourceLabels).forEach(([value, label]) => {
                const option = document.createElement('option')
                option.value = value
                option.textContent = label
                sourceSelect.appendChild(option)
            })

            modal.querySelectorAll('[data-nt-schedule-close]').forEach((closeButton) => {
                closeButton.addEventListener('click', () => closeModal(modal))
            })

            modal.querySelector('[name="type"]').addEventListener('change', () => updateModalVisibility(modal, true))
            modal.querySelector('[name="is_all_day"]').addEventListener('change', () => updateModalVisibility(modal, false))
            modal.querySelector('[name="start_date"]').addEventListener('change', () => syncDateRange(modal))

            return modal
        }

        const syncDateRange = (modal) => {
            const mode = modal.dataset.mode
            const type = modal.querySelector('[name="type"]').value
            const startDate = modal.querySelector('[name="start_date"]')
            const endDate = modal.querySelector('[name="end_date"]')

            if (mode === 'edit' || type === 'shift') {
                endDate.value = startDate.value
            }
        }

        const updateModalVisibility = (modal, typeChanged) => {
            const type = modal.querySelector('[name="type"]').value
            const titleWrapper = modal.querySelector('[data-nt-custom-title]')
            const allDayInput = modal.querySelector('[name="is_all_day"]')
            const endDate = modal.querySelector('[name="end_date"]')
            const timeFields = modal.querySelector('[data-nt-time-fields]')
            const startsAt = modal.querySelector('[name="starts_at"]')
            const endsAt = modal.querySelector('[name="ends_at"]')
            const visibility = modal.querySelector('[name="visibility"]')
            const requestHint = modal.querySelector('[data-nt-request-hint]')
            const shiftHint = modal.querySelector('[data-nt-shift-hint]')
            const isEditMode = modal.dataset.mode === 'edit'

            titleWrapper.hidden = type !== 'custom'

            if (typeChanged) {
                visibility.value = typeDefaults[type]?.visibility || 'private'
                if (forceAllDayTypes.includes(type)) {
                    allDayInput.checked = true
                } else if (type === 'shift') {
                    allDayInput.checked = false
                    startsAt.value = startsAt.value || '09:00'
                    endsAt.value = endsAt.value || '18:00'
                } else {
                    allDayInput.checked = Boolean(typeDefaults[type]?.allDay)
                }
            }

            syncDateRange(modal)

            endDate.disabled = isEditMode || type === 'shift'
            allDayInput.disabled = type === 'shift' || forceAllDayTypes.includes(type)
            timeFields.hidden = allDayInput.checked
            startsAt.required = ! allDayInput.checked
            endsAt.required = ! allDayInput.checked
            requestHint.hidden = ! requestOnlyTypes.includes(type)
            shiftHint.hidden = type !== 'shift' || canCreateShift
        }

        const closeModal = (modal) => {
            modal.classList.add('is-hidden')
        }

        const modalPayload = (modal) => {
            return {
                type: modal.querySelector('[name="type"]').value,
                title: modal.querySelector('[name="title"]').value,
                start_date: modal.querySelector('[name="start_date"]').value,
                end_date: modal.querySelector('[name="end_date"]').value,
                is_all_day: modal.querySelector('[name="is_all_day"]').checked,
                starts_at: modal.querySelector('[name="starts_at"]').value,
                ends_at: modal.querySelector('[name="ends_at"]').value,
                visibility: modal.querySelector('[name="visibility"]').value,
                source: modal.querySelector('[name="source"]').value,
                comment: modal.querySelector('[name="comment"]').value,
            }
        }

        const openModal = (mode, data) => {
            hideDayPopover()
            const modal = ensureModal()
            const form = modal.querySelector('form')
            const error = modal.querySelector('[data-nt-schedule-error]')
            const deleteButton = modal.querySelector('[data-nt-schedule-delete]')
            const editRangeHint = modal.querySelector('[data-nt-edit-range-hint]')

            form.reset()
            modal.dataset.mode = mode
            error.textContent = ''
            modal.querySelector('#nt-schedule-modal-title').textContent = mode === 'create' ? 'Новое событие' : 'Редактирование события'
            modal.querySelector('[name="id"]').value = data.id || ''
            modal.querySelector('[name="type"]').value = data.type || 'shift'
            modal.querySelector('[name="title"]').value = data.title || ''
            modal.querySelector('[name="start_date"]').value = data.start_date || data.date || formatDate(new Date())
            modal.querySelector('[name="end_date"]').value = data.end_date || data.date || data.start_date || formatDate(new Date())
            modal.querySelector('[name="is_all_day"]').checked = Boolean(data.is_all_day)
            modal.querySelector('[name="starts_at"]').value = data.starts_at || '09:00'
            modal.querySelector('[name="ends_at"]').value = data.ends_at || '18:00'
            modal.querySelector('[name="visibility"]').value = data.visibility || typeDefaults[data.type || 'custom']?.visibility || 'private'
            modal.querySelector('[name="source"]').value = data.source || 'manual'
            modal.querySelector('[name="comment"]').value = data.comment || ''
            deleteButton.hidden = mode === 'create' || ! data.editable
            editRangeHint.hidden = mode !== 'edit'
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

        const createPayloadForDate = (date) => ({
            type: canCreateShift ? 'shift' : 'custom',
            start_date: formatDate(date),
            end_date: formatDate(date),
            is_all_day: ! canCreateShift,
            starts_at: date.getHours() === 0 && date.getMinutes() === 0 ? '09:00' : formatTime(date),
            ends_at: date.getHours() === 0 && date.getMinutes() === 0 ? '18:00' : formatTime(addMinutes(date, 60)),
            visibility: canCreateShift ? 'manager' : 'private',
            source: 'manual',
            editable: true,
        })

        const editPayloadForEvent = (event) => {
            const props = event.extendedProps || {}

            return {
                id: event.id,
                type: props.type || 'shift',
                title: props.title || '',
                start_date: props.date || formatDate(event.start),
                end_date: props.date || formatDate(event.start),
                is_all_day: Boolean(props.is_all_day),
                starts_at: props.starts_at || '',
                ends_at: props.ends_at || '',
                visibility: props.visibility || 'hr',
                source: props.source || 'manual',
                comment: props.comment || '',
                editable: props.editable,
            }
        }

        const hideDayPopover = () => {
            calendarRoot?.querySelector('.nik-calendar-day-popover')?.remove()
        }

        const showDayPopover = (info, dayEvents) => {
            hideDayPopover()

            if (! calendarRoot) {
                return
            }

            const clickedDate = new Date(info.date)
            const dateKey = formatDate(clickedDate)
            const label = clickedDate.toLocaleDateString('ru-RU', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
            })
            const canCreate = canUpdate && (canEditPast || ! isPastDate(clickedDate))
            const holiday = holidayLabel(dateKey)
            const popover = document.createElement('section')
            popover.className = 'nik-calendar-day-popover'
            popover.setAttribute('role', 'dialog')
            popover.setAttribute('aria-label', 'События дня')
            popover.innerHTML = `
                <div class="nik-calendar-day-popover-head">
                    <div>
                        <span>${escapeHtml(label)}</span>
                        <strong>События дня</strong>
                    </div>
                    <button type="button" data-nt-day-popover-close aria-label="Закрыть">×</button>
                </div>
                ${holiday ? `<div class="nik-calendar-day-popover-holiday">${escapeHtml(holiday)}</div>` : ''}
                <div class="nik-calendar-day-popover-list">
                    ${dayEvents.length > 0 ? dayEvents.map((event) => {
                        const props = event.extendedProps || {}
                        const time = event.allDay ? 'Весь день' : `${props.starts_at || ''}${props.ends_at ? `–${props.ends_at}` : ''}`
                        const type = props.type || 'custom'
                        const typeLabel = props.type_label || typeLabels[type] || 'Событие'
                        const visibility = props.visibility_label || ''

                        return `
                            <button type="button" class="is-${type}" data-nt-day-event-id="${event.id}">
                                <time>${escapeHtml(time)}</time>
                                <span>
                                    <strong>${escapeHtml(props.display_title || event.title)}</strong>
                                    <small>${escapeHtml(typeLabel)}${visibility ? ` · ${escapeHtml(visibility)}` : ''}</small>
                                </span>
                            </button>
                        `
                    }).join('') : '<div class="nik-calendar-day-popover-empty">На выбранный день нет событий.</div>'}
                </div>
                <div class="nik-calendar-day-popover-actions">
                    <button type="button" data-nt-day-create ${canCreate ? '' : 'disabled'}>${canCreate ? 'Создать событие' : 'Создание недоступно'}</button>
                </div>
            `
            calendarRoot.appendChild(popover)

            const rootRect = calendarRoot.getBoundingClientRect()
            const targetRect = info.dayEl.getBoundingClientRect()
            const popoverRect = popover.getBoundingClientRect()
            const left = targetRect.left - rootRect.left + targetRect.width - popoverRect.width
            const top = targetRect.top - rootRect.top + 12

            popover.style.left = `${Math.max(12, Math.min(left, rootRect.width - popoverRect.width - 12))}px`
            popover.style.top = `${Math.max(12, top)}px`

            popover.querySelector('[data-nt-day-popover-close]')?.addEventListener('click', hideDayPopover)
            popover.querySelector('[data-nt-day-create]')?.addEventListener('click', () => {
                if (! canCreate) {
                    return
                }

                hideDayPopover()
                openModal('create', createPayloadForDate(clickedDate))
            })
            popover.querySelectorAll('[data-nt-day-event-id]').forEach((button) => {
                button.addEventListener('click', () => {
                    const event = dayEvents.find((item) => String(item.id) === button.dataset.ntDayEventId)
                    const props = event?.extendedProps || {}

                    if (! event) {
                        return
                    }

                    hideDayPopover()

                    if (! canUpdate || ! props.editable) {
                        alert(`${event.title}`)

                        return
                    }

                    openModal('edit', editPayloadForEvent(event))
                })
            })
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

                let calendar = null

                calendar = new FullCalendar.Calendar(element, {
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
                    headerToolbar: false,
                    dayCellClassNames: (info) => {
                        const dateKey = formatDate(info.date)

                        return [
                            isWeekendDate(info.date) ? 'nt-calendar-day-weekend' : '',
                            isHolidayDate(dateKey) ? 'nt-calendar-day-holiday' : '',
                        ].filter(Boolean)
                    },
                    dayCellDidMount: (info) => {
                        const dateKey = formatDate(info.date)
                        const label = holidayLabel(dateKey)

                        if (! label || info.el.querySelector('.nt-calendar-holiday-label')) {
                            return
                        }

                        const frame = info.el.querySelector('.fc-daygrid-day-frame')

                        if (! frame) {
                            return
                        }

                        const badge = document.createElement('div')
                        badge.className = 'nt-calendar-holiday-label'
                        badge.textContent = label
                        frame.appendChild(badge)
                    },
                    dayHeaderClassNames: (info) => {
                        return isWeekendDate(info.date) ? ['nt-calendar-day-header-weekend'] : []
                    },
                    buttonText: {
                        today: 'Сегодня',
                        month: 'Месяц',
                        week: 'Неделя',
                        day: 'День',
                        list: 'Список',
                    },
                    views: {
                        listWeek: {
                            buttonText: 'Список',
                        },
                    },
                    events: (fetchInfo, successCallback, failureCallback) => {
                        wire.getCalendarEvents(fetchInfo.startStr, fetchInfo.endStr)
                            .then((events) => {
                                rawEventPayloads = events
                                successCallback(filteredPayloads(events))
                                window.setTimeout(() => {
                                    if (calendar) {
                                        renderAgenda(calendar.getEvents())
                                    }
                                }, 0)
                            })
                            .catch((error) => {
                                alert(errorMessage(error))
                                failureCallback(error)
                            })
                    },
                    dateClick: (info) => {
                        selectedDate = formatDate(info.date)
                        updateSelectedDayLabel()
                        renderAgenda(calendar ? calendar.getEvents() : [])

                        const clickedDate = new Date(info.date)
                        const dayEvents = calendar ? calendar.getEvents().filter((event) => {
                            const props = event.extendedProps || {}

                            return (props.date || (event.start ? formatDate(event.start) : null)) === selectedDate
                        }) : []

                        if (calendar.view.type === 'dayGridMonth' || isMobile()) {
                            showDayPopover(info, dayEvents)

                            return
                        }

                        if (! canUpdate) {
                            return
                        }

                        if (! canEditPast && isPastDate(clickedDate)) {
                            alert('Нельзя изменять прошедшие события.')

                            return
                        }

                        openModal('create', createPayloadForDate(clickedDate))
                    },
                    eventClick: (info) => {
                        const event = info.event
                        const props = event.extendedProps || {}
                        selectedDate = props.date || formatDate(event.start)
                        updateSelectedDayLabel()
                        renderAgenda(calendar ? calendar.getEvents() : [])

                        if (isMobile()) {
                            const dayEvents = calendar ? calendar.getEvents().filter((item) => {
                                const itemProps = item.extendedProps || {}

                                return (itemProps.date || (item.start ? formatDate(item.start) : null)) === selectedDate
                            }) : [event]

                            showDayPopover({ date: event.start, dayEl: info.el }, dayEvents)

                            return
                        }

                        if (! canUpdate || ! props.editable) {
                            alert(`${event.title}`)

                            return
                        }

                        openModal('edit', editPayloadForEvent(event))
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
                    eventsSet: (events) => renderAgenda(events),
                    datesSet: () => {
                        if (calendar) {
                            if (calendar.view.type === 'dayGridMonth') {
                                const calendarMonth = monthStart(calendar.getDate())

                                if (pendingMobileMonthDate && ! isSameMonth(calendarMonth, pendingMobileMonthDate)) {
                                    calendar.gotoDate(pendingMobileMonthDate)

                                    return
                                }

                                if (pendingMobileMonthDate && isSameMonth(calendarMonth, pendingMobileMonthDate)) {
                                    mobileMonthDate = pendingMobileMonthDate
                                    pendingMobileMonthDate = null
                                } else {
                                    mobileMonthDate = calendarMonth
                                }

                                const selected = new Date(`${selectedDate}T12:00:00`)
                                const currentStart = monthStart(mobileMonthDate)
                                const currentEnd = addMonths(currentStart, 1)

                                if (selected < currentStart || selected >= currentEnd) {
                                    selectedDate = formatDate(currentStart)
                                    updateSelectedDayLabel()
                                }
                            }

                            updateExternalTitle(calendar)
                            syncViewButtons(calendar.view.type)
                            renderMobileStrip(calendar)
                            syncPeriodPicker(calendar)
                        }
                    },
                })

                function closePeriodPicker() {
                    const picker = calendarRoot?.querySelector('[data-nt-period-picker]')

                    if (picker) {
                        picker.hidden = true
                    }
                }

                let lastPeriodMonthPointerAt = 0
                function selectPeriodMonth(button, event = null) {
                    event?.preventDefault()
                    event?.stopPropagation()
                    event?.stopImmediatePropagation?.()

                    if (! button || ! calendarRoot.contains(button)) {
                        return
                    }

                    const fallbackYear = (mobileMonthDate || calendar.getDate()).getFullYear()
                    const year = Number(calendarRoot?.querySelector('[data-nt-period-year]')?.value || fallbackYear)
                    const month = Number(button.dataset.ntPeriodMonth)
                    const targetDate = new Date(year, month, 1, 12, 0, 0)

                    goToMobileMonth(targetDate)
                    closePeriodPicker()
                }

                function syncPeriodPicker(targetCalendar) {
                    const picker = calendarRoot?.querySelector('[data-nt-period-picker]')
                    const months = calendarRoot?.querySelector('[data-nt-period-months]')
                    const yearSelect = calendarRoot?.querySelector('[data-nt-period-year]')

                    if (! picker || ! months || ! yearSelect || ! targetCalendar) {
                        return
                    }

                    const baseDate = monthStart(mobileMonthDate || targetCalendar.getDate())
                    const currentYear = baseDate.getFullYear()
                    const currentMonth = baseDate.getMonth()
                    const yearStart = currentYear - 4
                    const yearEnd = currentYear + 5

                    months.innerHTML = Array.from({ length: 12 }, (_, monthIndex) => `
                        <button type="button" data-nt-period-month="${monthIndex}" class="${monthIndex === currentMonth ? 'is-active' : ''}">
                            ${monthButtonLabel(monthIndex)}
                        </button>
                    `).join('')

                    yearSelect.innerHTML = Array.from({ length: yearEnd - yearStart + 1 }, (_, index) => {
                        const year = yearStart + index

                        return `<option value="${year}"${year === currentYear ? ' selected' : ''}>${year}</option>`
                    }).join('')

                    months.querySelectorAll('[data-nt-period-month]').forEach((button) => {
                        button.onpointerdown = (event) => {
                            lastPeriodMonthPointerAt = Date.now()
                            selectPeriodMonth(button, event)
                        }
                        button.onclick = (event) => {
                            if (Date.now() - lastPeriodMonthPointerAt < 500) {
                                event.preventDefault()
                                event.stopPropagation()
                                event.stopImmediatePropagation?.()

                                return
                            }

                            selectPeriodMonth(button, event)
                        }
                    })
                }

                function syncAfterPeriodChange() {
                    updateSelectedDayLabel()
                    updateExternalTitle(calendar)
                    syncViewButtons(calendar.view.type)
                    renderMobileStrip(calendar)
                    renderAgenda(calendar.getEvents())
                    syncPeriodPicker(calendar)
                    window.setTimeout(() => calendar.updateSize(), 0)
                }

                function goToMobileMonth(targetDate) {
                    const target = monthStart(targetDate)

                    pendingMobileMonthDate = target
                    mobileMonthDate = target
                    selectedDate = formatDate(target)
                    if (calendar.view.type !== 'dayGridMonth') {
                        calendar.changeView('dayGridMonth')
                    }
                    calendar.gotoDate(target)
                    syncAfterPeriodChange()
                    window.requestAnimationFrame(syncAfterPeriodChange)
                }

                function setMobileCalendarExpanded(expanded) {
                    const button = calendarRoot?.querySelector('[data-nt-mobile-calendar-expand]')

                    calendarRoot?.classList.toggle('is-mobile-calendar-expanded', expanded)

                    if (button) {
                        button.setAttribute('aria-expanded', expanded ? 'true' : 'false')
                        const label = button.querySelector('span')

                        if (label) {
                            label.textContent = expanded ? 'Свернуть календарь' : 'Развернуть календарь'
                        }
                    }

                    window.setTimeout(() => calendar.updateSize(), 0)
                    window.setTimeout(() => calendar.updateSize(), 250)
                }

                calendar.render()
                element._ntFullCalendar = calendar

                updateSelectedDayLabel()
                updateExternalTitle(calendar)
                syncViewButtons(calendar.view.type)
                renderMobileStrip(calendar)
                syncPeriodPicker(calendar)

                const navigatePeriod = (direction) => {
                    hideDayPopover()
                    const syncAfterNavigation = () => {
                        updateExternalTitle(calendar)
                        syncViewButtons(calendar.view.type)
                        renderMobileStrip(calendar)
                        renderAgenda(calendar.getEvents())
                        syncPeriodPicker(calendar)
                    }

                    if (calendar.view.type === 'dayGridMonth') {
                        const target = addMonths(mobileMonthDate || calendar.getDate(), direction)
                        pendingMobileMonthDate = target
                        mobileMonthDate = target
                        calendar.gotoDate(target)
                        selectedDate = formatDate(target)
                        updateSelectedDayLabel()
                        const title = calendarRoot?.querySelector('[data-nt-calendar-title]')

                        if (title) {
                            title.textContent = monthTitle(target)
                        }
                    } else if (direction < 0) {
                        calendar.prev()
                    } else {
                        calendar.next()
                    }

                    updateExternalTitle(calendar)
                    syncViewButtons(calendar.view.type)
                    window.setTimeout(() => {
                        syncAfterNavigation()
                    }, 0)
                    window.setTimeout(syncAfterNavigation, 250)
                    window.setTimeout(syncAfterNavigation, 800)

                    window.requestAnimationFrame(() => {
                        if (calendar.view.type === 'dayGridMonth') {
                            const title = calendarRoot?.querySelector('[data-nt-calendar-title]')

                            if (title && mobileMonthDate) {
                                title.textContent = monthTitle(mobileMonthDate)
                            }

                            renderMobileStrip(calendar)
                            syncPeriodPicker(calendar)
                        }
                    })
                }

                let lastNavigationAt = 0
                const guardedNavigatePeriod = (direction, event = null) => {
                    const now = Date.now()

                    if (now - lastNavigationAt < 280) {
                        return
                    }

                    lastNavigationAt = now
                    event?.preventDefault()
                    event?.stopPropagation()
                    navigatePeriod(direction)
                }

                const bindNavigationButton = (selector, direction) => {
                    const button = calendarRoot?.querySelector(selector)

                    if (! button) {
                        return
                    }

                    ;['pointerup', 'touchend', 'click'].forEach((eventName) => {
                        button.addEventListener(eventName, (event) => guardedNavigatePeriod(direction, event))
                    })
                }

                bindNavigationButton('[data-nt-calendar-prev]', -1)
                bindNavigationButton('[data-nt-calendar-next]', 1)

                calendarRoot?.addEventListener('click', (event) => {
                    const viewButton = event.target.closest('[data-nt-calendar-view]')
                    const previousButton = event.target.closest('[data-nt-calendar-prev]')
                    const nextButton = event.target.closest('[data-nt-calendar-next]')

                    if (viewButton && calendarRoot.contains(viewButton)) {
                        hideDayPopover()
                        pendingMobileMonthDate = null
                        calendar.changeView(viewButton.dataset.ntCalendarView)
                        if (calendar.view.type === 'dayGridMonth') {
                            mobileMonthDate = monthStart(calendar.getDate())
                        } else {
                            closePeriodPicker()
                            setMobileCalendarExpanded(false)
                        }
                        updateExternalTitle(calendar)
                        syncViewButtons(calendar.view.type)
                        renderMobileStrip(calendar)
                        syncPeriodPicker(calendar)
                        window.setTimeout(() => calendar.updateSize(), 0)

                        return
                    }

                    if (previousButton && calendarRoot.contains(previousButton)) {
                        guardedNavigatePeriod(-1, event)

                        return
                    }

                    if (nextButton && calendarRoot.contains(nextButton)) {
                        guardedNavigatePeriod(1, event)
                    }
                })

                calendarRoot?.querySelector('[data-nt-calendar-today]')?.addEventListener('click', () => {
                    hideDayPopover()
                    pendingMobileMonthDate = monthStart(new Date())
                    calendar.today()
                    selectedDate = formatDate(new Date())
                    mobileMonthDate = monthStart(new Date())
                    updateSelectedDayLabel()
                    updateExternalTitle(calendar)
                    syncViewButtons(calendar.view.type)
                    renderAgenda(calendar.getEvents())
                    renderMobileStrip(calendar)
                    syncPeriodPicker(calendar)
                })

                calendarRoot?.querySelector('[data-nt-mobile-calendar-expand]')?.addEventListener('click', () => {
                    if (calendar.view.type !== 'dayGridMonth') {
                        return
                    }

                    setMobileCalendarExpanded(! calendarRoot.classList.contains('is-mobile-calendar-expanded'))
                })

                calendarRoot?.querySelector('[data-nt-calendar-title-toggle]')?.addEventListener('click', (event) => {
                    if (calendar.view.type !== 'dayGridMonth') {
                        return
                    }

                    event.preventDefault()
                    event.stopPropagation()
                    syncPeriodPicker(calendar)
                    const picker = calendarRoot?.querySelector('[data-nt-period-picker]')

                    if (picker) {
                        picker.hidden = ! picker.hidden
                    }
                })

                calendarRoot?.querySelector('[data-nt-period-year]')?.addEventListener('change', (event) => {
                    const year = Number(event.target.value)
                    const activeMonth = (mobileMonthDate || calendar.getDate()).getMonth()

                    calendarRoot?.querySelectorAll('[data-nt-period-month]').forEach((button) => {
                        button.classList.toggle('is-active', Number(button.dataset.ntPeriodMonth) === activeMonth)
                    })
                    event.target.value = String(year)
                })

                document.addEventListener('click', (event) => {
                    if (! calendarRoot?.contains(event.target)) {
                        closePeriodPicker()
                    }
                })

                calendarRoot?.querySelectorAll('[data-nt-filter]').forEach((filter) => {
                    filter.addEventListener('change', refetchWithFilters)
                })

                calendarRoot?.querySelector('.nik-calendar-mobile-strip')?.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-nt-mobile-date]')

                    if (button) {
                        selectedDate = button.dataset.ntMobileDate
                        updateSelectedDayLabel()
                        syncMobileStripButtons()
                        renderAgenda(calendar.getEvents())

                        const clickedDate = new Date(`${selectedDate}T12:00:00`)
                        const dayEvents = calendar.getEvents().filter((item) => {
                            const itemProps = item.extendedProps || {}

                            return (itemProps.date || (item.start ? formatDate(item.start) : null)) === selectedDate
                        })

                        showDayPopover({ date: clickedDate, dayEl: button }, dayEvents)
                    }
                })

                syncMobileStripButtons()

                calendarRoot?.querySelector('[data-nt-calendar-filter-toggle]')?.addEventListener('click', () => {
                    calendarRoot?.querySelector('[data-nt-calendar-filters]')?.classList.toggle('is-open')
                })

                const addButton = calendarRoot?.querySelector('[data-nt-calendar-add]')
                if (addButton) {
                    addButton.onclick = () => openModal('create', {
                        type: canCreateShift ? 'shift' : 'custom',
                        start_date: formatDate(new Date()),
                        end_date: formatDate(new Date()),
                        is_all_day: ! canCreateShift,
                        starts_at: '09:00',
                        ends_at: '18:00',
                        visibility: canCreateShift ? 'manager' : 'private',
                        source: 'manual',
                        editable: true,
                    })
                }
            })
            .catch((error) => {
                element.innerHTML = `<div class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-sm text-danger-700">${error.message}</div>`
            })
    }
})()
