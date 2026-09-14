<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class CalendarViewSyntaxTest extends TestCase
{
    public function test_calendar_blade_compiles_to_valid_php(): void
    {
        foreach (['filament/pages/calendar', 'filament/pages/partials/calendar-event-list'] as $view) {
            $compiled = app('blade.compiler')->compileString(file_get_contents(resource_path('views/'.$view.'.blade.php')));
            $path = tempnam(sys_get_temp_dir(), 'calendar-syntax-');
            try {
                file_put_contents($path, $compiled);
                $process = new Process([PHP_BINARY, '-l', $path]);
                $process->run();
                $this->assertTrue($process->isSuccessful(), $view.': '.$process->getOutput().$process->getErrorOutput());
            } finally {
                unlink($path);
            }
        }
    }
}
