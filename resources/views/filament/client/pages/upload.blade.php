<x-filament-panels::page>
    
    <style>
        .fi-input-wrp:focus-within, 
        .fi-input:focus, 
        input:focus, 
        select:focus, 
        textarea:focus {
            border-color: #6366F1 !important;
            --tw-ring-color: #6366F1 !important;
            box-shadow: 0 0 0 2px rgba(98, 59, 231, 0.25) !important;
        }

        .client-upload-page .client-document-subject-field .fi-fo-field-content-col > div:has(.fi-sc-text) {
            width: 100% !important;
            text-align: right !important;
        }

        .client-upload-page [data-field-wrapper]:has(.client-document-subject-counter) .fi-fo-field-content-col > div:has(.client-document-subject-counter) {
            display: flex !important;
            width: 100% !important;
            justify-self: stretch !important;
            justify-content: flex-end !important;
            text-align: right !important;
        }

        .client-upload-page .client-document-subject-field .fi-fo-field-content-col .fi-sc-text {
            display: block !important;
            width: auto !important;
            margin-left: auto !important;
            margin-top: 0.25rem !important;
            font-size: 0.75rem !important;
            line-height: 1rem !important;
            text-align: right !important;
        }

        .client-upload-page [data-field-wrapper]:has(.client-document-subject-counter) .client-document-subject-counter {
            display: block !important;
            width: auto !important;
            margin-left: 0 !important;
            text-align: right !important;
            font-size: 0.75rem !important;
            line-height: 1rem !important;
        }

        .dark .client-upload-page .client-document-subject-field .fi-fo-field-content-col .fi-sc-text {
            color: #9ca3af !important;
        }

        .filepond--root {
            background-color: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.75rem !important;
            transition: background-color 0.3s ease-in-out !important;
        }

        .dark .client-upload-page .filepond--root {
            background-color: #1f2937 !important;
            border-color: #4b5563 !important;
        }

        .filepond--panel-root {
            background-color: transparent !important;
            border: none !important;
        }

        .filepond--drop-label {
            min-height: 140px !important;
            color: #4b5563 !important;
            transition: color 0.3s ease-in-out !important;
        }

        .dark .client-upload-page .filepond--drop-label {
            color: #d1d5db !important;
        }

        .filepond--root.lextrack-has-files {
            background-color: #ffffff !important;
            border: 1px solid #d1d5db !important;
        }

        .filepond--root.lextrack-has-files .filepond--drop-label,
        .filepond--root.lextrack-has-files .filepond--label-action {
            color: #4b5563 !important;
        }

        /* Keep uploaded rows above the browse area instead of docking them at the bottom. */
        .client-upload-page .filepond--root.lextrack-has-files {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            justify-content: flex-start !important;
            height: auto !important;
            min-height: 0 !important;
            overflow: hidden !important;
        }

        .client-upload-page .filepond--root.lextrack-has-files > .filepond--list-scroller,
        .client-upload-page .filepond--root.lextrack-has-files .filepond--list-scroller {
            position: relative !important;
            top: auto !important;
            right: auto !important;
            bottom: auto !important;
            left: auto !important;
            order: 1 !important;
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            width: 100% !important;
            margin: 0 !important;
            overflow: visible !important;
        }

        .client-upload-page .filepond--root.lextrack-has-files > .filepond--list-scroller .filepond--list,
        .client-upload-page .filepond--root.lextrack-has-files .filepond--list {
            position: relative !important;
            top: auto !important;
            right: auto !important;
            bottom: auto !important;
            left: auto !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.25rem !important;
            height: auto !important;
            min-height: 0 !important;
            transform: none !important;
        }

        .client-upload-page .filepond--root.lextrack-has-files .filepond--item {
            position: relative !important;
            top: auto !important;
            right: auto !important;
            bottom: auto !important;
            left: auto !important;
            flex: 0 0 auto !important;
            width: 100% !important;
            transform: none !important;
            margin: 0 !important;
        }

        .client-upload-page .filepond--root.lextrack-has-files > .filepond--drop-label,
        .client-upload-page .filepond--root.lextrack-has-files .filepond--drop-label {
            position: relative !important;
            order: 2 !important;
            flex: 0 0 140px !important;
            height: 140px !important;
            min-height: 140px !important;
            top: auto !important;
            right: auto !important;
            bottom: auto !important;
            left: auto !important;
            color: #4b5563 !important;
        }

        .client-upload-page .filepond--root.lextrack-has-files > .filepond--panel {
            position: absolute !important;
            inset: 0 !important;
            height: 100% !important;
            pointer-events: none !important;
        }

        .dark .client-upload-page .filepond--root.lextrack-has-files .filepond--drop-label,
        .dark .client-upload-page .filepond--root.lextrack-has-files .filepond--label-action {
            color: #d1d5db !important;
        }

        /* Final FilePond override: keep uploaded rows at the top of every box. */
        .client-upload-page .filepond--root {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            justify-content: flex-start !important;
            height: auto !important;
            min-height: 140px !important;
            overflow: hidden !important;
        }

        .client-upload-page .filepond--root > .filepond--panel {
            position: absolute !important;
            inset: 0 !important;
            height: 100% !important;
            pointer-events: none !important;
            z-index: 2 !important;
        }

        .client-upload-page .filepond--root > .filepond--list-scroller {
            position: relative !important;
            inset: auto !important;
            order: 1 !important;
            flex: 0 0 auto !important;
            width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            margin: 0 !important;
            overflow: visible !important;
            z-index: 6 !important;
        }

        .client-upload-page .filepond--root > .filepond--list-scroller > .filepond--list,
        .client-upload-page .filepond--root .filepond--list {
            position: relative !important;
            inset: auto !important;
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            gap: 0.25rem !important;
            transform: none !important;
        }

        .client-upload-page .filepond--root .filepond--item {
            position: relative !important;
            inset: auto !important;
            flex: 0 0 3.5rem !important;
            width: 100% !important;
            height: 3.5rem !important;
            min-height: 3.5rem !important;
            margin: 0 !important;
            transform: none !important;
        }

        .client-upload-page .filepond--root > .filepond--drop-label {
            position: relative !important;
            inset: auto !important;
            order: 2 !important;
            flex: 0 0 140px !important;
            width: 100% !important;
            height: 140px !important;
            min-height: 140px !important;
            margin: 0 !important;
            z-index: 5 !important;
        }

        .filepond--item-panel {
            background-color: #379b68 !important;
        }

        .client-upload-page .filepond--file-info-main,
        .client-upload-page .filepond--file-info-sub,
        .client-upload-page .filepond--file-status-main,
        .client-upload-page .filepond--file-status-sub {
            color: #ffffff !important;
        }

        .client-upload-page .filepond--file-status-main {
            font-weight: 600 !important;
        }

        .client-upload-page .filepond--file-status-sub {
            font-size: 0.75rem !important;
        }

        .client-upload-page .filepond--file-action-button.filepond--action-remove-item {
            background-color: #065f46 !important;
            color: #ffffff !important;
            border-radius: 9999px !important;
        }

        .dark .client-upload-page .filepond--item-panel {
            background-color: #166534 !important;
        }

        .dark .client-upload-page .filepond--root.lextrack-has-files {
            background-color: #111827 !important;
            border-color: #4b5563 !important;
        }

        /* Keep oversized/invalid files visible in light mode as well as dark mode. */
        .client-upload-page .filepond--root:has(.filepond--item[data-filepond-item-state*="invalid"]),
        .client-upload-page .filepond--root:has(.filepond--item[data-filepond-item-state*="error"]) {
            background-color: #fef2f2 !important;
            border-color: #dc2626 !important;
        }

        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--item-panel,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--item-panel {
            background-color: #dc2626 !important;
        }

        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-info,
        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-main,
        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-sub,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-info,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-main,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-sub {
            color: #ffffff !important;
            font-weight: 600 !important;
        }

        .client-upload-page .fi-fo-file-upload-error-message {
            color: #b91c1c !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
        }

        .dark .client-upload-page .filepond--root:has(.filepond--item[data-filepond-item-state*="invalid"]),
        .dark .client-upload-page .filepond--root:has(.filepond--item[data-filepond-item-state*="error"]) {
            background-color: #450a0a !important;
            border-color: #f87171 !important;
        }

        .dark .client-upload-page .fi-fo-file-upload-error-message {
            color: #fca5a5 !important;
        }
        
        .filepond--file-info {
            color: #1f2937 !important;
        }

        .dark .client-upload-page .filepond--file-info {
            color: #f3f4f6 !important;
        }

        .filepond--label-action {
            color: #374151 !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            background: transparent !important;
            padding: 0 !important;
        }

        .filepond--label-action:hover {
            color: #111827 !important;
            text-decoration: underline !important;
        }

        .dark .client-upload-page .filepond--label-action {
            color: #d1d5db !important;
        }

        .btn-custom-primary {
            background-color: #623BE7 !important;
            color: white !important;
            border: none !important;
        }
        
        .btn-custom-primary:hover {
            background-color: #502ec3 !important; 
        }
    </style>

    <script>
        (() => {
            const bindUploadProgress = () => {
                if (!window.FilePond) {
                    return;
                }

                document.querySelectorAll('.client-upload-page .filepond--root').forEach((root) => {
                    if (typeof root.__lexTrackSyncUploadLayout === 'function') {
                        root.__lexTrackSyncUploadLayout();
                        return;
                    }

                    const input = root.querySelector('input[type="file"]');
                    const pond = input ? window.FilePond.find(input) : null;

                    if (!pond) {
                        return;
                    }

                    root.dataset.lexTrackProgressBound = 'true';

                    const syncUploadLayout = () => {
                        const hasFiles = pond.getFiles().length > 0 || Boolean(
                            root.querySelector('.filepond--item[data-filepond-item-state]'),
                        );
                        const listScroller = root.querySelector('.filepond--list-scroller');
                        const dropLabel = root.querySelector('.filepond--drop-label');

                        root.classList.toggle('lextrack-has-files', hasFiles);

                        // FilePond normally docks the list at the bottom of the
                        // integrated panel. Keep the real file list before the
                        // browse/drop label so uploaded rows appear at the top.
                        if (hasFiles && listScroller && dropLabel) {
                            root.insertBefore(listScroller, dropLabel);
                        }
                    };

                    root.__lexTrackSyncUploadLayout = syncUploadLayout;
                    syncUploadLayout();

                    const updateStatus = (file, mainText, subText = '') => {
                        const item = document.getElementById(`filepond--item-${file.id}`);

                        if (!item) {
                            return;
                        }

                        const main = item.querySelector('.filepond--file-status-main');
                        const sub = item.querySelector('.filepond--file-status-sub');

                        if (main) {
                            main.textContent = mainText;
                        }

                        if (sub && subText) {
                            sub.textContent = subText;
                        }
                    };

                    pond.on('addfilestart', syncUploadLayout);
                    pond.on('updatefiles', syncUploadLayout);
                    pond.on('removefile', syncUploadLayout);

                    pond.on('processfilestart', (file) => {
                        syncUploadLayout();
                        window.requestAnimationFrame(() => updateStatus(file, 'Uploading 0%', '0%'));
                    });

                    pond.on('processfileprogress', (file, progress) => {
                        const percentage = Math.round(progress * 100);
                        updateStatus(file, `Uploading ${percentage}%`, `${percentage}%`);
                    });

                    pond.on('processfile', (error, file) => {
                        updateStatus(file, error ? 'Upload failed' : 'Upload complete', error ? 'Please try again' : 'tap to undo');
                    });
                });
            };

            const startUploadProgressWatcher = () => {
                bindUploadProgress();

                const observer = new MutationObserver(bindUploadProgress);
                observer.observe(document.body, { childList: true, subtree: true });

                const retryTimer = window.setInterval(bindUploadProgress, 250);
                window.setTimeout(() => window.clearInterval(retryTimer), 10000);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startUploadProgressWatcher, { once: true });
            } else {
                startUploadProgressWatcher();
            }
        })();
    </script>

    <div class="client-upload-page bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        
        <div style="background-color: #0F172A; border-top-left-radius: 0.75rem; border-top-right-radius: 0.75rem; padding: 1.5rem;">
            <h2 style="color: #6366f1; font-size: 1.5rem; font-weight: 600; margin: 0;">
                Submit A Document
            </h2>
            <p style="color: #e5e7eb; margin-top: 0.25rem; font-size: 0.875rem;">
                Please provide the details of your document and attach the necessary files.
            </p>
        </div>

        <!-- Form and Buttons Container -->
        <div class="p-6">
            
            <form wire:submit="submit">
                
                {{ $this->form }}

                <!-- Action Buttons aligned to the right -->
                <div class="flex justify-end gap-4 mt-6">
                    
                    <x-filament::button color="gray" variant="outline" wire:click="clearForm" size="lg" type="button">
                        Clear
                    </x-filament::button>

                    <x-filament::button type="submit" size="lg" class="btn-custom-primary">
                        Submit
                    </x-filament::button>
                    
                </div>
                
            </form>
            
        </div>
        
    </div>

</x-filament-panels::page>
