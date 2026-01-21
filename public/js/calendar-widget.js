document.addEventListener('alpine:init', () => {
    window.calendarWidget = function(livewire, calendarEvents) {
        return {
            init() {
                const calendarEl = this.$el.querySelector('#calendar');
                const titleEl = this.$el.querySelector('#calendar-title');

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'pt-br',
                    headerToolbar: false,
                    height: 'auto',
                    fixedWeekCount: false,
                    showNonCurrentDates: false,
                    events: calendarEvents,
                    
                    datesSet: (info) => titleEl.innerText = info.view.title,

                    dateClick: (info) => {
                        // Limpa seleção visual anterior
                        const selected = this.$el.querySelectorAll('.dia-selecionado');
                        selected.forEach(el => el.classList.remove('dia-selecionado'));
                        
                        // Adiciona seleção ao elemento pai (td) para o efeito de anel funcionar bem
                        info.dayEl.classList.add('dia-selecionado');
                        
                        livewire.selectDate(info.dateStr);
                    }
                });

                calendar.render();

                this.$el.querySelector('#prevBtn').addEventListener('click', () => calendar.prev());
                this.$el.querySelector('#nextBtn').addEventListener('click', () => calendar.next());
            }
        }
    }
})