<?php

namespace Tests\Feature;

use App\Models\ActionType;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentActionTypeForeignKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_catalog_has_a_protected_custom_value_record(): void
    {
        $this->assertDatabaseHas('action_types', [
            'action_name' => 'Others',
            'allows_custom_value' => true,
        ]);
    }

    public function test_exact_action_text_is_synchronized_to_the_catalog_foreign_key(): void
    {
        $action = ActionType::query()->create([
            'action_name' => 'For Approval',
            'color' => '#059669',
        ]);
        $document = $this->createDocument(['action_type' => ' for approval ']);

        $this->assertSame($action->action_id, $document->action_type_id);
        $this->assertSame('For Approval', $document->action_type);
        $this->assertSame('For Approval', $document->action_type_label);
    }

    public function test_custom_action_text_references_others_and_keeps_the_typed_value(): void
    {
        $other = ActionType::query()->where('allows_custom_value', true)->firstOrFail();
        $document = $this->createDocument(['action_type' => 'Coordinate with Regional Counsel']);

        $this->assertSame($other->action_id, $document->action_type_id);
        $this->assertSame('Coordinate with Regional Counsel', $document->action_type);
        $this->assertSame('Coordinate with Regional Counsel', $document->action_type_label);
    }

    public function test_changing_action_text_updates_the_foreign_key_and_clearing_it_removes_the_key(): void
    {
        $first = ActionType::query()->create(['action_name' => 'First Action', 'color' => '#059669']);
        $second = ActionType::query()->create(['action_name' => 'Second Action', 'color' => '#2563EB']);
        $document = $this->createDocument(['action_type' => $first->action_name]);

        $document->update(['action_type' => $second->action_name]);
        $this->assertSame($second->action_id, $document->fresh()->action_type_id);

        $document->update(['action_type' => null]);
        $document->refresh();
        $this->assertNull($document->action_type_id);
        $this->assertNull($document->action_type_label);
    }

    public function test_legacy_null_foreign_key_remains_readable_and_is_synchronized_on_save(): void
    {
        $user = User::factory()->create();
        $documentId = DB::table('documents')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'action_type' => 'Legacy Custom Action',
            'action_type_id' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'document_id');

        $document = Document::query()->findOrFail($documentId);
        $this->assertSame('Legacy Custom Action', $document->action_type_label);

        $document->update(['particulars' => 'Reviewed legacy record']);
        $document->refresh();

        $this->assertTrue($document->actionType->allows_custom_value);
        $this->assertSame('Legacy Custom Action', $document->action_type);
    }

    public function test_action_filter_matches_foreign_key_and_legacy_snapshot_rows(): void
    {
        $action = ActionType::query()->create(['action_name' => 'For Routing', 'color' => '#059669']);
        $linked = $this->createDocument(['action_type' => $action->action_name]);
        $legacy = $this->insertLegacyDocument(' for routing ');
        $this->createDocument(['action_type' => 'Different Action']);

        $ids = Document::query()
            ->forActionType($action->action_name)
            ->pluck('document_id')
            ->all();

        $this->assertEqualsCanonicalizing([$linked->document_id, $legacy], $ids);
    }

    private function createDocument(array $attributes): Document
    {
        return Document::query()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'status' => 'pending',
        ], $attributes));
    }

    private function insertLegacyDocument(string $actionType): int
    {
        return DB::table('documents')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'user_id' => User::factory()->create()->id,
            'action_type' => $actionType,
            'action_type_id' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'document_id');
    }
}
