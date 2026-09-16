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
            $table->string('status')->default('in_progress');
            $table->timestamps();
        });
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id('version_id');
            $table->unsignedBigInteger('document_id');
            $table->timestamps();
        });
        (require database_path('migrations/2026_09_15_000000_create_cabinet_folders.php'))->up();
        (require database_path('migrations/2026_09_15_010000_create_cabinet_copies.php'))->up();
        (require database_path('migrations/2026_09_15_020000_add_cabinet_destinations.php'))->up();
        (require database_path('migrations/2026_09_15_030000_create_cabinet_recycle_bin.php'))->up();
        (require database_path('migrations/2026_09_15_040000_add_cabinet_copy_name.php'))->up();
        (require database_path('migrations/2026_09_15_050000_add_parent_to_cabinet_folders.php'))->up();
        (require database_path('migrations/2026_09_15_060000_add_recycled_at_to_cabinet_folders.php'))->up();
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
        $folder = DB::table('cabinet_folders')->insertGetId(['name' => 'Staff files']);
        DB::table('cabinet_document_locations')->insert(['document_id' => 1, 'folder_id' => $folder]);
        $empty = DB::table('cabinet_folders')->insertGetId(['name' => 'Empty folder']);
        $page->loadCabinet();
        $this->assertCount(1, $page->cabinet['Staff files']['Documents']);
        $this->assertSame(1, $page->folderFileCount('Staff files'));
        $this->assertCount(2, $page->cabinet['Others']['Affidavit']);
        $this->assertSame([], $page->cabinet['Empty folder']['Documents']);
        DB::table('cabinet_folders')->where('id', $empty)->update(['recycled_at' => now()]);
        $page->loadCabinet();
        $this->assertArrayNotHasKey('Empty folder', $page->cabinet);
        $this->assertArrayHasKey('Empty folder', $page->cabinet['Recycle Bin']);
        DB::table('cabinet_folders')->where('id', $empty)->update(['recycled_at' => null]);
        $page->loadCabinet();
        $page->openType('Empty folder');
        $this->assertSame('Empty folder', $page->currentType);
        $this->assertSame('Documents', $page->currentOffice);
        $page->goToRoot();
        DB::table('cabinet_folders')->insert(['name' => 'Nested folder', 'parent_type' => 'Empty folder', 'parent_office' => 'Documents']);
        $page->loadCabinet();
        $this->assertSame(0, $page->folderFileCount('Empty folder'));
        $page->openType('Nested folder');
        $this->assertSame('Nested folder', $page->currentType);
        $this->assertSame('Documents', $page->currentOffice);
        $this->assertSame(['Empty folder', 'Nested folder'], $page->folderBreadcrumbs());
        DB::table('cabinet_folders')->insert(['name' => 'hehe', 'parent_type' => 'DOD', 'parent_office' => 'CSSP']);
        $page->loadCabinet();
        $page->openType('hehe');
        $this->assertSame(['DOD', 'CSSP', 'hehe'], $page->folderBreadcrumbs());
        $page->goToRoot();
        $this->assertSame('Affidavit', DB::table('documents')->where('document_id', 1)->value('document_type'));
        $this->assertSame('CS', DB::table('documents')->where('document_id', 1)->value('office_unit'));
        DB::table('cabinet_document_locations')->where('document_id', 1)->delete();
        $page->loadCabinet();
        $this->assertCount(2, $page->cabinet['Others']['Affidavit']);
        DB::table('cabinet_copies')->insert(['document_id' => 1, 'folder_id' => $folder]);
        $page->loadCabinet();
        $this->assertCount(1, $page->cabinet['Staff files']['Documents']);
        $this->assertCount(2, $page->cabinet['Others']['Affidavit']);
        $this->assertSame(4, DB::table('documents')->count());
        $this->assertStringStartsWith('copy-', $page->cabinet['Staff files']['Documents'][0]['copy_key']);
        $this->assertContains('MOA / CS', $page->destinationOptions());
        DB::table('cabinet_document_locations')->insert(['document_id' => 4, 'folder_id' => null, 'cabinet_type' => 'Others', 'cabinet_office' => 'Custom Letter']);
        $page->loadCabinet();
        $this->assertCount(2, $page->cabinet['Others']['Custom Letter']);
        $this->assertCount(1, $page->cabinet['MOA']['CS']);
        $this->assertSame(' moa ', DB::table('documents')->where('document_id', 4)->value('document_type'));
        DB::table('cabinet_recycle_bin')->insert(['document_id' => 1]);
        $page->loadCabinet();
        $this->assertCount(1, $page->cabinet['Recycle Bin']['Documents']);
        $this->assertTrue(\App\Filament\Pages\RecycleBin::shouldRegisterNavigation());
        $this->assertCount(1, $page->cabinet['Others']['Affidavit']);
        $this->assertCount(0, $page->cabinet['Staff files']['Documents']);
        DB::table('cabinet_recycle_bin')->where('document_id', 1)->delete();
        $page->loadCabinet();
        $this->assertArrayNotHasKey('Recycle Bin', $page->cabinet);
        $this->assertTrue(\App\Filament\Pages\RecycleBin::shouldRegisterNavigation());
        $page->goToRoot();
        $page->openType('MOA');
        $page->openOffice('CS');
        $defaults = new \ReflectionMethod($page, 'currentDocumentDefaults');
        $this->assertSame(['MOA', 'CS'], $defaults->invoke($page));
        $page->goToRoot();
        $page->openType('Others');
        $page->openOffice('Affidavit');
        $this->assertSame('Others', $page->currentType);
        $this->assertSame('Affidavit', $page->currentOffice);
        $staff = \Mockery::mock(\App\Models\User::class)->makePartial();
        $staff->shouldReceive('canAccessPanel')->andReturn(true);
        auth()->setUser($staff);
        $page->clipboardDocumentId = 1;
        $page->pasteDocument('Renamed Document.pdf');
        $this->assertCount(3, $page->cabinet['Others']['Affidavit']);
        $this->assertSame('Renamed Document.pdf', $page->cabinet['Others']['Affidavit'][2]['name']);
        $page->pasteDocument('Another Document.pdf');
        $this->assertSame('Another Document.pdf', $page->cabinet['Others']['Affidavit'][3]['name']);
        $this->assertSame('Others', $page->currentType);
        $this->assertSame('Affidavit', $page->currentOffice);
        $this->assertSame(4, DB::table('documents')->count());
        $this->assertSame(['MOA', 'Proposal', 'Others'], DocumentType::query()->orderedForChoices()->pluck('type_name')->all());
    }
}
