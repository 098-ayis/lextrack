<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MonthlyReportService;
use Mockery;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Tests\TestCase;

class MonthlyReportPdfTest extends TestCase
{
    public function test_pdf_option_downloads_a_real_pdf_attachment(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['user_id' => 1, 'name' => 'Report Test']);
        $user->shouldReceive('isAdmin')->andReturn(true);
        $this->actingAs($user);
        $this->mock(MonthlyReportService::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->with('2026-09')->andReturn([
                'month' => 'September 2026', 'received' => 1, 'processed' => 1,
                'completed' => 1, 'requests' => 0, 'activities' => collect(range(1, 6))->map(fn ($id) => (object) [
                    'created_at' => now(), 'document' => null,
                    'action_type' => 'Document completed', 'action_details' => 'Completed review '.$id,
                ]),
            ]);
        });
        $response = $this->get('/admin/reports/monthly?month=2026-09&format=pdf');
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="monthly-report-2026-09.pdf"');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $pdf = new Fpdi;
        $stream = StreamReader::createByString($response->getContent());
        $this->assertSame(2, $pdf->setSourceFile($stream));

    }
}
