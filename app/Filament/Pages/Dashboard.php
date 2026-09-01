<?php

namespace App\Filament\Pages;

use App\Models\Calendar;
use App\Models\Document;
use Carbon\Carbon;
use Filament\Pages\Page;
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
            ->with('latestVersion')

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
        $start = Carbon::create(
            $this->year,
            $this->month,
            1
        )->startOfMonth();

        $end = $start
            ->copy()
            ->endOfMonth();

        return Calendar::query()

            ->whereBetween(
                'date',
                [
                    $start->toDateString(),
                    $end->toDateString(),
                ]
            )

            ->orderBy('date')

            ->orderBy('time')

            ->get();
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
                    return $event
                        ->date
                        ->format('Y-m-d');
                }
            );

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

            $cells[] = [
                'day' => $date->day,

                'date' => $dateKey,

                'isCurrentMonth' =>
                    $date->month === $this->month
                    &&
                    $date->year === $this->year,

                'isToday' =>
                    $dateKey === $today,

                'hasEvent' =>
                    $events->has($dateKey),
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
