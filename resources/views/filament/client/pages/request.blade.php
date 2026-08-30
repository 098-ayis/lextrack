<x-filament-panels::page>
    <div
        class="client-request-page w-full overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        x-data="requestQrScanner"
        x-on:beforeunload.window="stopScanner()"
    >
        <div class="request-page-header px-6 py-6">
            <h1 class="text-2xl font-semibold tracking-tight text-[#6366F1]">
                Request A Document
            </h1>
            <p class="mt-1 text-sm text-gray-100">
                Please provide the details of your request and upload the necessary files.
            </p>
        </div>
        <form wire:submit="submit" class="p-6">
            <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
                <div class="space-y-7">
                    <div>
                        <label for="request-document-name" class="request-field-label">
                            Document Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="request-document-name"
                            type="text"
                            wire:model="documentName"
                            class="request-field-input"
                        >
                        @error('documentName')
                            <p class="request-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="request-lao-number" class="request-field-label">
                            LAO Number <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="request-lao-number"
                            type="text"
                            wire:model.blur="laoNumber"
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
                        <textarea
                            id="request-purpose"
                            wire:model="purpose"
                            rows="6"
                            class="request-field-input resize-y"
                        ></textarea>
                        @error('purpose')
                            <p class="request-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="request-field-label">
                        Scan QR
                    </label>

                    <div class="request-scanner-box">
                        <div x-show="!scanning" class="flex min-h-[25rem] flex-col items-center justify-center px-6 text-center">
                            <svg class="h-20 w-20 text-[#6366F1]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 8.25V5.5A1.5 1.5 0 0 1 6 4h2.75M15.25 4H18a1.5 1.5 0 0 1 1.5 1.5v2.75M19.5 15.75v2.75A1.5 1.5 0 0 1 18 20h-2.75M8.75 20H6a1.5 1.5 0 0 1-1.5-1.5v-2.75" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h3v3H7zM14 14h3v3h-3zM14 7h1.5v1.5H17M7 14v3h3v-1.5H8.5V14" />
                            </svg>
                            <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-300">
                                Scan a QR code or attach a supporting file
                            </p>

                            <div class="mt-6 flex flex-wrap justify-center gap-3">
                                <button
                                    type="button"
                                    x-on:click="startScanner()"
                                    class="request-secondary-button"
                                >
                                    Scan with camera
                                </button>

                                <label for="request-attachment" class="request-primary-button cursor-pointer">
                                    Browse
                                </label>
                                <input
                                    id="request-attachment"
                                    type="file"
                                    wire:model="attachment"
                                    accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
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
                                class="request-secondary-button absolute bottom-6 left-1/2 -translate-x-1/2"
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

                    <p x-cloak x-show="error" x-text="error" class="mt-2 text-sm text-red-500"></p>
                    @if ($qrValue)
                        <p class="mt-2 truncate text-sm text-gray-500 dark:text-gray-400">
                            QR value: {{ $qrValue }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="mt-10 flex justify-end gap-3 sm:gap-4">
                <button
                    type="button"
                    wire:click="clearForm"
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

        .dark .request-field-input {
            border-color: #4b5563;
            background: #1f2937;
            color: #f9fafb;
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

        .dark .request-secondary-button {
            color: #a5b4fc;
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

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('requestQrScanner', () => ({
                detector: null,
                stream: null,
                scanning: false,
                error: '',
                animationFrame: null,

                async startScanner() {
                    this.error = '';

                    if (!('BarcodeDetector' in window)) {
                        this.error = 'QR scanning is not supported by this browser. Select an LAO number from the list instead.';
                        return;
                    }

                    if (!navigator.mediaDevices?.getUserMedia) {
                        this.error = 'Camera access is unavailable. Open this page on localhost or HTTPS, then try again.';
                        return;
                    }

                    try {
                        this.detector = new BarcodeDetector({ formats: ['qr_code'] });
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { ideal: 'environment' } },
                            audio: false,
                        });
                        this.$refs.video.srcObject = this.stream;
                        await this.$refs.video.play();
                        this.scanning = true;
                        this.scanFrame();
                    } catch (exception) {
                        this.stopScanner();
                        this.error = exception?.name === 'NotAllowedError'
                            ? 'Camera permission was denied. Allow camera access and try again.'
                            : 'Unable to start the camera. You can select an LAO number from the list instead.';
                    }
                },

                async scanFrame() {
                    if (!this.scanning || !this.detector || !this.$refs.video) {
                        return;
                    }

                    try {
                        const codes = await this.detector.detect(this.$refs.video);
                        const value = codes.find((code) => code.rawValue)?.rawValue;

                        if (value) {
                            this.stopScanner();
                            this.$wire.resolveQr(value);
                            return;
                        }
                    } catch (exception) {
                        // Keep scanning while the camera is still initializing.
                    }

                    this.animationFrame = requestAnimationFrame(() => this.scanFrame());
                },

                stopScanner() {
                    this.scanning = false;

                    if (this.animationFrame) {
                        cancelAnimationFrame(this.animationFrame);
                        this.animationFrame = null;
                    }

                    this.stream?.getTracks().forEach((track) => track.stop());
                    this.stream = null;

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
