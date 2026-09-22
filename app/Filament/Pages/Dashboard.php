<?php

namespace App\Filament\Pages;

use App\Models\Calendar;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Dashboard extends Page
{
    // use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?string $slug = 'dashboard';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.dashboard';

    public string $search = '';

    public string $statusFilter = '';

    public string $documentTypeFilter = '';

    public string $processingTrendPeriod = 'monthly';

    public int $month;

    public int $year;

    /*
    |--------------------------------------------------------------------------
    | MOUNT
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $now = now();

        $this->month = $now->month;
        $this->year = $now->year;
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD STATISTICS
    |--------------------------------------------------------------------------
    */

    public function getProcessingTrend(): array
    {
        $period = in_array($this->processingTrendPeriod, ['weekly', 'monthly', 'yearly'], true)
            ? $this->processingTrendPeriod
            : 'monthly';
        $today = Carbon::today();

        [$start, $end, $bucketExpression, $bucketDates] = match ($period) {
            'weekly' => (function () use ($today) {
                $start = $today->copy()->subDays(6)->startOfDay();
                $dates = collect(range(0, 6))->map(fn (int $offset) => $start->copy()->addDays($offset));

                return [
                    $start,
                    $today->copy()->addDay()->startOfDay(),
                    'DATE(created_at)',
                    $dates->map(fn (Carbon $date) => [
                        'key' => $date->format('Y-m-d'),
                        'label' => $date->format('D'),
                        'tooltip' => $date->format('M d, Y'),
                    ])->all(),
                ];
            })(),
            'yearly' => (function () use ($today) {
                $start = $today->copy()->startOfYear()->subYears(4);
                $years = collect(range($start->year, $today->year));

                return [
                    $start,
                    $today->copy()->addDay()->startOfDay(),
                    DB::connection()->getDriverName() === 'sqlite'
                        ? "strftime('%Y', created_at)"
                        : 'YEAR(created_at)',
                    $years->map(fn (int $year) => [
                        'key' => (string) $year,
                        'label' => (string) $year,
                        'tooltip' => (string) $year,
                    ])->all(),
                ];
            })(),
            default => (function () use ($today) {
                $start = $today->copy()->startOfYear();
                $months = collect(range(1, 12));
                $expression = DB::connection()->getDriverName() === 'sqlite'
                    ? "strftime('%Y-%m', created_at)"
                    : "DATE_FORMAT(created_at, '%Y-%m')";

                return [
                    $start,
                    $today->copy()->addDay()->startOfDay(),
                    $expression,
                    $months->map(fn (int $month) => $start->copy()->month($month))->map(fn (Carbon $date) => [
                        'key' => $date->format('Y-m'),
                        'label' => $date->format('M'),
                        'tooltip' => $date->format('F Y'),
                    ])->all(),
                ];
            })(),
        };

        $documentQuery = Document::query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);

        $grouped = (clone $documentQuery)
            ->selectRaw("{$bucketExpression} as period_bucket, COUNT(*) as total")
            ->groupByRaw($bucketExpression)
            ->pluck('total', 'period_bucket');

        $buckets = collect($bucketDates)->map(fn (array $bucket) => [
            ...$bucket,
            'count' => (int) ($grouped[$bucket['key']] ?? 0),
        ])->all();

        $summaryStart = match ($period) {
            'weekly' => $today->copy()->subDays(6)->startOfDay(),
            'yearly' => $today->copy()->startOfYear(),
            default => $today->copy()->startOfMonth(),
        };
        $summaryQuery = Document::query()
            ->where('created_at', '>=', $summaryStart)
            ->where('created_at', '<', $today->copy()->addDay()->startOfDay());

        return [
            'period' => $period,
            'periodLabel' => match ($period) {
                'weekly' => 'Last 7 days',
                'yearly' => 'This year',
                default => 'This month',
            },
            'chartLabel' => match ($period) {
                'weekly' => 'Daily document activity for the last 7 days',
                'yearly' => 'Yearly document activity for the last 5 years',
                default => 'Monthly document activity for this year',
            },
            'buckets' => $buckets,
            'total' => (int) $summaryQuery->count('document_id'),
            'maximum' => max(1, max(array_column($buckets, 'count'))),
        ];
    }

    public function setProcessingTrendPeriod(string $period): void
    {
        if (in_array($period, ['weekly', 'monthly', 'yearly'], true)) {
            $this->processingTrendPeriod = $period;
        }
    }

    public function clearStatusFilter(): void
    {
        $this->statusFilter = '';
    }

    public function clearDocumentTypeFilter(): void
    {
        $this->documentTypeFilter = '';
    }

    public function getStats(): array
    {
        return [
            // All documents
            'total' => Document::count(),

            // Pending only
            'pending' => Document::where('status', 'pending')
                ->count(),

            // In progress only
            'active' => Document::where('status', 'in_progress')
                ->count(),

            // Completed ONLY
            'completed' => Document::where('status', 'completed')
                ->count(),

            'trends' => $this->getStatTrends(),
        ];
    }

    protected function getStatTrends(): array
    {
        $today = Carbon::today();
        $currentStart = $today->copy()->startOfMonth();
        $currentEnd = $today->copy()->addDay();
        $previousStart = $currentStart->copy()->subMonth();
        $previousEnd = $currentStart->copy();

        $documentCounts = function (?string $status) use ($previousStart, $previousEnd, $currentStart, $currentEnd): array {
            $query = Document::query()
                ->where('created_at', '>=', $previousStart)
                ->where('created_at', '<', $currentEnd);

            if ($status !== null) {
                $query->where('status', $status);
            }

            $counts = $query->selectRaw(
                'SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as recent, '
                .'SUM(CASE WHEN created_at < ? THEN 1 ELSE 0 END) as previous',
                [$currentStart, $previousEnd],
            )->first();

            return [(int) ($counts->recent ?? 0), (int) ($counts->previous ?? 0)];
        };

        $activitySets = [
            'active' => ['recent' => [], 'previous' => []],
            'completed' => ['recent' => [], 'previous' => []],
        ];

        $activities = \App\Models\ActivityLog::query()
            ->where('created_at', '>=', $previousStart)
            ->where('created_at', '<', $currentEnd)
            ->whereIn('action_type', [
                'Document accepted',
                'Document returned',
                'Document completed',
                'Document returned from archive',
                'Document updated',
            ])
            ->get(['document_id', 'action_type', 'action_details', 'new_value', 'created_at']);

        foreach ($activities as $activity) {
            $period = $activity->created_at->greaterThanOrEqualTo($currentStart) ? 'recent' : 'previous';

            if ($period === null) {
                continue;
            }

            $newValues = json_decode((string) $activity->new_value, true);
            $newStatus = is_array($newValues) ? ($newValues['status'] ?? null) : null;
            $details = strtolower((string) $activity->action_details);

            if (
                $activity->action_type === 'Document accepted'
                || ($activity->action_type === 'Document returned' && str_contains($details, 'incoming'))
                || ($activity->action_type === 'Document updated' && $newStatus === 'in_progress')
            ) {
                $activitySets['active'][$period][$activity->document_id] = true;
            }

            if (
                $activity->action_type === 'Document completed'
                || $activity->action_type === 'Document returned from archive'
                || ($activity->action_type === 'Document updated' && $newStatus === 'completed')
            ) {
                $activitySets['completed'][$period][$activity->document_id] = true;
            }
        }

        return [
            'total' => $this->formatStatTrend(...$documentCounts(null)),
            'pending' => $this->formatStatTrend(...$documentCounts('pending')),
            'active' => $this->formatStatTrend(
                count($activitySets['active']['recent']),
                count($activitySets['active']['previous']),
            ),
            'completed' => $this->formatStatTrend(
                count($activitySets['completed']['recent']),
                count($activitySets['completed']['previous']),
            ),
        ];
    }

    protected function formatStatTrend(int $recent, int $previous): array
    {
        $change = $recent - $previous;
        $direction = $change <=> 0;
        $value = ($change > 0 ? '+' : '').number_format($change);

        return [
            'direction' => $direction,
            'value' => $value,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RECENT DOCUMENTS
    |--------------------------------------------------------------------------
    */

    public function getRecentDocuments()
    {
        return Document::query()
            ->with(['latestVersion'])

            /*
            |--------------------------------------------------------------------------
            | SEARCH
            |--------------------------------------------------------------------------
            */

            ->when(
                trim($this->search) !== '',
                function ($query) {
                    $search = '%' . trim($this->search) . '%';

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('lao_number', 'like', $search)
                            ->orWhere('particulars', 'like', $search)
                            ->orWhere('office_unit', 'like', $search)
                            ->orWhere('sent_to', 'like', $search)
                            ->orWhere('returned_from', 'like', $search);
                    });
                }
            )

            ->when(
                $this->documentTypeFilter !== '',
                fn ($query) => $query->where('document_type', $this->documentTypeFilter)
            )

            /*
            |--------------------------------------------------------------------------
            | STATUS FILTER
            |--------------------------------------------------------------------------
            */

            ->when(
                $this->statusFilter !== '',
                function ($query) {
                    $query->where(
                        'status',
                        $this->statusFilter
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | MOST RECENTLY UPDATED
            |--------------------------------------------------------------------------
            */

            ->latest('updated_at')

            /*
            |--------------------------------------------------------------------------
            | SHOW ONLY 6
            |--------------------------------------------------------------------------
            */

            ->limit(6)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIOUS MONTH
    |--------------------------------------------------------------------------
    */

    public function previousMonth(): void
    {
        $date = Carbon::create(
            $this->year,
            $this->month,
            1
        )->subMonth();

        $this->month = $date->month;
        $this->year = $date->year;
    }

    /*
    |--------------------------------------------------------------------------
    | NEXT MONTH
    |--------------------------------------------------------------------------
    */

    public function nextMonth(): void
    {
        $date = Carbon::create(
            $this->year,
            $this->month,
            1
        )->addMonth();

        $this->month = $date->month;
        $this->year = $date->year;
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT MONTH LABEL
    |--------------------------------------------------------------------------
    */

    public function getCurrentMonthLabel(): string
    {
        return Carbon::create(
            $this->year,
            $this->month,
            1
        )->format('F Y');
    }

    /*
    |--------------------------------------------------------------------------
    | CALENDAR EVENTS FOR DISPLAYED MONTH
    |--------------------------------------------------------------------------
    |
    | Shared calendar:
    | Walang user_id restriction para makita ng authorized admin/staff users
    | ang parehong office calendar events.
    |
    */

    public function getCalendarEvents()
    {
        $calendar = new \App\Filament\Pages\Calendar;
        $calendar->year = $this->year;
        $calendar->month = $this->month;

        return $calendar->getMonthEvents();
    }

    public function getCalendarEventLegend(): array
    {
        $calendar = new \App\Filament\Pages\Calendar;
        $calendar->year = $this->year;
        $calendar->month = $this->month;

        return collect($calendar->getEventCategories())
            ->map(fn (string $label, string $category): array => [
                'label' => $label,
                'color' => $calendar->getEventColor((object) ['category' => $category]),
            ])
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | CALENDAR CELLS
    |--------------------------------------------------------------------------
    */

    public function getCalendarCells(): array
    {
        /*
        |--------------------------------------------------------------------------
        | START OF CURRENT MONTH
        |--------------------------------------------------------------------------
        */

        $monthStart = Carbon::create(
            $this->year,
            $this->month,
            1
        )->startOfMonth();

        /*
        |--------------------------------------------------------------------------
        | END OF CURRENT MONTH
        |--------------------------------------------------------------------------
        */

        $monthEnd = $monthStart
            ->copy()
            ->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | START CALENDAR GRID ON SUNDAY
        |--------------------------------------------------------------------------
        */

        $gridStart = $monthStart
            ->copy()
            ->startOfWeek(Carbon::SUNDAY);

        /*
        |--------------------------------------------------------------------------
        | END CALENDAR GRID ON SATURDAY
        |--------------------------------------------------------------------------
        */

        $gridEnd = $monthEnd
            ->copy()
            ->endOfWeek(Carbon::SATURDAY);

        /*
        |--------------------------------------------------------------------------
        | GROUP CALENDAR EVENTS BY DATE
        |--------------------------------------------------------------------------
        */

        $events = $this
            ->getCalendarEvents()
            ->groupBy(
                function ($event) {
                    return Carbon::parse($event->date)->format('Y-m-d');
                }
            );

        $calendarStyler = new \App\Filament\Pages\Calendar;
        $calendarStyler->year = $this->year;
        $calendarStyler->month = $this->month;

        $today = now()->format('Y-m-d');

        $cells = [];

        $date = $gridStart->copy();

        /*
        |--------------------------------------------------------------------------
        | CREATE COMPLETE CALENDAR GRID
        |--------------------------------------------------------------------------
        */

        while ($date->lte($gridEnd)) {
            $dateKey = $date->format('Y-m-d');
            $dayEvents = $events->get($dateKey, collect());
            $isCurrentMonth =
                $date->month === $this->month
                && $date->year === $this->year;

            $cells[] = [
                'day' => $date->day,

                'date' => $dateKey,

                'isCurrentMonth' => $isCurrentMonth,

                'isToday' =>
                    $dateKey === $today,

                'hasEvent' => $dayEvents->isNotEmpty(),

                'eventCount' => $isCurrentMonth ? $dayEvents->count() : 0,

                'eventColors' => $isCurrentMonth
                    ? $dayEvents
                        ->take(2)
                        ->map(fn ($event) => $calendarStyler->getEventColor($event))
                        ->values()
                        ->all()
                    : [],

                'isCompleted' => $dayEvents->isNotEmpty()
                    && $dayEvents->every(fn ($event) => $event->is_completed),
            ];

            $date->addDay();
        }

        return $cells;
    }

    /*
    |--------------------------------------------------------------------------
    | UPCOMING DOCUMENT DEADLINES
    |--------------------------------------------------------------------------
    |
    | Rules:
    |
    | 1. Document must have a deadline.
    | 2. Deadline must be today or in the future.
    | 3. Deadline must be within the next 14 days.
    | 4. Completed documents are excluded.
    | 5. Show nearest deadlines first.
    |
    */

    public function getUpcomingDeadlines()
    {
        return Document::query()

            /*
            |--------------------------------------------------------------------------
            | MUST HAVE DEADLINE
            |--------------------------------------------------------------------------
            */

            ->whereNotNull('deadline')

            /*
            |--------------------------------------------------------------------------
            | TODAY OR FUTURE
            |--------------------------------------------------------------------------
            */

            ->whereDate(
                'deadline',
                '>=',
                today()
            )

            /*
            |--------------------------------------------------------------------------
            | WITHIN NEXT 14 DAYS
            |--------------------------------------------------------------------------
            */

            ->whereDate(
                'deadline',
                '<=',
                today()->copy()->addDays(14)
            )

            /*
            |--------------------------------------------------------------------------
            | DON'T SHOW COMPLETED DOCUMENTS
            |--------------------------------------------------------------------------
            */

            ->where(
                'status',
                '!=',
                'completed'
            )

            /*
            |--------------------------------------------------------------------------
            | NEAREST DEADLINE FIRST
            |--------------------------------------------------------------------------
            */

            ->orderBy(
                'deadline',
                'asc'
            )

            /*
            |--------------------------------------------------------------------------
            | SHOW ONLY 5
            |--------------------------------------------------------------------------
            */

            ->limit(5)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | UPCOMING CALENDAR EVENTS / REMINDERS
    |--------------------------------------------------------------------------
    |
    | Shared reminders:
    |
    | Walang user_id restriction dito, kaya ang upcoming reminders na
    | naka-save sa calendars table ay makikita ng authorized admin/staff
    | users na may access sa dashboard.
    |
    */

    public function getUpcomingEvents()
    {
        return Calendar::query()

            /*
            |--------------------------------------------------------------------------
            | TODAY OR FUTURE
            |--------------------------------------------------------------------------
            */

            ->whereDate(
                'date',
                '>=',
                today()
            )

            /*
            |--------------------------------------------------------------------------
            | EARLIEST EVENT FIRST
            |--------------------------------------------------------------------------
            */

            ->orderBy(
                'date',
                'asc'
            )

            ->orderBy(
                'time',
                'asc'
            )

            /*
            |--------------------------------------------------------------------------
            | SHOW ONLY 5
            |--------------------------------------------------------------------------
            */

            ->limit(5)

            ->get();
    }
}
