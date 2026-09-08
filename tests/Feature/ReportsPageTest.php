<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    public function test_filters_and_totals_include_all_matching_pages(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->unsignedBigInteger('user_id')->nullable();
            foreach (['document_type', 'office_unit', 'status', 'lao_number', 'particulars'] as $field) {
                $table->string($field);
            }
            $table->timestamps();
        });
        for ($i = 1; $i <= 17; $i++) {
            DB::table('documents')->insert(['document_type' => 'Contract', 'office_unit' => 'Legal', 'status' => 'completed', 'lao_number' => 'LAO-'.$i, 'particulars' => 'Review', 'created_at' => '2026-09-08 23:59:59']);
        }
        DB::table('documents')->insert(['document_type' => 'Proposal', 'office_unit' => 'Other', 'status' => 'pending', 'lao_number' => 'EXCLUDED', 'particulars' => 'Other', 'created_at' => '2026-09-09 00:00:00']);
        $page = new Reports;
        $page->from = '2026-09-01';
        $page->to = '2026-09-08';
        $page->type = 'Contract';
        $page->office = 'Legal';
        $page->status = 'completed';
        $method = new \ReflectionMethod(Reports::class, 'getViewData');
        $data = $method->invoke($page);
        $this->assertSame(17, $data['total']);
        $this->assertSame(17, $data['documents']->total());
        $this->assertCount(15, $data['documents']);
        $this->assertSame(17, $data['counts']['completed']);
        $page->search = 'missing';
        $this->assertSame(0, $method->invoke($page)['total']);
    }
}
