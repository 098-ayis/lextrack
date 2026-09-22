<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Notifications\DocumentDeadlineReminder;
use Tests\TestCase;

class DocumentNotificationLabelTest extends TestCase
{
    public function test_filename_is_used_in_bell_and_email_reminders(): void
    {
        $document = new Document(['document_name' => 'OMNIBUS-MOA-Template.docx', 'particulars' => 'Review agreement', 'deadline' => '2026-09-20']);
        $notification = new DocumentDeadlineReminder($document, '3_days');
        $recipient = (object) ['name' => 'Admin'];
        $this->assertSame('OMNIBUS-MOA-Template.docx', $notification->toDatabase($recipient)['title']);
        $this->assertSame(
            'Document deadline reminder: OMNIBUS-MOA-Template.docx',
            $notification->toMail($recipient)->subject
        );
        $this->assertContains('Document: OMNIBUS-MOA-Template.docx', $notification->toMail($recipient)->introLines);
        $document->document_name = null;
        $this->assertSame('Review agreement', $document->notificationLabel());
    }
}
