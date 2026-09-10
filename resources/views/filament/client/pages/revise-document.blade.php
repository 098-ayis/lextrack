<x-filament-panels::page>
    <style>
        .revise-document-page {
            max-width: 860px;
        }

        .revise-document-card {
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }

        .revise-document-header {
            padding: 24px 28px 22px;
            background: #0f172a;
        }

        .revise-document-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 18px;
            color: #c7d2fe;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.15s, transform 0.15s;
        }

        .revise-document-back:hover {
            color: #ffffff;
            transform: translateX(-2px);
        }

        .revise-document-header h2 {
            margin: 0;
            color: #818cf8;
            font-size: 23px;
            font-weight: 700;
            line-height: 1.2;
        }

        .revise-document-header p {
            margin: 7px 0 0;
            color: #e5e7eb;
            font-size: 14px;
            line-height: 1.5;
        }

        .revise-document-body {
            padding: 26px 28px 28px;
        }

        .revise-document-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .revision-closed-state {
            display: flex;
            align-items: center;
            flex-direction: column;
            gap: 10px;
            padding: 34px 20px 38px;
            text-align: center;
        }

        .revision-closed-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            background: #dcfce7;
            border-radius: 50%;
            color: #16a34a;
        }

        .revision-closed-state h3 {
            margin: 4px 0 0;
            color: #111827;
            font-size: 17px;
            font-weight: 700;
        }

        .revision-closed-state p {
            max-width: 430px;
            margin: 0;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
        }

        .revision-closed-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 8px;
            padding: 9px 14px;
            background: #6366f1;
            border-radius: 8px;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.15s, transform 0.15s;
        }

        .revision-closed-back:hover {
            background: #4f46e5;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .dark .revise-document-card {
            background: #111827;
            border-color: #374151;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
        }

        .dark .revise-document-header {
            background: #0b1120;
        }

        .dark .revise-document-header p {
            color: #d1d5db;
        }

        .dark .revision-closed-icon {
            background: #14532d;
            color: #86efac;
        }

        .dark .revision-closed-state h3 {
            color: #f9fafb;
        }

        .dark .revision-closed-state p {
            color: #9ca3af;
        }

        @media (max-width: 640px) {
            .revise-document-header,
            .revise-document-body {
                padding-right: 18px;
                padding-left: 18px;
            }

            .revise-document-actions {
                justify-content: stretch;
            }

            .revise-document-actions > * {
                flex: 1 1 0;
            }
        }
    </style>

    <div class="revise-document-page mx-auto w-full">
        <div class="revise-document-card">
            <div class="revise-document-header">
                <a
                    href="{{ \App\Filament\Client\Pages\Messages::getUrl() }}"
                    class="revise-document-back"
                >
                    <x-heroicon-o-arrow-left class="h-4 w-4" />
                    Back to Messages
                </a>

                <h2>Upload Revised Document</h2>

                <p>
                    Upload a revised version of “{{ $documentRecord?->particulars ?? 'your document' }}”.
                </p>
            </div>

            @if ($revisionRequestClosed)
                <div class="revision-closed-state">
                    <div class="revision-closed-icon">
                        <x-heroicon-o-check class="h-7 w-7" />
                    </div>

                    <h3>Revision already submitted</h3>

                    <p>
                        This revision request has already been completed. A new upload link will be provided if another revision is needed.
                    </p>

                    <a
                        href="{{ \App\Filament\Client\Pages\Messages::getUrl() }}"
                        class="revision-closed-back"
                    >
                        <x-heroicon-o-arrow-left class="h-4 w-4" />
                        Back to Messages
                    </a>
                </div>
            @else
                <div class="revise-document-body">
                    <form wire:submit="submit" class="space-y-6">
                        {{ $this->form }}

                        <div class="revise-document-actions">
                            <x-filament::button
                                color="primary"
                                variant="outline"
                                type="button"
                                wire:click="clearForm"
                            >
                                Clear
                            </x-filament::button>

                            <x-filament::button
                                color="primary"
                                type="submit"
                            >
                                Submit Revision
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
