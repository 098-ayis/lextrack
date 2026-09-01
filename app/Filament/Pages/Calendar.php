<?php

namespace App\Filament\Pages;

use App\Models\Calendar as CalendarModel;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
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


    /*
    |--------------------------------------------------------------------------
    | DATE SELECTION
    |--------------------------------------------------------------------------
    */

    public function selectDate(string $date): void
    {
        $this->selectedDate =
            $this->selectedDate === $date
                ? null
                : $date;
    }

    public function clearSelectedDate(): void
    {
        $this->selectedDate = null;
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
        return CalendarModel::query()
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
        return CalendarModel::query()
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
    }


    /**
     * Get all events for a specific date.
     */
    public function getEventsForDate(string $date): Collection
    {
        return CalendarModel::query()
            ->with('user')
            ->whereDate('date', $date)
            ->orderBy('time')
            ->get();
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

    public function createEvent(): Action
    {
        return Action::make('createEvent')
            ->label('Add Event')
            ->icon('heroicon-o-plus')
            ->modalHeading('Add Event')
            ->modalDescription(
                'Add a schedule or important calendar event.'
            )
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
                    ->required(),

                TimePicker::make('time')
                    ->label('Time')
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('h:i A')
                    ->format('H:i')
                    ->minutesStep(5)
                    ->required(),
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

    public function editEvent(?int $eventId): ?Action
    {
        if (!$eventId) {
            return null;
        }

        $event = CalendarModel::findOrFail($eventId);

        return Action::make("editEvent{$eventId}")
            ->label('')
            ->icon('heroicon-o-pencil')
            ->tooltip('Edit event')
            ->color('gray')
            ->modalHeading('Edit Event')
            ->modalDescription(
                'Update the event information below.'
            )
            ->fillForm([

                'event' => $event->event,

                'details' => $event->details,

                'date' => $event->date
                    ? Carbon::parse(
                        $event->date
                    )->format('Y-m-d')
                    : null,

                'time' => $event->time
                    ? Carbon::parse(
                        $event->time
                    )->format('H:i')
                    : null,
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
                    ->required(),

                TimePicker::make('time')
                    ->label('Time')
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('h:i A')
                    ->format('H:i')
                    ->minutesStep(5)
                    ->required(),
            ])
            ->action(
                function (array $data) use ($event): void {

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