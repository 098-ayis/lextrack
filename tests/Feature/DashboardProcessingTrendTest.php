<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardProcessingTrendTest extends TestCase
{
    public function test_document_counts_follow_upload_dates_for_each_period(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-14 12:00:00'));
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->timestamps();
        });
        foreach ([
            [1, '2026-09-14 08:00:00'],
            [2, '2026-09-14 10:00:00'],
            [3, '2026-09-13 10:00:00'],
            [4, '2026-09-07 10:00:00'],
            [5, '2026-09-15 10:00:00'],
            [6, '2026-08-03 10:00:00'],
        ] as [$id, $date]) {
            DB::table('documents')->insert(['document_id' => $id, 'created_at' => $date]);
        }
        $trend = (new Dashboard)->getProcessingTrend();
        $this->assertSame('monthly', $trend['period']);
        $this->assertCount(12, $trend['buckets']);
        $this->assertSame('Jan', $trend['buckets'][0]['label']);
        $this->assertSame(0, $trend['buckets'][0]['count']);
        $this->assertSame(4, $trend['buckets'][8]['count']);
        $this->assertSame(4, $trend['total']);

        $dashboard = new Dashboard;
        $dashboard->setProcessingTrendPeriod('weekly');
        $weekly = $dashboard->getProcessingTrend();
        $this->assertSame('weekly', $weekly['period']);
        $this->assertCount(7, $weekly['buckets']);
        $this->assertSame(2, $weekly['buckets'][6]['count']);
        $this->assertSame(3, $weekly['total']);

        $dashboard->setProcessingTrendPeriod('yearly');
        $yearly = $dashboard->getProcessingTrend();
        $this->assertSame('yearly', $yearly['period']);
        $this->assertSame(['2022', '2023', '2024', '2025', '2026'], array_column($yearly['buckets'], 'label'));
        $this->assertSame(5, $yearly['buckets'][4]['count']);
        $this->assertSame(5, $yearly['total']);
    }
}
