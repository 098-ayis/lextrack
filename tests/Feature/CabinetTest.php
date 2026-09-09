<?php

namespace Tests\Feature;

use App\Filament\Pages\Cabinet;
use App\Models\DocumentType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CabinetTest extends TestCase
{
    public function test_custom_types_are_subfolders_of_others_and_choices_put_others_last(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('document_types', function (Blueprint $table) {
            $table->id('type_id');
            $table->string('type_name');
        });
        Schema::create('documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->string('document_type');
            $table->string('office_unit');
            $table->timestamps();
        });
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id('version_id');
            $table->unsignedBigInteger('document_id');
            $table->timestamps();
        });
        DB::table('document_types')->insert([
            ['type_name' => 'Others'], ['type_name' => 'Proposal'], ['type_name' => 'MOA'],
        ]);
        DB::table('documents')->insert([
            ['document_type' => 'Affidavit', 'office_unit' => 'CS'],
            ['document_type' => 'Affidavit', 'office_unit' => 'CN'],
            ['document_type' => 'Custom Letter', 'office_unit' => 'CS'],
            ['document_type' => ' moa ', 'office_unit' => 'CS'],
        ]);
        $page = new Cabinet;
        $page->loadCabinet();
        $this->assertArrayNotHasKey('Affidavit', $page->cabinet);
        $this->assertCount(2, $page->cabinet['Others']['Affidavit']);
        $this->assertCount(1, $page->cabinet['Others']['Custom Letter']);
        $this->assertCount(1, $page->cabinet['MOA']['CS']);
        $page->openType('Others');
        $page->openOffice('Affidavit');
        $this->assertSame('Others', $page->currentType);
        $this->assertSame('Affidavit', $page->currentOffice);
        $this->assertSame(['MOA', 'Proposal', 'Others'], DocumentType::query()->orderedForChoices()->pluck('type_name')->all());
    }
}
