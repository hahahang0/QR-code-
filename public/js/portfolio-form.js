(() => {
    const form = document.querySelector('#portfolio-form');

    if (!form) {
        return;
    }

    function nextIndex(list) {
        let highestIndex = -1;

        list.querySelectorAll('[name]').forEach((input) => {
            const match = /\[(\d+)\]/u.exec(input.name);

            if (match) {
                highestIndex = Math.max(highestIndex, Number(match[1]));
            }
        });

        return highestIndex + 1;
    }

    function updateDateRange(item) {
        if (!item) {
            return false;
        }

        const startDateInput = item.querySelector('[name$="[start_date]"]');
        const endDateInput = item.querySelector('[name$="[end_date]"], [name$="[completion_date]"]');

        if (!endDateInput) {
            return false;
        }

        endDateInput.min = startDateInput?.value || '';

        const hasInvalidRange = startDateInput?.value && endDateInput.value && endDateInput.value < startDateInput.value;
        endDateInput.setCustomValidity(hasInvalidRange ? 'The end date must be on or after the start date.' : '');

        return Boolean(hasInvalidRange);
    }

    function initializeCalendars(root = form) {
        root.querySelectorAll('input[type="date"]:not([data-calendar-ready])').forEach((dateInput) => {
            dateInput.dataset.calendarReady = 'true';

            const field = dateInput.closest('.field');
            const label = field?.querySelector('label')?.textContent.trim() || 'Date';
            const wrapper = document.createElement('div');
            wrapper.className = 'date-calendar';

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'date-calendar-trigger';
            trigger.setAttribute('aria-haspopup', 'dialog');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.setAttribute('aria-label', `Choose ${label.toLowerCase()}`);

            const triggerText = document.createElement('span');
            const calendarIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            calendarIcon.setAttribute('viewBox', '0 0 24 24');
            calendarIcon.setAttribute('aria-hidden', 'true');
            calendarIcon.innerHTML = '<rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 11h18"></path>';
            trigger.append(triggerText, calendarIcon);

            const popover = document.createElement('div');
            popover.className = 'date-calendar-popover';
            popover.hidden = true;
            popover.setAttribute('role', 'dialog');
            popover.setAttribute('aria-label', `${label} calendar`);

            const header = document.createElement('div');
            header.className = 'date-calendar-header';
            const previous = document.createElement('button');
            previous.type = 'button';
            previous.className = 'date-calendar-nav';
            previous.setAttribute('aria-label', 'Previous month');
            previous.textContent = '‹';
            const title = document.createElement('button');
            title.type = 'button';
            title.className = 'date-calendar-title';
            title.setAttribute('aria-label', 'Choose month and year');
            const next = document.createElement('button');
            next.type = 'button';
            next.className = 'date-calendar-nav';
            next.setAttribute('aria-label', 'Next month');
            next.textContent = '›';
            header.append(previous, title, next);

            const days = document.createElement('div');
            days.className = 'date-calendar-days';
            days.setAttribute('role', 'grid');
            const chooser = document.createElement('div');
            chooser.className = 'date-calendar-chooser';
            chooser.hidden = true;

            const monthSelect = document.createElement('select');
            monthSelect.setAttribute('aria-label', 'Month');
            const monthFormatter = new Intl.DateTimeFormat(undefined, { month: 'long' });

            for (let month = 0; month < 12; month += 1) {
                monthSelect.add(new Option(monthFormatter.format(new Date(2024, month, 1)), String(month)));
            }

            const yearSelect = document.createElement('select');
            yearSelect.setAttribute('aria-label', 'Year');
            const currentYear = new Date().getFullYear();
            const years = new Set();

            for (let year = currentYear + 20; year >= currentYear - 100; year -= 1) {
                years.add(year);
            }

            const existingYear = Number(dateInput.value.slice(0, 4));

            if (existingYear) {
                years.add(existingYear);
            }

            [...years].sort((left, right) => right - left).forEach((year) => {
                yearSelect.add(new Option(String(year), String(year)));
            });

            const showDates = document.createElement('button');
            showDates.type = 'button';
            showDates.className = 'date-calendar-continue';
            showDates.textContent = 'Choose a day';
            chooser.append(monthSelect, yearSelect, showDates);

            popover.append(header, days, chooser);
            wrapper.append(trigger, popover);

            let activeDate = dateInput.value ? new Date(`${dateInput.value}T12:00:00`) : new Date();
            let visibleMonth = activeDate.getMonth();
            let visibleYear = activeDate.getFullYear();

            function updateTrigger() {
                triggerText.textContent = dateInput.value
                    ? new Date(`${dateInput.value}T12:00:00`).toLocaleDateString()
                    : 'Select a date';
                trigger.setAttribute('aria-label', dateInput.value ? `${label}: ${triggerText.textContent}` : `Choose ${label.toLowerCase()}`);
            }

            function renderCalendar() {
                title.textContent = new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' })
                    .format(new Date(visibleYear, visibleMonth, 1));
                previous.disabled = visibleYear === Number(dateInput.min.slice(0, 4)) && visibleMonth <= Number(dateInput.min.slice(5, 7)) - 1;
                days.replaceChildren();

                ['S', 'M', 'T', 'W', 'T', 'F', 'S'].forEach((dayName) => {
                    const weekday = document.createElement('span');
                    weekday.className = 'date-calendar-weekday';
                    weekday.setAttribute('role', 'columnheader');
                    weekday.textContent = dayName;
                    days.append(weekday);
                });

                const firstWeekday = new Date(visibleYear, visibleMonth, 1).getDay();
                const daysInMonth = new Date(visibleYear, visibleMonth + 1, 0).getDate();

                for (let blank = 0; blank < firstWeekday; blank += 1) {
                    const spacer = document.createElement('span');
                    spacer.className = 'date-calendar-spacer';
                    days.append(spacer);
                }

                for (let day = 1; day <= daysInMonth; day += 1) {
                    const dayButton = document.createElement('button');
                    const dayValue = String(day).padStart(2, '0');
                    const monthValue = String(visibleMonth + 1).padStart(2, '0');
                    const isoDate = `${visibleYear}-${monthValue}-${dayValue}`;
                    dayButton.type = 'button';
                    dayButton.className = 'date-calendar-day';
                    dayButton.setAttribute('role', 'gridcell');
                    dayButton.textContent = String(day);
                    dayButton.disabled = Boolean(dateInput.min && isoDate < dateInput.min);

                    if (dateInput.value === isoDate) {
                        dayButton.classList.add('is-selected');
                        dayButton.setAttribute('aria-selected', 'true');
                    }

                    dayButton.addEventListener('click', () => {
                        dateInput.value = isoDate;
                        dateInput.dispatchEvent(new Event('input', { bubbles: true }));
                        dateInput.dispatchEvent(new Event('change', { bubbles: true }));
                        updateTrigger();
                        popover.hidden = true;
                        trigger.setAttribute('aria-expanded', 'false');
                        trigger.focus();
                    });

                    days.append(dayButton);
                }
            }

            function openCalendar() {
                const initialDateValue = dateInput.value || dateInput.min;
                activeDate = initialDateValue ? new Date(`${initialDateValue}T12:00:00`) : new Date();
                visibleMonth = activeDate.getMonth();
                visibleYear = activeDate.getFullYear();
                chooser.hidden = true;
                days.hidden = false;
                renderCalendar();
                popover.hidden = false;
                trigger.setAttribute('aria-expanded', 'true');
            }

            trigger.addEventListener('click', () => {
                if (popover.hidden) {
                    openCalendar();
                } else {
                    popover.hidden = true;
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });

            previous.addEventListener('click', () => {
                visibleMonth -= 1;

                if (visibleMonth < 0) {
                    visibleMonth = 11;
                    visibleYear -= 1;
                }

                renderCalendar();
            });

            next.addEventListener('click', () => {
                visibleMonth += 1;

                if (visibleMonth > 11) {
                    visibleMonth = 0;
                    visibleYear += 1;
                }

                renderCalendar();
            });

            title.addEventListener('click', () => {
                monthSelect.value = String(visibleMonth);
                yearSelect.value = String(visibleYear);
                days.hidden = true;
                chooser.hidden = false;
            });

            showDates.addEventListener('click', () => {
                visibleMonth = Number(monthSelect.value);
                visibleYear = Number(yearSelect.value);
                chooser.hidden = true;
                days.hidden = false;
                renderCalendar();
            });

            dateInput.hidden = true;
            dateInput.tabIndex = -1;
            dateInput.setAttribute('aria-hidden', 'true');
            dateInput.insertAdjacentElement('afterend', wrapper);
            updateTrigger();
        });
    }

    initializeCalendars();
    form.querySelectorAll('[data-repeater-item]').forEach(updateDateRange);

    form.addEventListener('input', (event) => {
        if (event.target.matches('input[type="date"]')) {
            updateDateRange(event.target.closest('[data-repeater-item]'));
        }
    });

    form.addEventListener('submit', (event) => {
        const invalidItem = [...form.querySelectorAll('[data-repeater-item]')].find(updateDateRange);

        if (invalidItem) {
            event.preventDefault();
            invalidItem.querySelector('[name$="[end_date]"], [name$="[completion_date]"]')?.reportValidity();
        }
    });

    form.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-add-row]');

        if (addButton) {
            const section = addButton.dataset.addRow;
            const list = form.querySelector(`[data-repeater-list="${section}"]`);
            const template = document.querySelector(`#${section}-template`);

            if (!list || !template) {
                return;
            }

            const index = nextIndex(list);
            const content = template.innerHTML.replaceAll('__INDEX__', String(index)).trim();
            const temporary = document.createElement('template');
            temporary.innerHTML = content;
            list.append(temporary.content.cloneNode(true));
            initializeCalendars(list.lastElementChild);
            updateDateRange(list.lastElementChild);
            list.lastElementChild?.querySelector('input:not([type="hidden"]), textarea, select, button:not([data-remove-row])')?.focus();

            return;
        }

        const removeButton = event.target.closest('[data-remove-row]');
        removeButton?.closest('[data-repeater-item]')?.remove();

        if (!event.target.closest('.date-calendar')) {
            form.querySelectorAll('.date-calendar-popover:not([hidden])').forEach((popover) => {
                popover.hidden = true;
                popover.previousElementSibling?.setAttribute('aria-expanded', 'false');
            });
        }
    });

    form.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            form.querySelectorAll('.date-calendar-popover:not([hidden])').forEach((popover) => {
                popover.hidden = true;
                popover.previousElementSibling?.setAttribute('aria-expanded', 'false');
            });
        }
    });

    form.addEventListener('click', async (event) => {
        const copyButton = event.target.closest('[data-copy-url]');

        if (!copyButton) {
            return;
        }

        try {
            await navigator.clipboard.writeText(copyButton.dataset.copyUrl);
            copyButton.textContent = 'Copied';
        } catch {
            const temporary = document.createElement('textarea');
            temporary.value = copyButton.dataset.copyUrl;
            temporary.style.position = 'fixed';
            temporary.style.opacity = '0';
            document.body.append(temporary);
            temporary.select();
            document.execCommand('copy');
            temporary.remove();
            copyButton.textContent = 'Copied';
        }

        window.setTimeout(() => {
            copyButton.textContent = 'Copy URL';
        }, 1600);
    });
})();
