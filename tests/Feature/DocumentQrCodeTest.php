<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
}
