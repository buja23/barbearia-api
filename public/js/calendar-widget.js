document.addEventListener('alpine:init', () => {
    window.calendarWidget = function(livewire) {
        return {
            calendar: null,
            init() {
                this.calendar = new FullCalendar.Calendar(
                    this.$el.querySelector('#calendar'),
                    {
                        initialView: 'dayGridMonth',
                        locale: 'pt-br', // Senior touch: garante o idioma
                        selectable: true,
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek'
                        },
                        dateClick: (info) => {
                            // disparamaos um evento para a página filtrar a tabela principal.
                            livewire.dispatch('filtrar-data', { date: info.dateStr });

                            // Feedback visual: opcionalmente você pode destacar o dia clicado aqui
                        },
                    }
                );
                this.calendar.render();

                livewire.on('refreshCalendar', (event) => {
                    this.calendar.refetchEvents();
                });
            }
        }
    }
})