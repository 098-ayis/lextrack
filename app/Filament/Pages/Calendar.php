<?php

namespace App\Filament\Pages;

use App\Models\Calendar as CalendarModel;
use App\Models\Document;
use App\Models\DocumentRequest;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Filament\Support\Enums\Alignment;
use UnitEnum;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Calendar extends Page
{
   // use HasPageShield;

    protected static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = 'OPERATIONS';

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $title = 'Calendar';

    protected string $view = 'filament.pages.calendar';

    public int $year;

    public int $month;

    public ?string $selectedDate = null;

    #[Url(as: 'show_all', history: true)]
    public bool $showAllEvents = false;

    public string $calendarView = 'month';

    public string $search = '';


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
        $this->selectedDate = $this->showAllEvents
            ? null
            : $now->toDateString();

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

            if (! $this->showAllEvents) {
                $this->selectedDate = $requestedDate;
            }
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
        $this->showAllEvents = true;
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
        $this->showAllEvents = true;
    }

    public function setCalendarView(string $view): void
    {
        if (! in_array($view, ['month', 'week', 'day'], true)) {
            return;
        }

        $this->calendarView = $view;
        $this->showAllEvents = false;

        if ($view !== 'month' && ! $this->selectedDate) {
            $this->selectedDate = Carbon::create(
                $this->year,
                $this->month,
                1
            )->toDateString();
        }
    }

    public function previousCalendarPeriod(): void
    {
        if ($this->calendarView === 'month') {
            $this->previousMonth();

            return;
        }

        $anchor = Carbon::parse($this->selectedDate ?? now()->toDateString());
        $anchor = $this->calendarView === 'week'
            ? $anchor->subWeek()
            : $anchor->subDay();

        $this->selectedDate = $anchor->toDateString();
        $this->year = $anchor->year;
        $this->month = $anchor->month;
    }

    public function nextCalendarPeriod(): void
    {
        if ($this->calendarView === 'month') {
            $this->nextMonth();

            return;
        }

        $anchor = Carbon::parse($this->selectedDate ?? now()->toDateString());
        $anchor = $this->calendarView === 'week'
            ? $anchor->addWeek()
            : $anchor->addDay();

        $this->selectedDate = $anchor->toDateString();
        $this->year = $anchor->year;
        $this->month = $anchor->month;
    }

    public function goToToday(): void
    {
        $today = now();

        $this->year = $today->year;
        $this->month = $today->month;
        $this->selectedDate = $today->format('Y-m-d');
        $this->showAllEvents = false;
    }


    /*
    |--------------------------------------------------------------------------
    | DATE SELECTION
    |--------------------------------------------------------------------------
    */

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->showAllEvents = false;
    }

    public function changeMonth(string $value): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return;
        }
        [$year, $month] = array_map('intval', explode('-', $value));
        if ($year < 1 || $year > 9999) {
            return;
        }
        $this->year = $year;
        $this->month = $month;
        $this->selectedDate = null;
        $this->showAllEvents = true;
    }

    public function clearSelectedDate(): void
    {
        $this->selectedDate = null;
        $this->showAllEvents = true;
    }

    public function openDocumentDeadline(int $documentId): void
    {
        $document = Document::findOrFail($documentId);
        $returnTo = $this->showAllEvents
            ? static::getUrl(['show_all' => '1'])
            : static::getUrl();

        $this->redirect(
            ViewDocument::getUrl([
                'document' => $document->public_id,
                'return_to' => $returnTo,
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
     * If a date is selected and no search is active,
     * only events from that date will be returned.
     *
     * Otherwise all events for the displayed month
     * will be returned.
     */
    public function getEvents(): Collection
    {
        $filterDate = trim($this->search) === ''
            ? $this->selectedDate
            : null;

        $calendarEvents = CalendarModel::query()
            ->with('user')
            ->when(
                $filterDate,
                fn ($query) => $query->whereDate(
                    'date',
                    $filterDate
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

        return $this->filterEvents(
            $this->sortEvents(
                $calendarEvents->concat(
                    $this->getDocumentDeadlineEvents($filterDate)
                )->concat($this->getHolidayEvents($filterDate))
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

        return $this->filterEvents(
            $this->sortEvents(
                $calendarEvents->concat(
                    $this->getDocumentDeadlineEvents()
                )->concat($this->getHolidayEvents())
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

        return $this->filterEvents(
            $this->sortEvents(
                $calendarEvents->concat(
                    $this->getDocumentDeadlineEvents($date)
                )->concat($this->getHolidayEvents($date))
            )
        );
    }

    protected function filterEvents(Collection $events): Collection
    {
        $search = mb_strtolower(trim($this->search), 'UTF-8');

        if ($search === '') {
            return $events;
        }

        return $events
            ->filter(function ($event) use ($search): bool {
                $category = $this->getEventCategory($event);
                $categoryLabel = $this->getEventCategories()[$category] ?? $category;

                $searchableText = collect([
                    $event->event ?? null,
                    $event->details ?? null,
                    $categoryLabel,
                    $event->user?->name ?? null,
                    $event->date ?? null,
                    $event->time ?? null,
                    ($event->is_document_deadline ?? false) ? 'document deadline' : null,
                ])
                    ->filter(fn ($value): bool => filled($value))
                    ->implode(' ');

                return str_contains(
                    mb_strtolower($searchableText, 'UTF-8'),
                    $search
                );
            })
            ->values();
    }


    /**
     * Convert documents with deadlines into calendar-compatible items.
     * These are virtual events, so no duplicate Calendar record is created.
     */
    protected function getHolidayEvents(?string $date = null): Collection
    {
        $display = $date ? Carbon::parse($date) : Carbon::create($this->year, $this->month, 1);

        return app(\App\Services\PhilippineHolidayService::class)
            ->events($display->year, $display->month, $date);
    }

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
                    $document->action_type,
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

    public const EVENT_CATEGORIES = [
        'holiday' => 'Holidays',
        'meeting' => 'Meetings',
        'pickup' => 'Document Pickup',
        'deadline' => 'Deadlines',
    ];

    protected ?Collection $customCategories = null;

    protected function customCategories(): Collection
    {
        return $this->customCategories ??= \App\Models\CalendarCategory::orderBy('name')->get();
    }

    public function getEventCategories(): array
    {
        return self::EVENT_CATEGORIES + $this->customCategories()->pluck('name', 'key')->all();
    }

    public function getEventCategory(object $event): string
    {
        if ($event->is_document_deadline ?? false) {
            return 'deadline';
        }

        if (preg_match('/^document pickup\b/i', (string) ($event->event ?? ''))) {
            return 'pickup';
        }

        $category = $event->category ?? 'meeting';
        if (isset(self::EVENT_CATEGORIES[$category])) {
            return $category;
        }

        return $this->customCategories()->contains('key', $category) ? $category : 'meeting';
    }

    public function getEventTitle(object $event): string
    {
        $title = trim((string) ($event->event ?? ''));

        if ($this->getEventCategory($event) === 'pickup') {
            $title = preg_replace('/^document pickup\s*:\s*/i', '', $title) ?? $title;
            $title = preg_replace('/\s+pickup\b.*$/i', '', $title) ?? $title;

            return 'Document pickup: ' . $this->getPickupPurposeLabel($title);
        }

        return $title !== '' ? $title : 'Untitled event';
    }

    public function getEventDetails(object $event): ?string
    {
        $details = trim((string) ($event->details ?? ''));
        $details = preg_replace('/\.\s+Details:/i', ".\nDetails:", $details) ?? $details;

        return $details !== '' ? $details : null;
    }

    protected function getPickupPurposeLabel(?string $purpose): string
    {
        $purpose = strtolower(trim(str_replace(['_', '-'], ' ', (string) $purpose)));

        return match ($purpose) {
            'certificate', 'certificate request' => 'Certificate',
            'template', 'template request' => 'Template',
            'document', 'document request' => 'Document',
            default => $purpose !== '' ? ucwords($purpose) : 'Document',
        };
    }

    protected function findPickupRequest(
        CalendarModel $event,
        ?string $oldDate,
        ?string $oldTime
    ): ?DocumentRequest {
        if ($event->document_request_id) {
            return $event->documentRequest;
        }

        if (
            $this->getEventCategory($event) !== 'pickup'
            || blank($oldDate)
            || blank($oldTime)
        ) {
            return null;
        }

        $oldPickupAt = Carbon::parse($oldDate . ' ' . $oldTime);
        $eventPurpose = preg_replace(
            '/^document pickup\s*:\s*/i',
            '',
            $this->getEventTitle($event)
        ) ?? '';
        $eventPurpose = strtolower(trim($eventPurpose));
        $requesterName = '';

        if (preg_match(
            '/^document pickup for\s+(.+?)\.\s*(?:details:|$)/is',
            trim((string) $event->details),
            $matches
        )) {
            $requesterName = trim($matches[1]);
        }

        $matches = DocumentRequest::query()
            ->with('user')
            ->where('status', 'accepted')
            ->whereNotNull('pickup_at')
            ->where('pickup_at', $oldPickupAt->format('Y-m-d H:i:s'))
            ->where(function ($query): void {
                $query
                    ->where('copy_type', '!=', 'soft_copy')
                    ->orWhereNull('copy_type');
            })
            ->get()
            ->filter(function (DocumentRequest $request) use ($eventPurpose, $requesterName): bool {
                if (
                    strtolower($this->getPickupPurposeLabel($request->purpose))
                    !== $eventPurpose
                ) {
                    return false;
                }

                return $requesterName === ''
                    || strcasecmp($request->user?->name ?? '', $requesterName) === 0;
            })
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function getEventColor(object $event): string
    {
        return match ($category = $this->getEventCategory($event)) {
            'holiday' => '#c9362b',
            'meeting' => '#0f766e',
            'pickup' => '#0891b2',
            'deadline' => '#6366f1',
            default => $this->customCategories()->firstWhere('key', $category)->color,
        };
    }

    protected function eventCategoryField(): Select
    {
        return Select::make('category')
            ->label('Category')
            ->placeholder('Select a category')
            ->options(fn () => $this->getEventCategories())
            ->native(false)
            ->required()
            ->in(fn () => array_keys($this->getEventCategories()));
    }

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

    protected function eventTimeField(): TextInput
    {
        return TextInput::make('time')
            ->label('Time')
            ->placeholder('08:00 AM')
            ->type('time')
            ->prefixIcon('heroicon-o-clock')
            ->extraInputAttributes([
                'min' => '08:00',
                'max' => '17:00',
                'step' => '300',
            ])
            ->rules([
                'date_format:H:i',
                'after_or_equal:08:00',
                'before_or_equal:17:00',
            ])
            ->required();
    }

    public function createEvent(): Action
    {
        return Action::make('createEvent')
            ->label('Add Event')
            ->icon('heroicon-o-plus')
            ->modalHeading('Add Event')
            ->modalSubmitAction(fn (Action $action): Action => $action
                ->extraAttributes([
                    'style' => 'background-color: #6366F1; border-color: #6366F1; color: #ffffff;',
                ]))
            ->modalFooterActionsAlignment(Alignment::End)
            ->fillForm(fn (): array => [
                'date' => $this->selectedDate ?? now()->toDateString(),
            ])
            ->form([
                $this->eventCategoryField(),

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

                Grid::make(2)
                    ->schema([
                        DatePicker::make('date')
                            ->label('Date')
                            ->native(false)
                            ->displayFormat('M d, Y')
                            ->default(now()->toDateString())
                            ->required(),

                        $this->eventTimeField(),
                    ]),
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
            ->label('Edit')
            ->icon('heroicon-o-pencil')
            ->tooltip('Edit event')
            ->color('gray')
            ->extraAttributes([
                'class' => 'calendar-event-menu-edit w-full justify-start rounded-md px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-100',
            ])
            ->modalHeading('Edit Event')
            ->modalSubmitAction(fn (Action $action): Action => $action
                ->extraAttributes([
                    'style' => 'background-color: #6366F1; border-color: #6366F1; color: #ffffff;',
                ]))
            ->modalFooterActionsAlignment(Alignment::End)
            ->fillForm(function (array $arguments): array {
                $event = CalendarModel::findOrFail($arguments['eventId']);

                return [
                    'event' => $event->event,
                    'category' => $event->category ?? 'meeting',
                    'details' => $event->details,
                    'date' => $event->date?->format('Y-m-d'),
                    'time' => $event->time?->format('H:i'),
                ];
            })
            ->form([
                $this->eventCategoryField(),

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

                Grid::make(2)
                    ->schema([
                        DatePicker::make('date')
                            ->label('Date')
                            ->native(false)
                            ->displayFormat('M d, Y')
                            ->default(now()->toDateString())
                            ->required(),

                        $this->eventTimeField(),
                    ]),
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

                    $pickupRequest = $this->findPickupRequest(
                        $event,
                        $oldDate,
                        $oldTime
                    );


                    /*
                     * user_id is NOT changed.
                     *
                     * The original creator stays as
                     * the event owner.
                     */
                    DB::transaction(function () use (
                        $event,
                        $data,
                        $dateChanged,
                        $timeChanged,
                        $pickupRequest,
                        $newDate,
                        $newTime,
                    ): void {
                        $event->update($data);

                        if ($pickupRequest && ($dateChanged || $timeChanged)) {
                            $pickupRequest->update([
                                'pickup_at' => Carbon::parse(
                                    $newDate . ' ' . $newTime
                                )->format('Y-m-d H:i:s'),
                            ]);

                            if (! $event->document_request_id) {
                                $event->forceFill([
                                    'document_request_id' => $pickupRequest->request_id,
                                ])->save();
                            }
                        }

                        /*
                         * Reset reminders if schedule changes.
                         */
                        if ($dateChanged || $timeChanged) {
                            $event->forceFill([
                                'reminder_3_days_sent_at' => null,
                                'reminder_1_day_sent_at' => null,
                                'reminder_1_hour_sent_at' => null,
                            ])->save();
                        }
                    });


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
