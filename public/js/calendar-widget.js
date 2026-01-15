document.addEventListener('alpine:init', () => {
    window.calendarWidget = function (livewire) {
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
                        // Dentro do seu dateClick do FullCalendar:
                        dateClick: (info) => {
                            // DISPATCH envia a data para a página ListAppointments
                            Livewire.dispatch('filtrar-data', { date: info.dateStr });

                            // Opcional: Feedback visual no console para você testar
                            console.log('Calendário enviou a data:', info.dateStr);
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