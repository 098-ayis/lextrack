<?php

namespace Tests\Feature;

use App\Filament\Pages\DocumentRequests;
use App\Filament\Pages\Document as DocumentPage;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Notifications\DocumentAcceptedNotification;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Mockery;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class DocumentAcceptanceNotificationTest extends TestCase
{
    public function test_incoming_acceptance_redirects_with_warning_when_email_fails(): void
    {
        $client = Mockery::mock(User::class)->makePartial();
        $client->shouldReceive('notify')->once()
            ->with(Mockery::type(DocumentAcceptedNotification::class))
            ->andThrow(new TransportException('certificate verify failed'));

        $document = new Document;
        $document->document_id = 1;
        $document->lao_number = 'LAO-26-001';
        $document->setRelation('user', $client);

        DB::shouldReceive('transaction')->once()->andReturn([
            'document' => $document,
            'accepted' => true,
        ]);

        $page = new DocumentPage;
        $page->acceptDocument(1);

        $this->assertStringContainsString('section=incoming', \Livewire\store($page)->get('redirect'));
        $notification = collect(session('filament.notifications'))->last();
        $this->assertSame('Document accepted, but email failed', $notification['title']);
        $this->assertSame('warning', $notification['status']);
        $this->assertStringContainsString('LAO-26-001', $notification['body']);
    }

    public function test_incoming_acceptance_redirects_without_warning_when_email_succeeds(): void
    {
        $client = Mockery::mock(User::class)->makePartial();
        $client->shouldReceive('notify')->once()
            ->with(Mockery::type(DocumentAcceptedNotification::class));

        $document = new Document;
        $document->setRelation('user', $client);

        DB::shouldReceive('transaction')->once()->andReturn([
            'document' => $document,
            'accepted' => true,
        ]);

        $page = new DocumentPage;
        $page->acceptDocument(1);

        $this->assertStringContainsString('section=incoming', \Livewire\store($page)->get('redirect'));
        $this->assertEmpty(session('filament.notifications', []));
    }

    public function test_mail_failure_still_sends_bell_notification_and_redirects_with_warning(): void
    {
        $client = Mockery::mock(User::class)->makePartial();
        $client->shouldReceive('notify')->once()
            ->with(Mockery::type(DocumentAcceptedNotification::class))
            ->andThrow(new TransportException('certificate verify failed'));
        $client->shouldReceive('notify')->once()
            ->with(Mockery::type(DatabaseNotification::class));

        $document = new Document;
        $document->document_id = 1;
        $document->lao_number = 'LAO-26-001';
        $request = new DocumentRequest;
        $request->setRelation('user', $client);

        // Simulate the already committed acceptance, then fail its SMTP delivery.
        DB::shouldReceive('transaction')->once()->andReturn([
            'request' => $request,
            'document' => $document,
        ]);

        $page = new DocumentRequests;

        $page->acceptRequest(1);

        $this->assertStringContainsString('section=accepted', \Livewire\store($page)->get('redirect'));

        $notification = collect(session('filament.notifications'))->last();
        $this->assertSame('Document accepted, but email failed', $notification['title']);
        $this->assertSame('warning', $notification['status']);
        $this->assertStringContainsString('LAO-26-001', $notification['body']);
    }
}
