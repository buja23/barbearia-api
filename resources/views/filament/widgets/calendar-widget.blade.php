<div x-data="calendarWidget(@this)" class="w-full">
    <div id="calendar" wire:ignore></div> 
    <div class="mt-4">
        Data selecionada: <span x-text="'{{ $selectedDate }}'"></span>
    </div>
</div>