<div x-data="calendarWidget(@this)" x-init="init()" class="w-full">
    <div id="calendar"></div>

    <div x-text="'Data selecionada: ' + '{{ $selectedDate }}'"></div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script>
    window.calendarWidget = function(livewire) {
        return {
            calendar: null,
            init() {
                this.calendar = new FullCalendar.Calendar(
                    document.getElementById('calendar'),
                    {
                        initialView: 'dayGridMonth',
                        selectable: true,
                        dateClick: (info) => {
                            livewire.call('selectDate', info.dateStr);
                        },
                    }
                );
                this.calendar.render();
            }
        }
    }
</script>
@endpush
