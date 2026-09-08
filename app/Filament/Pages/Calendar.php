<?php

namespace App\Filament\Pages;

use App\Models\Calendar as CalendarModel;
use App\Models\Document;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Calendar extends Page
{
   // use HasPageShield;

    protected static ?int $navigationSort = 7;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $title = 'Calendar';

    protected string $view = 'filament.pages.calendar';

    public int $year;

    public int $month;

    public ?string $selectedDate = null;


    /*
    |--------------------------------------------------------------------------
    | MOUNT
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $now = now();
        $this->year = $now->year;
        $this->month = $now->month;
        $this->selectedDate = $now->toDateString();

        $requestedDate = request()->query('date');

        if (!is_string($requestedDate)) {
            return;
        }

        try {
            $calendarDate = Carbon::createFromFormat(
                '!Y-m-d',
                $requestedDate,
            );

            if ($calendarDate->format('Y-m-d') !== $requestedDate) {
                return;
            }

            $this->selectedDate = $requestedDate;
            $this->year = $calendarDate->year;
            $this->month = $calendarDate->month;
        } catch (\Throwable) {
            // Keep today selected when the query date is invalid.
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MONTH NAVIGATION
    |--------------------------------------------------------------------------
    */

    public function previousMonth(): void
    {
        $date = Carbon::create(
            $this->year,
            $this->month,
            1
        )->subMonth();

        $this->year = $date->year;
        $this->month = $date->month;

        $this->selectedDate = null;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create(
            $this->year,
            $this->month,
            1
        )->addMonth();

        $this->year = $date->year;
        $this->month = $date->month;

        $this->selectedDate = null;
    }

    public function goToToday(): void
    {
        $today = now();

        $this->year = $today->year;
        $this->month = $today->month;
        $this->selectedDate = $today->format('Y-m-d');
    }


    /*
    |--------------------------------------------------------------------------
    | DATE SELECTION
    |--------------------------------------------------------------------------
    */

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function clearSelectedDate(): void
    {
        $this->selectedDate = null;
    }

    public function openDocumentDeadline(int $documentId): void
    {
        $this->redirect(
            ViewDocument::getUrl([
                'document' => $documentId,
            ])
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MONTH HELPERS
    |--------------------------------------------------------------------------
    */

    protected function monthStart(): string
    {
        return Carbon::create(
            $this->year,
            $this->month,
            1
        )
            ->startOfMonth()
            ->format('Y-m-d');
    }

    protected function monthEnd(): string
    {
        return Carbon::create(
            $this->year,
            $this->month,
            1
        )
            ->endOfMonth()
            ->format('Y-m-d');
    }


    /*
    |--------------------------------------------------------------------------
    | EVENTS
    |--------------------------------------------------------------------------
    */

    /**
     * Events shown in the right sidebar.
     *
     * If a date is selected, only events from
     * that date will be returned.
     *
     * Otherwise all events for the displayed month
     * will be returned.
     */
    public function getEvents(): Collection
    {
        $calendarEvents = CalendarModel::query()
            ->with('user')
            ->when(
                $this->selectedDate,
                fn ($query) => $query->whereDate(
                    'date',
                    $this->selectedDate
                ),
                fn ($query) => $query->whereBetween(
                    'date',
                    [
                        $this->monthStart(),
                        $this->monthEnd(),
                    ]
                )
            )
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return $this->sortEvents(
            $calendarEvents->concat(
                $this->getDocumentDeadlineEvents(
                    $this->selectedDate
                )
            )
        );
    }


    /**
     * Get all events in the currently displayed month.
     *
     * NOTE:
     * There is intentionally NO:
     *
     * ->where('user_id', auth()->id())
     *
     * because all staff should see each other's events.
     */
    public function getMonthEvents(): Collection
    {
        $calendarEvents = CalendarModel::query()
            ->with('user')
            ->whereBetween(
                'date',
                [
                    $this->monthStart(),
                    $this->monthEnd(),
                ]
            )
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return $this->sortEvents(
            $calendarEvents->concat(
                $this->getDocumentDeadlineEvents()
            )
        );
    }


    /**
     * Get all events for a specific date.
     */
    public function getEventsForDate(string $date): Collection
    {
        $calendarEvents = CalendarModel::query()
            ->with('user')
            ->whereDate('date', $date)
            ->orderBy('time')
            ->get();

        return $this->sortEvents(
            $calendarEvents->concat(
                $this->getDocumentDeadlineEvents($date)
            )
        );
    }


    /**
     * Convert documents with deadlines into calendar-compatible items.
     * These are virtual events, so no duplicate Calendar record is created.
     */
    protected function getDocumentDeadlineEvents(?string $date = null): Collection
    {
        return Document::query()
            ->with(['latestVersion'])
            ->whereNotNull('deadline')
            ->when(
                $date,
                fn ($query) => $query->whereDate('deadline', $date),
                fn ($query) => $query->whereBetween(
                    'deadline',
                    [
                        $this->monthStart(),
                        $this->monthEnd(),
                    ]
                )
            )
            ->orderBy('deadline')
            ->get()
            ->map(function (Document $document): object {
                $filePath = $document->latestVersion?->file_path;
                $fileName = $filePath ? basename($filePath) : null;

                $title = $document->particulars
                    ?: $document->lao_number
                    ?: $fileName
                    ?: "Document #{$document->document_id}";

                $details = collect([
                    'Document deadline',
                    $document->office_unit,
                    $document->lao_number,
                ])->filter()->implode(' · ');

                return (object) [
                    'sched_id' => "document-deadline-{$document->document_id}",
                    'document_id' => $document->document_id,
                    'is_document_deadline' => true,
                    'is_completed' => $document->status === 'completed',
                    'user_id' => $document->user_id,
                    'user' => null,
                    'event' => $title,
                    'details' => $details,
                    'date' => Carbon::parse($document->deadline)->format('Y-m-d'),
                    'time' => null,
                ];
            });
    }


    /**
     * Sort manual events and document deadlines together.
     */
    protected function sortEvents(Collection $events): Collection
    {
        return $events
            ->sort(function ($first, $second): int {
                $firstDate = Carbon::parse($first->date)->format('Y-m-d');
                $secondDate = Carbon::parse($second->date)->format('Y-m-d');

                $dateComparison = strcmp($firstDate, $secondDate);

                if ($dateComparison !== 0) {
                    return $dateComparison;
                }

                $firstTime = $first->time
                    ? Carbon::parse($first->time)->format('H:i:s')
                    : '00:00:00';

                $secondTime = $second->time
                    ? Carbon::parse($second->time)->format('H:i:s')
                    : '00:00:00';

                return strcmp($firstTime, $secondTime);
            })
            ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | STAFF COLORS
    |--------------------------------------------------------------------------
    |
    | No database migration is required.
    |
    | Each user receives a consistent color based on user_id.
    |
    */

    public function getUserColor(?int $userId): string
    {
        $colors = [
            '#2563EB', // Blue
            '#EA580C', // Orange
            '#16A34A', // Green
            '#9333EA', // Purple
            '#DB2777', // Pink
            '#0891B2', // Cyan
            '#CA8A04', // Yellow
            '#DC2626', // Red
            '#4F46E5', // Indigo
            '#059669', // Emerald
            '#7C3AED', // Violet
            '#0284C7', // Sky
        ];

        if (!$userId) {
            return '#64748B';
        }

        $index = ($userId - 1) % count($colors);

        return $colors[$index];
    }


    /**
     * Staff members who have events in the
     * currently displayed month.
     */
    public function getStaffLegend(): Collection
    {
        return $this->getMonthEvents()
            ->filter(fn ($event) => $event->user !== null)
            ->unique('user_id')
            ->map(function ($event) {
                return [
                    'id' => $event->user_id,

                    'name' =>
                        $event->user?->name
                        ?? 'Unknown Staff',

                    'color' =>
                        $this->getUserColor(
                            $event->user_id
                        ),
                ];
            })
            ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE EVENT
    |--------------------------------------------------------------------------
    */

    protected function eventTimeField(): Select
    {
        return Select::make('time')
            ->label('Time')
            ->placeholder('Select a time')
            ->prefixIcon('heroicon-o-clock')
            ->native(false)
            ->searchable()
            ->searchPrompt('Search a time, e.g. 09:30 AM')
            ->optionsLimit(300)
            ->options(function (?string $state): array {
                $options = [];

                for ($minutes = 0; $minutes < 24 * 60; $minutes += 5) {
                    $time = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
                    $options[$time] = Carbon::createFromFormat('H:i', $time)->format('h:i A');
                }

                // Preserve existing event times that are not on a five-minute interval.
                if ($state && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $state)) {
                    $options[$state] = Carbon::createFromFormat('H:i', $state)->format('h:i A');
                    ksort($options);
                }

                return $options;
            })
            ->required();
    }

    public function createEvent(): Action
    {
        return Action::make('createEvent')
            ->label('Add Event')
            ->icon('heroicon-o-plus')
            ->modalHeading('Add Event')
            ->modalDescription(
                'Add a schedule or important calendar event.'
            )
            ->fillForm(fn (): array => [
                'date' => $this->selectedDate ?? now()->toDateString(),
            ])
            ->form([

                TextInput::make('event')
                    ->label('Event')
                    ->placeholder('Enter event title')
                    ->required()
                    ->maxLength(255),

                Textarea::make('details')
                    ->label('Details')
                    ->placeholder('Enter event details')
                    ->rows(3)
                    ->required(),

                DatePicker::make('date')
                    ->label('Date')
                    ->native(false)
                    ->displayFormat('M d, Y')
                    ->default(now()->toDateString())
                    ->required(),

                $this->eventTimeField(),
            ])
            ->action(function (array $data): void {

                /*
                 * Automatically identify the logged-in
                 * staff member as the event creator.
                 */
                $data['user_id'] = auth()->id();

                CalendarModel::create($data);

                Notification::make()
                    ->title('Event added to calendar')
                    ->body(
                        'The event is now visible to all staff.'
                    )
                    ->success()
                    ->send();
            });
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT EVENT
    |--------------------------------------------------------------------------
    */

    public function editEventAction(): Action
    {
        return Action::make('editEvent')
            ->label('')
            ->icon('heroicon-o-pencil')
            ->tooltip('Edit event')
            ->color('gray')
            ->modalHeading('Edit Event')
            ->modalDescription(
                'Update the event information below.'
            )
            ->fillForm(function (array $arguments): array {
                $event = CalendarModel::findOrFail($arguments['eventId']);

                return [
                    'event' => $event->event,
                    'details' => $event->details,
                    'date' => $event->date?->format('Y-m-d'),
                    'time' => $event->time?->format('H:i'),
                ];
            })
            ->form([

                TextInput::make('event')
                    ->label('Event')
                    ->placeholder('Enter event title')
                    ->required()
                    ->maxLength(255),

                Textarea::make('details')
                    ->label('Details')
                    ->placeholder('Enter event details')
                    ->rows(3)
                    ->required(),

                DatePicker::make('date')
                    ->label('Date')
                    ->native(false)
                    ->displayFormat('M d, Y')
                    ->default(now()->toDateString())
                    ->required(),

                $this->eventTimeField(),
            ])
            ->action(
                function (array $data, array $arguments): void {
                    $event = CalendarModel::findOrFail($arguments['eventId']);

                    /*
                     * Original date
                     */
                    $oldDate = $event->date
                        ? Carbon::parse(
                            $event->date
                        )->format('Y-m-d')
                        : null;

                    /*
                     * New date
                     */
                    $newDate = Carbon::parse(
                        $data['date']
                    )->format('Y-m-d');


                    /*
                     * Original time
                     */
                    $oldTime = $event->time
                        ? Carbon::parse(
                            $event->time
                        )->format('H:i:s')
                        : null;

                    /*
                     * New time
                     */
                    $newTime = Carbon::parse(
                        $data['time']
                    )->format('H:i:s');


                    $dateChanged =
                        $oldDate !== $newDate;

                    $timeChanged =
                        $oldTime !== $newTime;


                    /*
                     * user_id is NOT changed.
                     *
                     * The original creator stays as
                     * the event owner.
                     */
                    $event->update($data);


                    /*
                     * Reset reminders if schedule changes.
                     */
                    if ($dateChanged || $timeChanged) {

                        $event->forceFill([

                            'reminder_3_days_sent_at'
                                => null,

                            'reminder_1_day_sent_at'
                                => null,

                            'reminder_10_minutes_sent_at'
                                => null,

                        ])->save();
                    }


                    Notification::make()
                        ->title('Event updated')
                        ->success()
                        ->send();
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE EVENT
    |--------------------------------------------------------------------------
    */

    public function deleteEvent(int $eventId): void
    {
        CalendarModel::findOrFail(
            $eventId
        )->delete();

        Notification::make()
            ->title('Event deleted')
            ->success()
            ->send();
    }
}
