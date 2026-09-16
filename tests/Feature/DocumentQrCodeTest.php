<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;
use App\Models\Document;
use App\Services\DocumentQrToken;
use Tests\TestCase;

class DocumentQrCodeTest extends TestCase
{
    public function test_unsigned_qr_links_are_rejected(): void
    {
        $this->get('/document-qr/1')->assertForbidden();
    }

    public function test_signed_link_returns_qr_image_for_existing_document_only(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('documents', fn (Blueprint $table) => $table->id('document_id'));
        DB::table('documents')->insert(['document_id' => 1]);
        $response = $this->get(URL::signedRoute('documents.qr', ['document' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $response->getContent());
        $this->assertNotFalse(getimagesizefromstring($response->getContent()));
        $this->get(URL::signedRoute('documents.qr', ['document' => 999]))->assertNotFound();
    }

    public function test_qr_image_upload_without_turnstile_token_is_rejected(): void
    {
        Turnstile::fake();

        $this->postJson(route('public.track.qr'), [
            'qr_source' => 'image',
            'qr_token' => 'LEXTRACK-QR-1.invalid-token',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('cf-turnstile-response');
    }

    public function test_qr_image_upload_with_invalid_turnstile_token_is_rejected(): void
    {
        Turnstile::fake()->fail();

        $this->postJson(route('public.track.qr'), [
            'qr_source' => 'image',
            'qr_token' => 'LEXTRACK-QR-1.invalid-token',
            'cf-turnstile-response' => 'invalid-turnstile-token',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('cf-turnstile-response');
    }

    public function test_valid_turnstile_token_and_valid_qr_image_payload_are_accepted(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->string('lao_number')->nullable();
            $table->string('document_type')->nullable();
            $table->text('particulars')->nullable();
            $table->string('status')->nullable();
            $table->string('sent_to')->nullable();
            $table->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('document_id');
            $table->string('action_type');
            $table->text('action_details');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();
        });

        $document = Document::query()->create([
            'lao_number' => 'LAO-26-001',
            'document_type' => 'Legal Document',
            'particulars' => 'Test document',
            'status' => 'outgoing',
            'sent_to' => 'Office of the President',
        ]);

        DB::table('activity_logs')->insert([
            'document_id' => $document->document_id,
            'action_type' => 'Document moved to outgoing',
            'action_details' => 'Sent to Office of the President on 2026-09-16.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Turnstile::fake();

        $this->postJson(route('public.track.qr'), [
            'qr_source' => 'image',
            'qr_token' => DocumentQrToken::encode($document),
            'cf-turnstile-response' => Turnstile::dummy(),
        ])->assertOk()
            ->assertJsonPath('document.tracking_number', 'LAO-26-001')
            ->assertJsonPath('document.timeline.0.title', 'Pending')
            ->assertJsonPath('document.timeline.1.title', 'Sent to Office of the President')
            ->assertJsonPath('document.timeline.1.description', 'Your document was sent to Office of the President.');
    }

    public function test_valid_turnstile_token_and_invalid_qr_image_payload_use_existing_validation(): void
    {
        Turnstile::fake();

        $this->postJson(route('public.track.qr'), [
            'qr_source' => 'image',
            'qr_token' => 'not-a-lextrack-qr-token',
            'cf-turnstile-response' => Turnstile::dummy(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('qr_token');
    }
}
