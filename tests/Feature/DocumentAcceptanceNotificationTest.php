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
use Illuminate\Support\Str;
use Illuminate\Contracts\View\View;
use Livewire\Livewire;
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

        $client->shouldReceive('notify')->once()
            ->with(Mockery::on(function ($notification): bool {
                if (! $notification instanceof DatabaseNotification) {
                    return false;
                }
                $qr = collect($notification->toDatabase(new User)['actions'])
                    ->firstWhere('name', 'viewDocumentQrCode');
                return $qr !== null && str_contains($qr['url'], '/document-qr/1?signature=');
            }));

        $document = new Document;
        $document->document_id = 1;
        $document->public_id = (string) Str::ulid();
        $document->lao_number = 'LAO-26-001';
        $document->setRelation('user', $client);

        DB::shouldReceive('transaction')->once()->andReturn([
            'document' => $document,
            'accepted' => true,
        ]);

        Livewire::test(DocumentAcceptancePageHarness::class)
            ->call('acceptDocument', 1)
            ->assertRedirect(DocumentPage::getUrl(['section' => 'incoming']));
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

        $client->shouldReceive('notify')->once()
            ->with(Mockery::on(function ($notification): bool {
                if (! $notification instanceof DatabaseNotification) {
                    return false;
                }
                $qr = collect($notification->toDatabase(new User)['actions'])
                    ->firstWhere('name', 'viewDocumentQrCode');
                return $qr !== null && str_contains($qr['url'], '/document-qr/1?signature=');
            }));

        $document = new Document;
        $document->document_id = 1;
        $document->public_id = (string) Str::ulid();
        $document->setRelation('user', $client);

        DB::shouldReceive('transaction')->once()->andReturn([
            'document' => $document,
            'accepted' => true,
        ]);

        Livewire::test(DocumentAcceptancePageHarness::class)
            ->call('acceptDocument', 1)
            ->assertRedirect(DocumentPage::getUrl(['section' => 'incoming']));
        $notificationTitles = collect(session('filament.notifications', []))
            ->pluck('title')
            ->all();

        $this->assertContains('Document accepted', $notificationTitles);
        $this->assertNotContains('Document accepted, but email failed', $notificationTitles);
    }

    public function test_mail_failure_still_sends_bell_notification_and_stays_on_requests_page(): void
    {
        $client = Mockery::mock(User::class)->makePartial();
        $client->shouldReceive('notify')->once()
            ->with(Mockery::type(DocumentAcceptedNotification::class))
            ->andThrow(new TransportException('certificate verify failed'));
        $client->shouldReceive('notify')->once()
            ->with(Mockery::on(function ($notification): bool {
                if (! $notification instanceof DatabaseNotification) {
                    return false;
                }
                $actions = $notification->toDatabase(new User)['actions'];
                $qr = collect($actions)->firstWhere('name', 'viewDocumentQrCode');
                return $qr !== null
                    && str_contains($qr['url'], '/document-qr/1')
                    && str_contains($qr['url'], 'signature=');
            }));

        $document = new Document;
        $document->document_id = 1;
        $document->public_id = (string) Str::ulid();
        $document->lao_number = 'LAO-26-001';
        $request = new DocumentRequest;
        $request->setRelation('user', $client);

        // Simulate the already committed acceptance, then fail its SMTP delivery.
        DB::shouldReceive('transaction')->once()->andReturn([
            'request' => $request,
            'document' => $document,
        ]);

        Livewire::test(DocumentRequestsAcceptancePageHarness::class)
            ->call('acceptRequest', 1)
            ->assertNoRedirect();

        $notification = collect(session('filament.notifications'))->last();
        $this->assertSame('Document accepted, but email failed', $notification['title']);
        $this->assertSame('warning', $notification['status']);
        $this->assertStringContainsString('LAO-26-001', $notification['body']);
    }
}

trait RendersAcceptanceTestView
{
    public function render(): View
    {
        return view()->file(__DIR__ . '/../Fixtures/empty.blade.php');
    }
}

class DocumentAcceptancePageHarness extends DocumentPage
{
    use RendersAcceptanceTestView;
}

class DocumentRequestsAcceptancePageHarness extends DocumentRequests
{
    use RendersAcceptanceTestView;

    protected static ?string $slug = 'document-requests';
}
