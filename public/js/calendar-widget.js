document.addEventListener('alpine:init', () => {
    window.calendarWidget = function(livewire, calendarEvents) {
        return {
            calendar: null,
            _skipFirstDatesSet: true,
            _cleanup: null,

            init() {
                const calendarEl = this.$el.querySelector('#calendar');
                const titleEl = this.$el.querySelector('#calendar-title');

                this.calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'pt-br',
                    headerToolbar: false,
                    height: 'auto',
                    fixedWeekCount: false,
                    showNonCurrentDates: false,
                    events: calendarEvents,

                    datesSet: (info) => {
                        titleEl.innerText = info.view.title;
                        if (this._skipFirstDatesSet) {
                            this._skipFirstDatesSet = false;
                            return;
                        }
                        livewire.loadEventsForRange(info.startStr, info.endStr);
                    },

                    dateClick: (info) => {
                        const selected = this.$el.querySelectorAll('.dia-selecionado');
                        selected.forEach(el => el.classList.remove('dia-selecionado'));
                        info.dayEl.classList.add('dia-selecionado');
                        livewire.selectDate(info.dateStr);
                    }
                });

                this.calendar.render();

                this._cleanup = Livewire.on('calendar-events-loaded', ({ events }) => {
                    this.calendar.removeAllEvents();
                    (events || []).forEach(e => this.calendar.addEvent(e));
                });
            },

            destroy() {
                if (this._cleanup) this._cleanup();
            },

            limparVisual() {
                const selected = this.$el.querySelectorAll('.dia-selecionado');
                selected.forEach(el => el.classList.remove('dia-selecionado'));
            }
        }
    }
})