<?php

namespace App\Filament\Pages;

use App\Models\Calendar as CalendarModel;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Calendar extends Page
{
    protected static ?int $navigationSort = 7;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $title = 'Calendar';

    protected string $view = 'filament.pages.calendar';

    public int $year;

    public int $month;

    public ?string $selectedDate = null;

    public function mount(): void
    {
        $now = now();

        $this->year = $now->year;
        $this->month = $now->month;
    }

    public function previousMonth(): void
    {
        $date = now()
            ->setDate($this->year, $this->month, 1)
            ->subMonth();

        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = now()
            ->setDate($this->year, $this->month, 1)
            ->addMonth();

        $this->year = $date->year;
        $this->month = $date->month;
    }

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

    public function getEvents(): Collection
    {
        return CalendarModel::query()
            ->when(
                $this->selectedDate,
                fn ($query) => $query->whereDate('date', $this->selectedDate),
                fn ($query) => $query
                    ->whereBetween(
                        'date',
                        [
                            sprintf('%04d-%02d-01', $this->year, $this->month),
                            now()
                                ->setDate($this->year, $this->month, 1)
                                ->endOfMonth()
                                ->format('Y-m-d'),
                        ]
                    )
            )
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    public function getMonthEvents(): Collection
    {
        return CalendarModel::query()
            ->whereBetween(
                'date',
                [
                    sprintf('%04d-%02d-01', $this->year, $this->month),
                    now()
                        ->setDate($this->year, $this->month, 1)
                        ->endOfMonth()
                        ->format('Y-m-d'),
                ]
            )
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    public function getEventsForDate(string $date): Collection
    {
        return CalendarModel::query()
            ->whereDate('date', $date)
            ->orderBy('time')
            ->get();
    }

    public function createEvent(): Action
    {
        return Action::make('createEvent')
            ->label('Add Event')
            ->icon('heroicon-o-plus')
            ->modalHeading('Add Event')
            ->form([
                TextInput::make('event')
                    ->label('Event')
                    ->required()
                    ->maxLength(255),

                Textarea::make('details')
                    ->label('Details')
                    ->required(),

                DatePicker::make('date')
                    ->label('Date')
                    ->required(),

                TimePicker::make('time')
                    ->label('Time')
                    ->required(),

                Select::make('user_id')
                ->label('Added by')
                ->options(
                    \App\Models\User::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->searchable()
                ->required(),
            ])
            ->action(function (array $data): void {
                CalendarModel::create($data);

                Notification::make()
                    ->title('Event added to calendar')
                    ->success()
                    ->send();
            });
    }

    public function editEvent(?int $eventId): ?Action
    {
        if (!$eventId) {
            return null;
        }

        $event = CalendarModel::findOrFail($eventId);

        return Action::make("editEvent{$eventId}")
            ->label('Edit')
            ->icon('heroicon-o-pencil')
            ->modalHeading('Edit Event')
            ->fillForm([
                'event' => $event->event,
                'details' => $event->details,
                'date' => $event->date?->format('Y-m-d'),
                'time' => $event->time,
                'user_id' => $event->user_id,
            ])
            ->form([
                TextInput::make('event')
                    ->label('Event')
                    ->required()
                    ->maxLength(255),

                Textarea::make('details')
                    ->label('Details')
                    ->required(),

                DatePicker::make('date')
                    ->label('Date')
                    ->required(),

                TimePicker::make('time')
                    ->label('Time')
                    ->required(),

                Select::make('user_id')
                    ->label('Added by')
                    ->options(
                        \App\Models\User::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data) use ($event): void {
                $event->update($data);

                Notification::make()
                    ->title('Event updated')
                    ->success()
                    ->send();
            });
    }

    public function deleteEvent(int $eventId): void
    {
        CalendarModel::findOrFail($eventId)->delete();

        Notification::make()
            ->title('Event deleted')
            ->success()
            ->send();
    }
}