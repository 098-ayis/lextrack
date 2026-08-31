<x-filament-panels::page>
    <div
        class="client-request-page w-full overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        x-data="requestQrScanner"
        x-on:beforeunload.window="stopScanner()"
        x-on:paste.window="handlePaste($event)"
    >
        <div class="request-page-header px-6 py-6">
            <h1 class="text-2xl font-semibold tracking-tight text-[#6366F1]">
                Request A Document
            </h1>
            <p class="mt-1 text-sm text-gray-100">
                Enter the LAO number and purpose for the document you need.
            </p>
        </div>
        <form wire:submit="submit" class="p-6">
            <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
                <div class="space-y-7">
                    <div>
                        <label for="request-lao-number" class="request-field-label">
                            LAO Number <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="request-lao-number"
                            type="text"
                            wire:model.blur="laoNumber"
                            x-on:input="error = ''; qrVerified = false; qrMessage = ''"
                            class="request-field-input"
                        >
                        @error('laoNumber')
                            <p class="request-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="request-purpose" class="request-field-label">
                            Purpose <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="request-purpose"
                            wire:model.live="purpose"
                            class="request-field-input"
                        >
                            <option value="">Select a purpose</option>
                            @foreach ($this->purposeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('purpose')
                            <p class="request-error">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($purpose === 'other')
                        <div>
                            <label for="request-other-purpose" class="request-field-label">
                                Please specify <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                id="request-other-purpose"
                                wire:model="otherPurpose"
                                rows="4"
                                placeholder="Please specify why you need this document"
                                class="request-field-input resize-y"
                            ></textarea>
                            @error('otherPurpose')
                                <p class="request-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>

                <div>
                    <label class="request-field-label">
                        Scan QR
                    </label>

                    <div
                        class="request-scanner-box"
                        :class="{
                            'qr-success': qrVerified,
                            'qr-error': qrMessage || error,
                        }"
                    >
                        <div
                            x-show="!scanning"
                            class="flex min-h-[25rem] flex-col items-center justify-center px-6 text-center"
                        >
                            <div
                                x-show="!qrVerified && !qrMessage && !error"
                                class="flex flex-col items-center"
                            >
                                <svg class="h-20 w-20 text-[#6366F1]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 8.25V5.5A1.5 1.5 0 0 1 6 4h2.75M15.25 4H18a1.5 1.5 0 0 1 1.5 1.5v2.75M19.5 15.75v2.75A1.5 1.5 0 0 1 18 20h-2.75M8.75 20H6a1.5 1.5 0 0 1-1.5-1.5v-2.75" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h3v3H7zM14 14h3v3h-3zM14 7h1.5v1.5H17M7 14v3h3v-1.5H8.5V14" />
                                </svg>
                                <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-300">
                                    Scan a QR code or upload a QR photo
                                </p>
                            </div>

                            <div
                                x-cloak
                                x-show="qrVerified || qrMessage || error"
                                class="flex flex-col items-center"
                            >
                                <svg
                                    x-show="qrVerified"
                                    class="h-14 w-14 text-green-600 dark:text-green-300"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="9" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8 12 2.5 2.5L16 9" />
                                </svg>
                                <svg
                                    x-show="qrMessage || error"
                                    class="h-14 w-14 text-red-600 dark:text-red-300"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="9" />
                                    <path stroke-linecap="round" d="M12 8v4" />
                                    <path stroke-linecap="round" d="M12 16h.01" />
                                </svg>
                                <p
                                    x-show="qrVerified"
                                    class="mt-4 text-sm font-semibold text-green-700 dark:text-green-200"
                                >
                                    QR code verified. LAO number selected.
                                </p>
                                <p
                                    x-show="qrMessage"
                                    x-text="qrMessage"
                                    class="mt-4 text-sm font-semibold text-red-700 dark:text-red-200"
                                ></p>
                                <p
                                    x-show="error"
                                    x-text="error"
                                    class="mt-4 text-sm font-semibold text-red-700 dark:text-red-200"
                                ></p>
                            </div>

                            <div class="mt-6 flex flex-wrap justify-center gap-3">
                                <button
                                    type="button"
                                    x-on:click="startScanner()"
                                    class="request-secondary-button"
                                >
                                    Scan with camera
                                </button>

                                <label for="request-qr-photo" class="request-secondary-button cursor-pointer">
                                    Upload QR photo
                                </label>
                                <input
                                    id="request-qr-photo"
                                    type="file"
                                    accept="image/*"
                                    x-on:change="handleQrPhoto($event)"
                                    class="sr-only"
                                >

                            </div>
                        </div>

                        <div x-cloak x-show="scanning" class="relative flex min-h-[25rem] flex-col items-center justify-center p-4">
                            <video x-ref="video" autoplay muted playsinline class="max-h-[22rem] w-full rounded-xl bg-black object-cover"></video>
                            <div class="pointer-events-none absolute left-1/2 top-1/2 h-48 w-48 -translate-x-1/2 -translate-y-1/2 rounded-xl border-4 border-[#6366F1] shadow-[0_0_0_9999px_rgba(15,23,42,0.35)]"></div>
                            <button
                                type="button"
                                x-on:click="stopScanner()"
                                class="request-secondary-button request-stop-scanner-button absolute bottom-6 left-1/2 z-10 -translate-x-1/2"
                            >
                                Stop scanner
                            </button>
                        </div>

                    </div>

                    <div wire:loading wire:target="attachment" class="mt-2 text-sm text-[#6366F1]">
                        Uploading attachment...
                    </div>
                    @if ($attachment)
                        <p class="mt-2 truncate text-sm text-gray-600 dark:text-gray-300">
                            Selected: {{ $attachment->getClientOriginalName() }}
                        </p>
                    @endif
                    @error('attachment')
                        <p class="request-error">{{ $message }}</p>
                    @enderror

                </div>
            </div>

            <div class="mt-10 flex justify-end gap-3 sm:gap-4">
                <button
                    type="button"
                    wire:click="clearForm"
                    x-on:click="resetQrState()"
                    class="request-clear-button"
                >
                    Clear
                </button>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="submit"
                    class="request-primary-button"
                >
                    <span wire:loading.remove wire:target="submit">Submit</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </button>
            </div>
        </form>
    </div>

    <style>
        [x-cloak] { display: none !important; }

        .client-request-page {
            --request-indigo: #6366f1;
        }

        .request-page-header {
            background: #0f172a;
        }

        .request-field-label {
            display: block;
            margin-bottom: 0.6rem;
            color: #164e77;
            font-size: 1rem;
            font-weight: 700;
        }

        .dark .request-field-label {
            color: #f3f4f6;
        }

        .request-field-input {
            display: block;
            width: 100%;
            border: 1px solid #9ca3af;
            border-radius: 0.75rem;
            background: #f5f5f9;
            color: #111827;
            padding: 0.85rem 1rem;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
        }

        .request-field-input::placeholder {
            color: #9ca3af;
        }

        .request-field-input:focus {
            border-color: var(--request-indigo);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        select.request-field-input {
            appearance: none;
            cursor: pointer;
            padding-right: 2.75rem;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%236b7280' stroke-width='1.8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m5 7.5 5 5 5-5'/%3E%3C/svg%3E") !important;
            background-position: right 1rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1rem !important;
        }

        .dark .request-field-input {
            border-color: #4b5563;
            background: #1f2937;
            color: #f9fafb;
        }

        .dark select.request-field-input {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%23d1d5db' stroke-width='1.8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m5 7.5 5 5 5-5'/%3E%3C/svg%3E") !important;
        }

        .dark .request-field-input:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.25);
        }

        .dark .request-field-input option {
            background: #1f2937;
            color: #f9fafb;
        }

        .request-scanner-box {
            min-height: 25rem;
            overflow: hidden;
            border: 2px dashed var(--request-indigo);
            border-radius: 0.75rem;
            background: #f8f8ff;
        }

        .dark .request-scanner-box {
            background: #111827;
        }

        .request-scanner-box.qr-success {
            border-style: solid;
            border-color: #22c55e;
            background: #dcfce7;
        }

        .dark .request-scanner-box.qr-success {
            border-color: #4ade80;
            background: #14532d;
        }

        .request-scanner-box.qr-success .request-secondary-button {
            border-color: #16a34a;
            color: #15803d;
        }

        .request-scanner-box.qr-success .request-secondary-button:hover {
            background: #bbf7d0;
        }

        .dark .request-scanner-box.qr-success .request-secondary-button {
            border-color: #4ade80;
            color: #bbf7d0;
        }

        .dark .request-scanner-box.qr-success .request-secondary-button:hover {
            background: rgba(74, 222, 128, 0.18);
        }

        .request-scanner-box.qr-error {
            border-style: solid;
            border-color: #ef4444;
            background: #fef2f2;
        }

        .dark .request-scanner-box.qr-error {
            border-color: #f87171;
            background: #450a0a;
        }

        .request-scanner-box.qr-error .request-secondary-button {
            border-color: #dc2626;
            color: #b91c1c;
        }

        .request-scanner-box.qr-error .request-secondary-button:hover {
            background: #fecaca;
        }

        .dark .request-scanner-box.qr-error .request-secondary-button {
            border-color: #f87171;
            color: #fecaca;
        }

        .dark .request-scanner-box.qr-error .request-secondary-button:hover {
            background: rgba(248, 113, 113, 0.18);
        }

        .request-primary-button,
        .request-secondary-button,
        .request-clear-button {
            display: inline-flex;
            min-height: 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease, opacity 150ms ease;
        }

        .request-primary-button {
            border: 1px solid var(--request-indigo);
            background: var(--request-indigo);
            color: #ffffff;
        }

        .request-primary-button:hover {
            background: #4f46e5;
        }

        .request-secondary-button {
            border: 1px solid var(--request-indigo);
            background: transparent;
            color: var(--request-indigo);
        }

        .request-secondary-button:hover {
            background: rgba(99, 102, 241, 0.1);
        }

        .request-stop-scanner-button {
            border-color: #dc2626;
            background: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(127, 29, 29, 0.35);
        }

        .request-stop-scanner-button:hover {
            border-color: #b91c1c;
            background: #b91c1c;
        }

        .dark .request-secondary-button {
            color: #a5b4fc;
        }

        .dark .request-stop-scanner-button {
            border-color: #f87171;
            background: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.45);
        }

        .dark .request-stop-scanner-button:hover {
            border-color: #fca5a5;
            background: #b91c1c;
        }

        .request-clear-button {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
        }

        .request-clear-button:hover {
            background: #f9fafb;
        }

        .dark .request-clear-button {
            border-color: #4b5563;
            background: #1f2937;
            color: #e5e7eb;
        }

        .dark .request-clear-button:hover {
            background: #374151;
        }

        .request-error {
            margin-top: 0.35rem;
            color: #dc2626;
            font-size: 0.875rem;
        }

        .request-primary-button:disabled,
        .request-secondary-button:disabled,
        .request-clear-button:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }
    </style>

    <script src="https://unpkg.com/@zxing/browser@0.2.0"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('requestQrScanner', () => ({
                reader: null,
                controls: null,
                scanning: false,
                error: '',
                qrVerified: false,
                qrMessage: '',

                getReader() {
                    if (!window.ZXingBrowser?.BrowserQRCodeReader) {
                        throw new Error('The QR decoder could not be loaded.');
                    }

                    this.reader ??= new window.ZXingBrowser.BrowserQRCodeReader();

                    return this.reader;
                },

                async resolveQrValue(value) {
                    const payload = value?.trim();

                    this.error = '';
                    this.qrVerified = false;
                    this.qrMessage = '';

                    if (!payload) {
                        this.qrMessage = 'No QR code was found.';
                        return;
                    }

                    try {
                        const resolved = await this.$wire.resolveQr(payload);

                        if (!resolved) {
                            this.qrMessage = 'Invalid QR code. It does not match an available document. Please try again.';
                            return;
                        }

                        this.qrVerified = true;
                    } catch (exception) {
                        this.qrMessage = 'The QR code could not be verified. Please try again.';
                    }
                },

                async handleQrPhoto(event) {
                    const file = event.target.files?.[0];

                    if (file) {
                        await this.decodeQrImage(file);
                    }

                    event.target.value = '';
                },

                async decodeQrImage(file) {
                    this.error = '';
                    this.qrVerified = false;
                    this.qrMessage = '';

                    if (!file) {
                        return;
                    }

                    let objectUrl = null;

                    try {
                        const reader = this.getReader();
                        objectUrl = URL.createObjectURL(file);

                        const image = await new Promise((resolve, reject) => {
                            const element = new Image();

                            element.onload = () => resolve(element);
                            element.onerror = () => reject(new Error('The QR photo could not be read.'));
                            element.src = objectUrl;
                        });

                        const result = await reader.decodeFromImageElement(image);
                        await this.resolveQrValue(result.getText());
                    } catch (exception) {
                        this.error = exception?.message === 'The QR decoder could not be loaded.'
                            ? 'The QR decoder could not be loaded. Refresh the page and try again.'
                            : 'No QR code was found in that photo. Try a clearer image.';
                    } finally {
                        if (objectUrl) {
                            URL.revokeObjectURL(objectUrl);
                        }
                    }
                },

                async handlePaste(event) {
                    const items = Array.from(event.clipboardData?.items ?? []);
                    const imageItem = items.find((item) => item.type.startsWith('image/'));

                    if (imageItem) {
                        event.preventDefault();
                        await this.decodeQrImage(imageItem.getAsFile());
                        return;
                    }

                    const text = event.clipboardData?.getData('text')?.trim();

                    if (text && (
                        /^\d+$/.test(text)
                        || /LAO-/i.test(text)
                        || /document-status|client\/document-(preview|download)/i.test(text)
                    )) {
                        event.preventDefault();
                        await this.resolveQrValue(text);
                    }
                },

                async startScanner() {
                    this.error = '';
                    this.qrMessage = '';
                    this.qrVerified = false;

                    if (!navigator.mediaDevices?.getUserMedia) {
                        this.error = 'Camera access is unavailable. Open this page on localhost or HTTPS, then try again.';
                        return;
                    }

                    try {
                        const reader = this.getReader();
                        this.scanning = true;

                        this.controls = await reader.decodeFromConstraints({
                            video: {
                                facingMode: { ideal: 'environment' },
                            },
                            audio: false,
                        }, this.$refs.video, (result, error, controls) => {
                            if (controls && !this.controls) {
                                this.controls = controls;
                            }

                            if (!result || !this.scanning) {
                                return;
                            }

                            this.stopScanner();
                            this.resolveQrValue(result.getText());
                        });
                    } catch (exception) {
                        this.stopScanner();
                        this.error = exception?.name === 'NotAllowedError'
                            ? 'Camera permission was denied. Allow camera access and try again.'
                            : 'Unable to start the camera. Enter the LAO number instead.';
                    }
                },

                resetQrState() {
                    this.stopScanner();
                    this.error = '';
                    this.qrVerified = false;
                    this.qrMessage = '';

                    const photoInput = document.getElementById('request-qr-photo');

                    if (photoInput) {
                        photoInput.value = '';
                    }
                },

                stopScanner() {
                    this.scanning = false;

                    this.controls?.stop?.();
                    this.controls = null;

                    if (this.$refs.video) {
                        this.$refs.video.srcObject = null;
                    }
                },

                destroy() {
                    this.stopScanner();
                },
            }));
        });
    </script>
</x-filament-panels::page>
