<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CalendarCategoryTest extends TestCase
{
    public function test_new_category_is_saved_and_available_in_legend_with_its_color(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('calendar_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name')->unique();
            $table->string('color');
            $table->timestamps();
        });
        $page = new Calendar;
        $this->assertSame(['holiday' => 'Holidays', 'meeting' => 'Meetings', 'deadline' => 'Deadlines'], $page->getEventCategories());
        $key = $page->addEventCategory(['name' => 'Training', 'color' => '#2563eb']);
        $freshPage = new Calendar;
        $this->assertCount(4, $freshPage->getEventCategories());
        $this->assertSame('Training', $freshPage->getEventCategories()[$key]);
        $this->assertSame('#2563eb', $freshPage->getEventColor((object) ['category' => $key]));
        $this->assertSame('deadline', $freshPage->getEventCategory((object) ['category' => $key, 'is_document_deadline' => true]));
    }
}
