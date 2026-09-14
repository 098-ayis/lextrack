<div class="flex items-center justify-center">
    @if (filled($record->transmittal))
        @include('filament.actions.transmittal-preview', [
            'document' => $record,
            'compact' => true,
        ])
    @else
        <span
            class="text-xs text-gray-400 dark:text-gray-500"
            title="No transmittal/endorsement uploaded"
        >
            &mdash;
        </span>
    @endif
</div>
