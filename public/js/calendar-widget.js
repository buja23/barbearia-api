document.addEventListener('alpine:init', () => {
    window.calendarWidget = function(livewire) {
        return {
            calendar: null,
            init() {
                this.calendar = new FullCalendar.Calendar(
                    this.$el.querySelector('#calendar'),
                    {
                        initialView: 'dayGridMonth',
                        locale: 'pt-br', // Senior touch: calendário em português
                        selectable: true,
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth'
                        },
                        dateClick: (info) => {
                            // SENIOR FIX: Chamada direta ao método do PHP via proxy
                            livewire.selectDate(info.dateStr);
                            console.log('Calendário enviou a data:', info.dateStr);
                        },
                    }
                );
                this.calendar.render();
            }
        }
    }
})