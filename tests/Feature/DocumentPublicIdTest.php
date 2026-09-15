<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DocumentPublicIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('documents', function (Blueprint $table): void {
            $table->id('document_id');
            $table->string('public_id', 26)->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('document_type')->nullable();
            $table->string('transmittal')->nullable();
            $table->string('lao_number')->nullable();
            $table->string('status')->default('in_progress');
            $table->string('document_name')->nullable();
            $table->timestamps();
        });

        Schema::create('document_requests', function (Blueprint $table): void {
            $table->id('request_id');
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('document_versions', function (Blueprint $table): void {
            $table->id('version_id');
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('file_path');
            $table->string('version_number')->nullable();
            $table->timestamps();
        });

        Storage::fake('local');
    }

    public function test_document_gets_a_stable_ulid_public_id(): void
    {
        $document = $this->createDocument(1);
        $publicId = $document->public_id;

        $this->assertMatchesRegularExpression(
            '/^[0-9A-HJKMNP-TV-Z]{26}$/',
            $publicId
        );

        $document->update(['status' => 'completed']);

        $this->assertSame($publicId, $document->fresh()->public_id);
    }

    public function test_correct_client_can_open_their_ulid_document_url(): void
    {
        $document = $this->createDocument(1);
        $this->attachVersion($document);
        $this->actingAs($this->user(1));

        $this->get(route('client.document.download', [
            'document' => $document->public_id,
        ]))->assertOk();
    }

    public function test_document_version_hash_detects_same_document_or_same_uploader_duplicates(): void
    {
        $firstDocument = $this->createDocument(1);
        $secondDocument = $this->createDocument(2);
        $this->attachVersion($firstDocument);

        $fileHash = DocumentVersion::query()->value('file_hash');

        $this->assertTrue(DocumentVersion::existsForDocumentOrUserHash(
            $firstDocument->document_id,
            $fileHash,
            99,
        ));
        $this->assertTrue(DocumentVersion::existsForDocumentOrUserHash(
            $secondDocument->document_id,
            $fileHash,
            1,
        ));
        $this->assertFalse(DocumentVersion::existsForDocumentOrUserHash(
            $secondDocument->document_id,
            $fileHash,
            2,
        ));
    }

    public function test_another_client_cannot_open_the_same_ulid_url(): void
    {
        $document = $this->createDocument(1);
        $this->attachVersion($document);
        $this->actingAs($this->user(2));

        $this->get(route('client.document.preview', [
            'document' => $document->public_id,
        ]))->assertNotFound();

        $this->get(route('client.document.download', [
            'document' => $document->public_id,
        ]))->assertNotFound();
    }

    public function test_authorized_admin_can_access_the_ulid_document_url(): void
    {
        $document = $this->createDocument(1);
        $this->attachVersion($document);
        $this->actingAs($this->user(99, true));

        $this->get(route('admin.documents.preview', [
            'document' => $document->public_id,
        ]))->assertOk();
    }

    public function test_unauthorized_user_is_denied_admin_document_access(): void
    {
        $document = $this->createDocument(1);
        $this->actingAs($this->user(2));

        $this->get(route('admin.documents.download', [
            'document' => $document->public_id,
        ]))->assertForbidden();
    }

    public function test_generated_document_urls_use_public_id_instead_of_internal_id(): void
    {
        $document = $this->createDocument(1);

        $urls = [
            \App\Filament\Client\Pages\ViewDocument::getUrl([
                'document' => $document->public_id,
            ], false, 'client'),
            \App\Filament\Pages\ViewDocument::getUrl([
                'document' => $document->public_id,
            ], false, 'admin'),
            route('client.document.preview', ['document' => $document->public_id]),
            route('client.document.download', ['document' => $document->public_id]),
            route('admin.documents.preview', ['document' => $document->public_id]),
            route('admin.documents.download', ['document' => $document->public_id]),
        ];

        foreach ($urls as $url) {
            $this->assertStringContainsString($document->public_id, $url);
            $this->assertDoesNotMatchRegularExpression(
                '#/(?:admin|client)/documents/' . $document->document_id . '(?:/|$)#',
                parse_url($url, PHP_URL_PATH)
            );
        }
    }

    private function createDocument(int $userId): Document
    {
        return Document::create([
            'user_id' => $userId,
            'document_type' => 'Test document',
            'document_name' => 'ULID test document',
            'status' => 'in_progress',
        ]);
    }

    private function attachVersion(Document $document): void
    {
        Storage::disk('local')->put('documents/test.pdf', "%PDF-1.4\n");

        DocumentVersion::create([
            'document_id' => $document->document_id,
            'user_id' => $document->user_id,
            'file_path' => 'documents/test.pdf',
            'version_number' => '1.0',
            'file_hash' => DocumentVersion::hashForUpload('documents/test.pdf'),
        ]);
    }

    private function user(int $id, bool $admin = false): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => $id]);
        $user->shouldReceive('isAdmin')->andReturn($admin);

        return $user;
    }
}
