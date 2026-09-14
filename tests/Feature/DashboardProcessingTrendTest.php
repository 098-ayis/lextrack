<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardProcessingTrendTest extends TestCase
{
    public function test_daily_counts_deduplicate_papers_and_include_zero_activity_days(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-14 12:00:00'));
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('action_type');
            $table->timestamps();
        });
        foreach ([
            [1, 'Document accepted', '2026-09-14 08:00:00'],
            [1, 'Document updated', '2026-09-14 09:00:00'],
            [2, 'Document completed', '2026-09-14 10:00:00'],
            [1, 'Document updated', '2026-09-13 10:00:00'],
            [3, 'Document downloaded', '2026-09-14 10:00:00'],
            [4, 'Document completed', '2026-09-07 10:00:00'],
            [5, 'Document completed', '2026-09-15 10:00:00'],
        ] as [$id, $action, $date]) {
            DB::table('activity_logs')->insert(['document_id' => $id, 'action_type' => $action, 'created_at' => $date]);
        }
        $trend = (new Dashboard)->getProcessingTrend();
        $this->assertCount(14, $trend['days']);
        $this->assertSame('Sep 01', $trend['days'][0]['date']);
        $this->assertSame(0, $trend['days'][0]['count']);
        $this->assertSame(2, $trend['today']);
        $this->assertSame(1, $trend['yesterday']);
        $this->assertSame(4, $trend['total']);
        $this->assertSame(4, $trend['change']);
        $this->assertEquals(0.3, $trend['average']);
    }
}
