<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class MonthlyReportPdfService
{
    public function build(array $report, string $preparedBy): string
    {
        $directory = sys_get_temp_dir().'/monthly-pdf-'.bin2hex(random_bytes(16));
        File::makeDirectory($directory, 0700, true);

        try {
            $this->render($report, $preparedBy, $directory, 'pdf');
            $path = $directory.'/report.pdf';
            $pdf = file_get_contents($path);
            if ($pdf === false || ! str_starts_with($pdf, '%PDF-')) {
                throw new RuntimeException('Unable to read the generated report PDF.');
            }

            return $pdf;
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function render(array $report, string $preparedBy, string $directory, string $mode): void
    {
        $html = view('reports.monthly', $report + [
            'reportMonth' => now()->parse($report['month'])->format('Y-m'),
            'preparedBy' => $preparedBy,
        ])->render();
        // Embed the same artwork so browser exports never depend on HTTP asset requests.
        foreach (['bu-certified', 'sdg', 'bagong-pilipinas', 'qr'] as $name) {
            $html = str_replace(asset('images/reports/'.$name.'.png'),
                'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/reports/'.$name.'.png'))), $html);
        }
        foreach (['Regular', 'Bold', 'Italic', 'BoldItalic'] as $variant) {
            $font = 'fonts/reports/LiberationSerif-'.$variant.'.ttf';
            $html = str_replace(asset($font), 'data:font/ttf;base64,'.base64_encode(file_get_contents(public_path($font))), $html);
        }
        file_put_contents($directory.'/report.html', $html);
        $process = new Process(['node', base_path('scripts/render-monthly-report.mjs'), $directory.'/report.html', $directory, $mode]);
        $process->setEnv(['PLAYWRIGHT_BROWSERS_PATH' => '/opt/playwright']);
        $process->setTimeout(120);
        $process->mustRun();
    }
}
