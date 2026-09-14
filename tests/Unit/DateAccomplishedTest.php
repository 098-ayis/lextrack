<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class DateAccomplishedTest extends TestCase
{
    public function test_accomplishment_uses_latest_completion_and_ignores_later_edits(): void
    {
        $document = new Document;
        $document->setRelation('activityLogs', new Collection([
            (new ActivityLog)->forceFill(['action_type' => 'Document completed', 'created_at' => '2026-09-01 10:00:00']),
            (new ActivityLog)->forceFill(['action_type' => 'Document updated', 'old_value' => '{"status":"in_progress"}', 'new_value' => '{"status":"completed"}', 'created_at' => '2026-09-05 10:00:00']),
            (new ActivityLog)->forceFill(['action_type' => 'Document updated', 'old_value' => '{"status":"completed"}', 'new_value' => '{"status":"completed"}', 'created_at' => '2026-09-08 10:00:00']),
        ]));
        $this->assertSame('2026-09-05', $document->date_accomplished->toDateString());
        $document->setRelation('activityLogs', new Collection);
        $this->assertNull($document->date_accomplished);
    }
}
