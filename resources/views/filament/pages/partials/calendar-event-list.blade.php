<div class="office-calendar-event-list">
    @forelse($entries as $event)
        @php
            $deadline = $event->is_document_deadline ?? false;
            $color = $this->getEventColor($event);
        @endphp
        <div class="office-calendar-list-item" wire:key="{{ $showDetails ? 'sidebar' : 'modal' }}-event-{{ $event->sched_id }}">
            <div class="office-calendar-list-title">
                <i style="background:{{ $color }}"></i>
                @if($deadline)
                    <button type="button" wire:click="openDocumentDeadline({{ $event->document_id }})" title="{{ $event->event }}">{{ $event->event }}</button>
                @else
                    <span title="{{ $event->event }}">{{ $event->event }}</span>
                @endif
                @if($event->is_completed)<span class="office-calendar-complete" aria-label="Completed">✓</span>@endif
            </div>
            @if($showDetails)
                <p>{{ \Carbon\Carbon::parse($event->date)->format('M j') }}{{ $event->time ? ' · '.\Carbon\Carbon::parse($event->time)->format('g:i A') : '' }}</p>
                @if($event->details)<p>{{ $event->details }}</p>@endif
                @unless($deadline || ($event->is_automatic_holiday ?? false))
                    <div class="office-calendar-event-actions">
                        {{ ($this->editEventAction)(['eventId' => $event->sched_id]) }}
                        <button type="button" wire:click="deleteEvent({{ $event->sched_id }})" wire:confirm="Delete this event?" aria-label="Delete event"><x-heroicon-o-trash class="h-4 w-4" /></button>
                    </div>
                @endunless
            @else
                <span class="office-calendar-list-owner">{{ $this->getEventCategories()[$this->getEventCategory($event)] }}</span>
            @endif
        </div>
    @empty
        <p class="office-calendar-empty">No events.</p>
    @endforelse
</div>
