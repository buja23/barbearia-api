document.addEventListener('alpine:init', () => {
    window.calendarWidget = function(livewire, calendarEvents) {
        return {
            init() {
                const calendar = new FullCalendar.Calendar(this.$el.querySelector('#calendar'), {
                    initialView: 'dayGridMonth',
                    locale: 'pt-br',
                    headerToolbar: { left: 'prev', center: 'title', right: 'next' },
                    height: 'auto', // Deixa o calendário compacto
                    contentHeight: 'auto',
                    fixedWeekCount: false, // Remove semanas vazias no final do mês
                    showNonCurrentDates: false, // Limpa o visual (opcional)
                    selectable: true,
                    events: calendarEvents,
                    
                    dateClick: (info) => {
                        livewire.selectDate(info.dateStr);
                        
                        // Classe para estilizar o dia selecionado
                        document.querySelectorAll('.dia-selecionado').forEach(el => el.classList.remove('dia-selecionado'));
                        info.dayEl.classList.add('dia-selecionado');
                    },
                });
                calendar.render();
            }
        }
    }
})