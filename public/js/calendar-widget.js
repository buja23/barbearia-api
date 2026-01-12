document.addEventListener('alpine:init', () => {
    window.calendarWidget = function(livewire) {
        return {
            calendar: null,
            init() {
                this.calendar = new FullCalendar.Calendar(
                    this.$el.querySelector('#calendar'),
                    {
                        initialView: 'dayGridMonth',
                        selectable: true,
                        dateClick: (info) => {
                            livewire.call('selectDate', info.dateStr);
                        },
                    }
                );
                this.calendar.render();

                // Exemplo: Se você disparar um evento para atualizar o calendário
                livewire.on('refreshCalendar', (event) => {
                    this.calendar.refetchEvents();
                });
            }
        }
    }
})