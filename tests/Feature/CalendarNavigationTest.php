<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar as CalendarPage;
use App\Filament\Pages\Dashboard;
use App\Models\Calendar;
use Illuminate\Http\Request;
use Tests\TestCase;

class CalendarNavigationTest extends TestCase
{
    public function test_calendar_opens_the_requested_date_and_month(): void
    {
        $this->app->instance('request', Request::create('/admin/calendar?date=2027-01-12'));
        $page = new CalendarPage;
        $page->mount();

        $this->assertSame('2027-01-12', $page->selectedDate);
        $this->assertSame(2027, $page->year);
        $this->assertSame(1, $page->month);
    }

    public function test_invalid_date_is_ignored(): void
    {
        $this->app->instance('request', Request::create('/admin/calendar?date=2026-02-31'));
        $page = new CalendarPage;
        $page->mount();

        $this->assertNull($page->selectedDate);
        $this->assertSame(now()->month, $page->month);
    }

    public function test_completion_uses_the_event_date_and_time_or_end_of_day(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 7)->setTime(12, 0));

        $this->assertTrue((new Calendar(['date' => '2026-09-07', 'time' => '09:00']))->is_completed);
        $this->assertFalse((new Calendar(['date' => '2026-09-07', 'time' => '15:00']))->is_completed);
        $this->assertFalse((new Calendar(['date' => '2026-09-07']))->is_completed);
        $this->assertTrue((new Calendar(['date' => '2026-09-06']))->is_completed);
        $this->assertFalse((new Calendar(['date' => '2026-09-08', 'time' => '09:00']))->is_completed);
    }

    public function test_day_is_checked_only_when_all_its_events_are_completed(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 7)->setTime(12, 0));
        $dashboard = new class extends Dashboard {
            public function getCalendarEvents()
            {
                return collect([
                    new Calendar(['date' => '2026-09-06', 'time' => '09:00']),
                    new Calendar(['date' => '2026-09-07', 'time' => '09:00']),
                    new Calendar(['date' => '2026-09-07', 'time' => '15:00']),
                ]);
            }
        };
        $dashboard->mount();
        $cells = collect($dashboard->getCalendarCells())->keyBy('date');

        $this->assertTrue($cells['2026-09-06']['isCompleted']);
        $this->assertFalse($cells['2026-09-07']['isCompleted']);
        $this->assertFalse($cells['2026-09-08']['isCompleted']);
    }
}
