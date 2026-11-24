<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentTransmittal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTransmittalTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_no_longer_store_a_transmittal_column(): void
    {
        $this->assertFalse(Schema::hasColumn('documents', 'transmittal'));
        $this->assertNotContains('transmittal', (new Document)->getFillable());
        $this->assertArrayNotHasKey(
            'transmittal',
            (new Document)->fill(['transmittal' => 'legacy.docx'])->getAttributes(),
        );
    }

    public function test_client_can_preview_and_download_a_related_transmittal(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $document = Document::create([
            'user_id' => $user->id,
            'description' => 'Relationship-only transmittal test',
            'document_name' => 'submission.docx',
            'status' => 'pending',
        ]);

        $filePath = 'documents/transmittals/endorsement.docx';
        Storage::disk('local')->put($filePath, file_get_contents(base_path('LETTERHEAD-LAO.docx')));

        $attachment = DocumentTransmittal::create([
            'document_id' => $document->document_id,
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_disk' => 'local',
            'original_filename' => 'endorsement.docx',
            'file_hash' => hash_file('sha256', Storage::disk('local')->path($filePath)),
        ]);

        $this->actingAs($user)
            ->get(route('client.document.transmittal-attachment.preview', [
                'document' => $document->getPublicRouteKey(),
                'attachment' => $attachment->transmittal_id,
            ]))
            ->assertOk()
            ->assertSee('Word document preview');

        $this->actingAs($user)
            ->get(route('client.document.transmittal-attachment.download', [
                'document' => $document->getPublicRouteKey(),
                'attachment' => $attachment->transmittal_id,
            ]))
            ->assertOk()
            ->assertDownload('endorsement.docx');

        // Existing bookmarks to the old singular route still resolve through
        // the relationship, without reading documents.transmittal.
        $this->actingAs($user)
            ->get(route('client.document.transmittal.download', [
                'document' => $document->getPublicRouteKey(),
            ]))
            ->assertOk()
            ->assertDownload('endorsement.docx');
    }
}
