<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Barbershop;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    private BookingService $service;
    private Barbershop $barbershop;
    private Barber $barber;
    private Service $haircut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BookingService();

        $owner = User::factory()->barber()->create();
        $this->barbershop = Barbershop::factory()->create(['user_id' => $owner->id]);

        $this->barber = Barber::factory()->create([
            'barbershop_id' => $this->barbershop->id,
            'lunch_start'   => '12:00:00',
            'lunch_end'     => '13:00:00',
        ]);

        $this->haircut = Service::factory()->create([
            'barbershop_id'    => $this->barbershop->id,
            'duration_minutes' => 30,
        ]);
    }

    private function createOpeningHour(int $dayOfWeek, bool $closed = false): OpeningHour
    {
        return OpeningHour::factory()->create([
            'barbershop_id' => $this->barbershop->id,
            'day_of_week'   => $dayOfWeek,
            'opening_time'  => '09:00:00',
            'closing_time'  => '19:00:00',
            'is_closed'     => $closed,
        ]);
    }

    #[Test]
    public function returns_slots_on_a_normal_opening_day(): void
    {
        // Use next Monday to avoid weekend issues
        $monday = Carbon::now()->next(Carbon::MONDAY);
        $this->createOpeningHour($monday->dayOfWeek);

        $slots = $this->service->getAvailableSlots($this->barber, $monday->toDateString(), $this->haircut->id);

        $this->assertNotEmpty($slots);
        $this->assertContains('09:00', $slots);
        // Last slot must end at or before 19:00 → last valid start for 30min is 18:30
        $this->assertContains('18:30', $slots);
        $this->assertNotContains('19:00', $slots);
    }

    #[Test]
    public function returns_empty_when_day_is_closed(): void
    {
        $sunday = Carbon::now()->next(Carbon::SUNDAY);
        $this->createOpeningHour($sunday->dayOfWeek, closed: true);

        $slots = $this->service->getAvailableSlots($this->barber, $sunday->toDateString(), $this->haircut->id);

        $this->assertEmpty($slots);
    }

    #[Test]
    public function returns_empty_when_no_opening_hour_configured(): void
    {
        // No OpeningHour row for this day at all
        $date = Carbon::now()->next(Carbon::TUESDAY);

        $slots = $this->service->getAvailableSlots($this->barber, $date->toDateString(), $this->haircut->id);

        $this->assertEmpty($slots);
    }

    #[Test]
    public function excludes_lunch_break_slots(): void
    {
        $monday = Carbon::now()->next(Carbon::MONDAY);
        $this->createOpeningHour($monday->dayOfWeek);

        $slots = $this->service->getAvailableSlots($this->barber, $monday->toDateString(), $this->haircut->id);

        // 12:00 starts during lunch → should be excluded
        $this->assertNotContains('12:00', $slots);
        // 12:30 also during lunch
        $this->assertNotContains('12:30', $slots);
        // 13:00 is fine (lunch ends at 13:00)
        $this->assertContains('13:00', $slots);
    }

    #[Test]
    public function excludes_slots_overlapping_existing_appointment(): void
    {
        $monday = Carbon::now()->next(Carbon::MONDAY);
        $this->createOpeningHour($monday->dayOfWeek);

        $client = User::factory()->create();
        // Book 10:00–10:30
        Appointment::factory()->create([
            'barber_id'    => $this->barber->id,
            'service_id'   => $this->haircut->id,
            'user_id'      => $client->id,
            'barbershop_id'=> $this->barbershop->id,
            'scheduled_at' => $monday->copy()->setTime(10, 0),
            'end_at'       => $monday->copy()->setTime(10, 30),
            'status'       => 'confirmed',
        ]);

        $slots = $this->service->getAvailableSlots($this->barber, $monday->toDateString(), $this->haircut->id);

        $this->assertNotContains('10:00', $slots);
        // 09:45 overlaps with 10:00–10:30 appointment
        $this->assertNotContains('09:45', $slots);
        // 10:30 is free
        $this->assertContains('10:30', $slots);
    }

    #[Test]
    public function cancelled_appointment_does_not_block_slot(): void
    {
        $monday = Carbon::now()->next(Carbon::MONDAY);
        $this->createOpeningHour($monday->dayOfWeek);

        $client = User::factory()->create();
        Appointment::factory()->cancelled()->create([
            'barber_id'     => $this->barber->id,
            'service_id'    => $this->haircut->id,
            'user_id'       => $client->id,
            'barbershop_id' => $this->barbershop->id,
            'scheduled_at'  => $monday->copy()->setTime(10, 0),
            'end_at'        => $monday->copy()->setTime(10, 30),
        ]);

        $slots = $this->service->getAvailableSlots($this->barber, $monday->toDateString(), $this->haircut->id);

        $this->assertContains('10:00', $slots);
    }

    #[Test]
    public function long_service_fills_end_of_day(): void
    {
        $monday = Carbon::now()->next(Carbon::MONDAY);
        $this->createOpeningHour($monday->dayOfWeek);

        $longService = Service::factory()->create([
            'barbershop_id'    => $this->barbershop->id,
            'duration_minutes' => 60,
        ]);

        $slots = $this->service->getAvailableSlots($this->barber, $monday->toDateString(), $longService->id);

        // A 60-min service cannot start at 18:30 (would end at 19:30)
        $this->assertNotContains('18:30', $slots);
        // But 18:00 is fine (ends exactly at 19:00)
        $this->assertContains('18:00', $slots);
    }

    #[Test]
    public function returns_empty_when_service_not_found(): void
    {
        $monday = Carbon::now()->next(Carbon::MONDAY);
        $this->createOpeningHour($monday->dayOfWeek);

        $slots = $this->service->getAvailableSlots($this->barber, $monday->toDateString(), 99999);

        $this->assertEmpty($slots);
    }
}
