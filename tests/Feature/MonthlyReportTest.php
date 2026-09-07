<?php

namespace Tests\Feature;

use App\Services\MonthlyReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonthlyReportTest extends TestCase
{
    public function test_monthly_counts_use_events_and_do_not_double_count_documents(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->unsignedBigInteger('type_id')->nullable();
            $table->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('document_id');
            $table->string('action_type');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();
        });
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->string('status');
            $table->date('date_processed')->nullable();
        });
        DB::table('documents')->insert([
            ['document_id' => 1, 'created_at' => '2026-08-01 00:00:00'],
            ['document_id' => 2, 'created_at' => '2026-09-01 00:00:00'],
        ]);
        foreach ([
            [1, 'Document completed', '2026-09-01 00:00:00'],
            [1, 'Document completed', '2026-09-30 23:59:59'],
            [2, 'Document downloaded', '2026-09-15 12:00:00'],
            [2, 'Document completed', '2026-10-01 00:00:00'],
        ] as [$document, $action, $date]) {
            DB::table('activity_logs')->insert(['document_id' => $document, 'action_type' => $action, 'created_at' => $date]);
        }
        DB::table('document_requests')->insert([
            ['status' => 'accepted', 'date_processed' => '2026-09-30'],
            ['status' => 'pending', 'date_processed' => null],
            ['status' => 'rejected', 'date_processed' => '2026-10-01'],
        ]);
        $report = app(MonthlyReportService::class)->generate('2026-09');
        $this->assertSame(1, $report['received']);
        $this->assertSame(1, $report['processed']);
        $this->assertSame(1, $report['completed']);
        $this->assertSame(1, $report['requests']);
        $this->assertCount(2, $report['activities']);
        $this->assertSame(0, app(MonthlyReportService::class)->generate('2025-01')['processed']);
    }

    public function test_guests_cannot_open_reports(): void
    {
        $this->get('/admin/reports/monthly?month=2026-09')->assertRedirect('/login');
    }
}
