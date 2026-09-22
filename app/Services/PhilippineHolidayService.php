<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class PhilippineHolidayService
{
    public function events(int $year, int $month, ?string $date = null): Collection
    {
        $holidays = config("holidays.official.{$year}");
        if ($holidays === null) {
            $holidays = $this->feedHolidays();
        }

        $simeonOlaDay = sprintf('%04d-09-02', $year);
        $existingHoliday = $holidays[$simeonOlaDay] ?? null;
        if ($existingHoliday === null) {
            $holidays[$simeonOlaDay] = 'Simeon Ola Day';
        } elseif (! str_contains($existingHoliday, 'Simeon Ola Day')) {
            $holidays[$simeonOlaDay] = $existingHoliday.' / Simeon Ola Day';
        }

        $prefix = sprintf('%04d-%02d-', $year, $month);

        return collect($holidays)->filter(fn ($name, $day) => $date ? $day === $date : str_starts_with($day, $prefix))
            ->map(fn ($name, $day) => (object) [
                'sched_id' => 'ph-holiday-'.$day.'-'.substr(sha1($name), 0, 8),
                'event' => $name, 'date' => $day, 'time' => null,
                'details' => str_contains($name, 'Simeon Ola Day')
                    ? 'Special non-working holiday in Albay (Republic Act No. 11136)'
                    : 'Philippine holiday',
                'category' => 'holiday',
                'is_automatic_holiday' => true, 'is_document_deadline' => false,
                'is_completed' => Carbon::parse($day)->endOfDay()->lt(now()),
                'user_id' => null, 'user' => null,
            ])->values();
    }

    protected function feedHolidays(): array
    {
        $key = 'ph-holidays:'.sha1((string) config('holidays.feed_url'));

        return Cache::remember($key.':daily', now()->addDay(), function () use ($key) {
            try {
                $response = Http::connectTimeout(2)->timeout(5)->get(config('holidays.feed_url'))->throw();
                $holidays = $this->parseFeed($response->body());
                if ($holidays !== []) {
                    Cache::forever($key.':last-good', $holidays);
                    return $holidays;
                }
            } catch (Throwable $exception) {
                report($exception);
            }

            return Cache::get($key.':last-good', []);
        });
    }

    public function parseFeed(string $ics): array
    {
        // Unfold iCalendar lines before reading all-day event dates and titles.
        $ics = preg_replace('/\r?\n[ \t]/', '', $ics);
        preg_match_all('/BEGIN:VEVENT\r?\n(.*?)END:VEVENT/s', $ics, $events);
        $holidays = [];
        foreach ($events[1] as $event) {
            if (! preg_match('/^DTSTART(?:;VALUE=DATE)?:([0-9]{8})\r?$/m', $event, $day)
                || ! preg_match('/^SUMMARY:(.+)\r?$/m', $event, $summary)) {
                continue;
            }
            try {
                $date = Carbon::createFromFormat('!Ymd', $day[1]);
                if ($date->format('Ymd') !== $day[1]) {
                    continue;
                }
                $title = trim(str_replace(['\\n', '\\N', '\\,', '\\;', '\\\\'], ["\n", "\n", ',', ';', '\\'], $summary[1]));
                $key = $date->toDateString();
                $holidays[$key] = isset($holidays[$key]) && $holidays[$key] !== $title
                    ? $holidays[$key].' / '.$title : $title;
            } catch (Throwable) {
                continue;
            }
        }
        return $holidays;
    }
}
