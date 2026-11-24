<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\OfficeUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentCatalogForeignKeysTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogs_have_protected_custom_value_records(): void
    {
        $this->assertDatabaseHas('document_types', [
            'type_name' => 'Others',
            'allows_custom_value' => true,
        ]);
        $this->assertDatabaseHas('office_units', [
            'name' => 'Others',
            'allows_custom_value' => true,
        ]);
    }

    public function test_client_upload_page_builds_with_catalog_foreign_key_fields(): void
    {
        $user = User::factory()->create(['status' => User::DEFAULT_STATUS]);
        $user->assignRole(Role::query()->firstOrCreate([
            'name' => 'Client',
            'guard_name' => 'web',
        ]));

        $this->actingAs($user)
            ->get('/client/upload')
            ->assertOk()
            ->assertSee('Document Type')
            ->assertSee('Office From');
    }

    public function test_exact_snapshot_values_are_synchronized_to_catalog_foreign_keys(): void
    {
        $type = DocumentType::query()->create([
            'type_name' => 'Contract',
            'type_desc' => 'Contract review',
        ]);
        $office = OfficeUnit::query()->create(['name' => 'Legal Office']);
        $user = User::factory()->create();

        $document = Document::query()->create([
            'user_id' => $user->id,
            'document_type' => ' contract ',
            'office_unit' => ' legal office ',
            'status' => 'pending',
        ]);

        $this->assertSame($type->type_id, $document->document_type_id);
        $this->assertSame('Contract', $document->document_type);
        $this->assertSame($office->office_unit_id, $document->office_unit_id);
        $this->assertSame('Legal Office', $document->office_unit);
    }

    public function test_custom_values_reference_others_and_keep_the_typed_snapshot(): void
    {
        $otherType = DocumentType::query()->where('allows_custom_value', true)->firstOrFail();
        $otherOffice = OfficeUnit::query()->where('allows_custom_value', true)->firstOrFail();
        $user = User::factory()->create();

        $document = Document::query()->create([
            'user_id' => $user->id,
            'document_type' => 'Special Legal Certification',
            'office_unit' => 'Research Extension Office',
            'status' => 'pending',
        ]);

        $this->assertSame($otherType->type_id, $document->document_type_id);
        $this->assertSame('Special Legal Certification', $document->document_type_label);
        $this->assertSame($otherOffice->office_unit_id, $document->office_unit_id);
        $this->assertSame('Research Extension Office', $document->office_unit_label);
    }

    public function test_legacy_values_with_null_foreign_keys_still_have_display_labels(): void
    {
        $user = User::factory()->create();
        $documentId = DB::table('documents')->insertGetId([
            'public_id' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => $user->id,
            'document_type' => 'Legacy Type',
            'document_type_id' => null,
            'office_unit' => 'Legacy Office',
            'office_unit_id' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'document_id');

        $document = Document::query()->findOrFail($documentId);

        $this->assertSame('Legacy Type', $document->document_type_label);
        $this->assertSame('Legacy Office', $document->office_unit_label);

        $document->update(['particulars' => 'Reviewed legacy record']);
        $document->refresh();

        $this->assertTrue($document->documentType->allows_custom_value);
        $this->assertSame('Legacy Type', $document->document_type);
        $this->assertTrue($document->officeUnit->allows_custom_value);
        $this->assertSame('Legacy Office', $document->office_unit);
    }
}
