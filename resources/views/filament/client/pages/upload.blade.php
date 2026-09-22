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

        .client-upload-page .filepond--drop-label {
            color: #4b5563 !important;
            transition: color 0.3s ease-in-out !important;
        }

        /* Keep FilePond's file list at the top, with the drop/browse target below it. */
        .client-upload-page .filepond--root .filepond--list-scroller {
            top: 0 !important;
            transform: translate3d(0, 0, 0) !important;
            margin-top: 0 !important;
        }

        .client-upload-page .filepond--root .filepond--drop-label {
            top: auto !important;
            bottom: 0 !important;
        }

        .dark .client-upload-page .filepond--drop-label {
            color: #d1d5db !important;
        }

        .client-upload-page .filepond--root.lextrack-has-files {
            background-color: #ffffff !important;
            border: 1px solid #d1d5db !important;
        }

        .client-upload-page .filepond--root.lextrack-has-files .filepond--drop-label,
        .client-upload-page .filepond--root.lextrack-has-files .filepond--label-action {
            color: #4b5563 !important;
        }

        .client-upload-page .filepond--item-panel {
            background-color: #e5e7eb !important;
            border: 1px solid #9ca3af !important;
        }

        .client-upload-page .filepond--file-info-main,
        .client-upload-page .filepond--file-info-sub,
        .client-upload-page .filepond--file-status-main,
        .client-upload-page .filepond--file-status-sub {
            color: #374151 !important;
        }

        .client-upload-page .filepond--item[data-filepond-item-state="processing-complete"] .filepond--item-panel {
            background-color: #dcfce7 !important;
            border-color: #166534 !important;
        }

        .client-upload-page .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-info-main,
        .client-upload-page .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-info-sub,
        .client-upload-page .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-status-main,
        .client-upload-page .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-status-sub {
            color: #14532d !important;
        }

        .dark .client-upload-page .filepond--item-panel {
            background-color: #374151 !important;
            border-color: #6b7280 !important;
        }

        .dark .client-upload-page .filepond--item[data-filepond-item-state="processing-complete"] .filepond--item-panel {
            background-color: #14532d !important;
            border-color: #4ade80 !important;
        }

        .dark .client-upload-page .filepond--file-info-main,
        .dark .client-upload-page .filepond--file-info-sub,
        .dark .client-upload-page .filepond--file-status-main,
        .dark .client-upload-page .filepond--file-status-sub {
            color: #f3f4f6 !important;
        }

        .dark .client-upload-page .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-info-main,
        .dark .client-upload-page .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-info-sub,
        .dark .client-upload-page .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-status-main,
        .dark .client-upload-page .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-status-sub {
            color: #dcfce7 !important;
        }

        .client-upload-page .lextrack-duplicate-file-message {
            margin-top: 0.5rem;
            color: #b91c1c;
            font-size: 0.875rem;
        }

        .dark .client-upload-page .lextrack-duplicate-file-message {
            color: #fca5a5;
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

        .dark .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--item-panel,
        .dark .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--item-panel {
            background-color: #7f1d1d !important;
            border-color: #f87171 !important;
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
            background-color: #fee2e2 !important;
            border-color: #991b1b !important;
        }

        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-info,
        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-main,
        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-sub,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-info,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-main,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-sub {
            color: #991b1b !important;
            font-weight: 600 !important;
        }

        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--action-remove-item,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--action-remove-item {
            right: 0.5625em !important;
            left: auto !important;
            background-color: #991b1b !important;
            color: #ffffff !important;
        }

        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-info,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-info {
            min-width: 0 !important;
            margin-left: 0 !important;
            margin-right: 0.5em !important;
        }

        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-status {
            min-width: 0 !important;
            max-width: 45% !important;
            margin-left: auto !important;
            margin-right: 2.25em !important;
            overflow: hidden !important;
        }

        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-main,
        .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-sub,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-main,
        .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-sub {
            max-width: 100% !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
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

        .dark .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-info,
        .dark .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-main,
        .dark .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-sub,
        .dark .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-info,
        .dark .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-main,
        .dark .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-sub {
            color: #fecaca !important;
        }

        .dark .client-upload-page .filepond--item[data-filepond-item-state*="invalid"] .filepond--action-remove-item,
        .dark .client-upload-page .filepond--item[data-filepond-item-state*="error"] .filepond--action-remove-item {
            background-color: #991b1b !important;
            color: #ffffff !important;
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

        .client-upload-page .filepond--label-action {
            color: #6366F1 !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            background: transparent !important;
            padding: 0 !important;
        }

        .client-upload-page .filepond--label-action:hover {
            color: #4f46e5 !important;
            text-decoration: underline !important;
        }

        .dark .client-upload-page .filepond--label-action {
            color: #6366F1 !important;
        }

        .btn-custom-primary {
            background-color: #6366F1 !important;
            color: white !important;
            border: none !important;
        }
        
        .btn-custom-primary:hover {
            background-color: #4f46e5 !important;
        }
    </style>

    <script>
        (() => {
            const bindUploadProgress = () => {
                if (!window.FilePond) {
                    return;
                }

                document.querySelectorAll('.client-upload-page .filepond--root').forEach((root) => {
                    if (typeof root.__lexTrackSyncUploadState === 'function') {
                        root.__lexTrackSyncUploadState();
                        return;
                    }

                    const input = root.querySelector('input[type="file"]');
                    const pond = input ? window.FilePond.find(input) : null;

                    if (!pond) {
                        return;
                    }

                    root.dataset.lexTrackProgressBound = 'true';

                    const duplicateMessage = document.createElement('p');
                    duplicateMessage.className = 'lextrack-duplicate-file-message';
                    duplicateMessage.setAttribute('role', 'alert');
                    duplicateMessage.textContent = 'Duplicate file skipped. This file is already selected.';
                    duplicateMessage.hidden = true;
                    root.insertAdjacentElement('afterend', duplicateMessage);

                    const selectedHashes = new Map();
                    const selectedFileSignatures = new Map();

                    const showDuplicateError = () => {
                        duplicateMessage.hidden = false;
                        window.clearTimeout(duplicateMessage.hideTimer);
                        duplicateMessage.hideTimer = window.setTimeout(() => {
                            duplicateMessage.hidden = true;
                        }, 5000);
                    };

                    const hashFile = async (file) => {
                        if (!window.crypto?.subtle) {
                            return null;
                        }

                        const digest = await window.crypto.subtle.digest('SHA-256', await file.arrayBuffer());

                        return Array.from(new Uint8Array(digest), (byte) => byte.toString(16).padStart(2, '0')).join('');
                    };

                    pond.setOptions({
                        beforeAddFile: (fileItem) => {
                            const file = fileItem.file;

                            if (!(file instanceof File)) {
                                return true;
                            }

                            const signature = JSON.stringify([file.name.toLocaleLowerCase(), file.size, file.lastModified]);

                            if (Array.from(selectedFileSignatures.values()).includes(signature)) {
                                showDuplicateError();

                                return false;
                            }

                            selectedFileSignatures.set(fileItem.id, signature);

                            return hashFile(file).then((hash) => {
                                if (hash && Array.from(selectedHashes.values()).includes(hash)) {
                                    selectedFileSignatures.delete(fileItem.id);
                                    showDuplicateError();

                                    return false;
                                }

                                if (hash) {
                                    selectedHashes.set(fileItem.id, hash);
                                }

                                return true;
                            }).catch(() => {
                                // Keep name/size/timestamp duplicate detection active if hashing fails.
                                return true;
                            });
                        },
                    });

                    pond.on('removefile', (_error, fileItem) => {
                        selectedHashes.delete(fileItem.id);
                        selectedFileSignatures.delete(fileItem.id);
                    });

                    const syncUploadState = () => {
                        const hasFiles = pond.getFiles().length > 0 || Boolean(
                            root.querySelector('.filepond--item[data-filepond-item-state]'),
                        );

                        root.classList.toggle('lextrack-has-files', hasFiles);
                    };

                    root.__lexTrackSyncUploadState = syncUploadState;
                    syncUploadState();

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

                    pond.on('addfilestart', syncUploadState);
                    pond.on('updatefiles', syncUploadState);
                    pond.on('removefile', syncUploadState);

                    pond.on('processfilestart', (file) => {
                        syncUploadState();
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

                    <x-filament::button
                        type="submit"
                        size="lg"
                        class="btn-custom-primary"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                    >
                        <span wire:loading.remove wire:target="submit">Submit</span>
                        <span wire:loading wire:target="submit" role="status" aria-live="polite">Submitting...</span>
                    </x-filament::button>
                    
                </div>
                
            </form>
            
        </div>
        
    </div>

</x-filament-panels::page>
