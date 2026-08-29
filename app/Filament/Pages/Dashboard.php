<?php

namespace App\Filament\Pages;

use App\Models\Calendar;
use App\Models\Document;
use Carbon\Carbon;
use Filament\Pages\Page;

class Dashboard extends Page
{
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
            // All documents regardless of status.
            'total' => Document::count(),

            // Pending only.
            'pending' => Document::where('status', 'pending')
                ->count(),

            // In progress only.
            'active' => Document::where('status', 'in_progress')
                ->count(),

            // Completed ONLY.
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
                $this->statusFilter !== '',
                fn ($query) =>
                    $query->where('status', $this->statusFilter)
            )

            ->latest('updated_at')

            ->limit(6)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | CALENDAR NAVIGATION
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
    | CALENDAR EVENTS FOR CURRENT DISPLAYED MONTH
    |--------------------------------------------------------------------------
    |
    | No user_id restriction is used here because the calendar is shared
    | between authorized Legal Office admin/staff users.
    |
    */

    public function getCalendarEvents()
    {
        $start = Carbon::create(
            $this->year,
            $this->month,
            1
        )->startOfMonth();

        $end = $start->copy()->endOfMonth();

        return Calendar::query()
            ->whereBetween('date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
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
        $monthStart = Carbon::create(
            $this->year,
            $this->month,
            1
        )->startOfMonth();

        $monthEnd = $monthStart
            ->copy()
            ->endOfMonth();

        $gridStart = $monthStart
            ->copy()
            ->startOfWeek(Carbon::SUNDAY);

        $gridEnd = $monthEnd
            ->copy()
            ->endOfWeek(Carbon::SATURDAY);

        $events = $this->getCalendarEvents()
            ->groupBy(
                fn ($event) =>
                    $event->date->format('Y-m-d')
            );

        $today = now()->format('Y-m-d');

        $cells = [];

        $date = $gridStart->copy();

        while ($date->lte($gridEnd)) {
            $dateKey = $date->format('Y-m-d');

            $cells[] = [
                'day' => $date->day,

                'date' => $dateKey,

                'isCurrentMonth' =>
                    $date->month === $this->month &&
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
    | Only Pending and In Progress documents are considered active deadlines.
    | Completed, returned, archived, outgoing and rejected documents are not
    | included.
    |
    */

    public function getUpcomingDeadlines()
    {
        return Document::query()
            ->whereNotNull('deadline')

            ->whereDate(
                'deadline',
                '>=',
                today()
            )

            ->whereDate(
                'deadline',
                '<=',
                today()->copy()->addDays(14)
            )

            ->whereIn('status', [
                'pending',
                'in_progress',
            ])

            ->orderBy('deadline')

            ->limit(5)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | UPCOMING CALENDAR EVENTS / REMINDERS
    |--------------------------------------------------------------------------
    |
    | Shared reminders:
    | No user_id condition is used, therefore upcoming events can be seen
    | by authorized users who have access to this dashboard.
    |
    */

    public function getUpcomingEvents()
    {
        return Calendar::query()
            ->whereDate(
                'date',
                '>=',
                today()
            )
            ->orderBy('date')
            ->orderBy('time')
            ->limit(5)
            ->get();
    }
}