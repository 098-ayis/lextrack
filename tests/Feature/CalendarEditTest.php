<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar as CalendarPage;
use App\Models\Calendar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CalendarEditTest extends TestCase
{
    public function test_edit_action_can_be_resolved_from_its_button_name(): void
    {
        $action = (new CalendarPage)->getAction('editEvent');
        $this->assertNotNull($action);
        $this->assertSame('editEvent', $action->getName());
    }

    public function test_edit_action_resolves_and_updates_only_the_selected_event(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('SQLite PDO driver is required for the database update test.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('calendars', function (Blueprint $table) {
            $table->id('sched_id');
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->time('time');
            $table->string('event');
            $table->text('details');
            $table->timestamp('reminder_3_days_sent_at')->nullable();
            $table->timestamp('reminder_1_day_sent_at')->nullable();
            $table->timestamp('reminder_10_minutes_sent_at')->nullable();
            $table->timestamps();
        });
        $original = ['user_id' => 1, 'date' => '2026-09-08', 'time' => '09:00', 'event' => 'Meeting', 'details' => 'Office'];
        $event = Calendar::create($original);
        $other = Calendar::create($original);
        $event->forceFill(['reminder_1_day_sent_at' => now()])->save();

        $page = new CalendarPage;
        $action = $page->getAction('editEvent');
        $this->assertNotNull($action);
        $this->assertSame('editEvent', $action->getName());

        ($action->getActionFunction())([
            'event' => 'Updated meeting', 'details' => 'New details',
            'date' => '2026-09-10', 'time' => '14:30',
        ], ['eventId' => $event->sched_id]);

        $event->refresh();
        $this->assertSame('Updated meeting', $event->event);
        $this->assertSame('New details', $event->details);
        $this->assertSame('2026-09-10', $event->date->format('Y-m-d'));
        $this->assertSame('14:30', $event->time->format('H:i'));
        $this->assertSame(1, $event->user_id);
        $this->assertNull($event->reminder_1_day_sent_at);
        $this->assertSame('Meeting', $other->fresh()->event);
    }
}
