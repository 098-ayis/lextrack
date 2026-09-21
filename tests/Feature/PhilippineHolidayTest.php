<?php

namespace Tests\Feature;

use App\Services\PhilippineHolidayService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhilippineHolidayTest extends TestCase
{
    public function test_verified_holidays_are_automatic_and_available_without_network(): void
    {
        Http::preventStrayRequests();
        $service = new PhilippineHolidayService;
        $august = $service->events(2026, 8);
        $this->assertCount(2, $august);
        $this->assertSame('National Heroes Day', $august->firstWhere('date', '2026-08-31')->event);
        $this->assertTrue($august->first()->is_automatic_holiday);
        $this->assertSame('holiday', $august->first()->category);
        $this->assertCount(1, $service->events(2026, 3, '2026-03-20'));
        Http::assertNothingSent();
    }

    public function test_2027_nationwide_proclaimed_holidays_are_available_offline(): void
    {
        Http::preventStrayRequests();
        $service = new PhilippineHolidayService;
        $holidays = collect(range(1, 12))
            ->flatMap(fn (int $month) => $service->events(2027, $month));

        $this->assertCount(20, $holidays);
        $this->assertSame(
            'EDSA People Power Anniversary (special working day)',
            $holidays->firstWhere('date', '2027-02-25')->event,
        );
        $this->assertSame('Maundy Thursday', $holidays->firstWhere('date', '2027-03-25')->event);
        $this->assertTrue($holidays->every(fn ($holiday) => $holiday->is_automatic_holiday));
        Http::assertNothingSent();
    }

    public function test_simeon_ola_day_repeats_every_september_second(): void
    {
        config(['cache.default' => 'array', 'holidays.feed_url' => 'https://example.test/holidays.ics']);
        Cache::flush();
        Http::fake(['*' => Http::response('')]);

        $service = new PhilippineHolidayService;

        foreach ([2026, 2027, 2030] as $year) {
            $date = sprintf('%04d-09-02', $year);
            $event = $service->events($year, 9, $date)->sole();

            $this->assertSame('Simeon Ola Day', $event->event);
            $this->assertSame('holiday', $event->category);
            $this->assertSame(
                'Special non-working holiday in Albay (Republic Act No. 11136)',
                $event->details,
            );
        }

        Http::assertSentCount(1);
    }

    public function test_feed_refreshes_once_and_last_good_dates_survive_outages(): void
    {
        config(['cache.default' => 'array', 'holidays.feed_url' => 'https://example.test/holidays.ics']);
        Cache::flush();
        Http::fake(['*' => Http::response("BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20280101\r\nSUMMARY:New Year's\r\n Day\r\nEND:VEVENT\r\nEND:VCALENDAR")]);
        $service = new PhilippineHolidayService;
        $this->assertSame("New Year'sDay", $service->events(2028, 1)->first()->event);
        $this->assertCount(1, $service->events(2028, 1));
        Http::assertSentCount(1);
        $this->travel(2)->days();
        Http::fake(['*' => Http::response('', 503)]);
        $this->assertCount(1, $service->events(2028, 1));
    }
}
