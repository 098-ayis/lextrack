<template>
    <main
        class="flex min-h-[80vh] w-full flex-col items-center bg-[#f4f5f7] px-4 pb-16 pt-28 sm:px-6 md:pt-36"
        @paste="handlePaste"
    >
        <div
            v-if="qrCaptchaOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="qr-captcha-title"
        >
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
                <button
                    type="button"
                    class="absolute right-4 top-4 rounded-full p-1 text-2xl leading-none text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    aria-label="Close CAPTCHA"
                    @click="cancelQrPhotoUpload"
                >
                    <span aria-hidden="true">&times;</span>
                </button>

                <h2 id="qr-captcha-title" class="text-lg font-bold text-[#174f78]">
                    Verify before uploading
                </h2>

                <p class="mt-2 text-sm text-gray-500">
                    Security verification runs automatically before the file picker opens.
                </p>

                <div ref="turnstileContainer" class="mt-5 flex justify-center"></div>

                <p
                    v-if="turnstileError"
                    class="mt-4 text-sm font-semibold text-red-700"
                >
                    {{ turnstileError }}
                </p>

            </div>
        </div>

        <div
            class="mb-6 w-full max-w-6xl overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-[0_10px_40px_rgba(0,0,0,0.08)]"
        >
            <div class="bg-[#121722] px-6 py-5 md:px-8 md:py-6">
                <h2 class="text-lg font-bold text-[#828cff] md:text-xl">
                    Track your Document
                </h2>

                <p class="mt-1 text-sm text-gray-300">
                    Scan the QR code or upload a QR photo to track your request.
                </p>
            </div>

            <div class="grid gap-0 lg:grid-cols-2 lg:divide-x lg:divide-gray-200">
                <div class="p-6 md:p-8 lg:p-10">
                <section class="mt-0">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-[#174f78]">Scan QR</h3>

                        <button
                            type="button"
                            class="rounded-md border border-[#6366f1] px-3 py-1 text-xs font-semibold text-[#6366f1] transition hover:bg-[#6366f1] hover:text-white"
                            @click="clearQrTracking"
                        >
                            Clear QR
                        </button>
                    </div>

                    <div
                        class="mt-2 overflow-hidden rounded-2xl border-2 border-dashed border-[#6b77ff] bg-[#f7f7ff]"
                        :class="{
                            'border-green-300 bg-green-50': qrVerified,
                            'border-red-300 bg-red-50': qrMessage || scannerError,
                        }"
                    >
                        <div
                            v-if="!scanning"
                            class="flex min-h-[20rem] flex-col items-center justify-center px-6 text-center"
                        >
                            <div
                                v-if="!qrVerified && !qrMessage && !scannerError"
                                class="flex flex-col items-center"
                            >
                                <svg
                                    class="h-20 w-20 text-[#6366F1]"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4.5 8.25V5.5A1.5 1.5 0 0 1 6 4h2.75M15.25 4H18a1.5 1.5 0 0 1 1.5 1.5v2.75M19.5 15.75v2.75A1.5 1.5 0 0 1 18 20h-2.75M8.75 20H6a1.5 1.5 0 0 1-1.5-1.5v-2.75"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M7 7h3v3H7zM14 14h3v3h-3zM14 7h1.5v1.5H17M7 14v3h3v-1.5H8.5V14"
                                    />
                                </svg>

                                <p class="mt-4 text-sm font-medium text-gray-500">
                                    Scan a QR code or upload a QR photo
                                </p>
                            </div>

                            <div v-else class="flex flex-col items-center">
                                <svg
                                    v-if="qrVerified"
                                    class="h-14 w-14 text-green-600"
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
                                    v-else
                                    class="h-14 w-14 text-red-600"
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

                                <p v-if="qrVerified" class="mt-4 text-sm font-semibold text-green-700">
                                    QR code verified. Tracking number selected.
                                </p>

                                <p v-else class="mt-4 text-sm font-semibold text-red-700">
                                    {{ qrMessage || scannerError }}
                                </p>
                            </div>

                            <div class="mt-6 flex flex-wrap justify-center gap-3">
                                <button
                                    type="button"
                                    class="flex h-10 items-center justify-center rounded-xl border px-4 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="qrActionColorClasses"
                                    :disabled="qrLoading"
                                    @click="startScanner"
                                >
                                    Scan with camera
                                </button>

                                <label
                                    class="flex h-10 cursor-pointer items-center justify-center rounded-xl border px-4 text-sm font-semibold transition"
                                    :class="qrActionColorClasses"
                                    @click.prevent="openQrPhotoUpload"
                                >
                                    Upload QR photo
                                </label>

                                <input
                                    id="public-qr-photo"
                                    ref="qrFileInput"
                                    type="file"
                                    accept="image/*"
                                    class="sr-only"
                                    @change="handleQrPhoto"
                                />
                            </div>

                        </div>

                        <div
                            v-else
                            class="relative flex min-h-[20rem] flex-col items-center justify-center p-4"
                        >
                            <video
                                ref="videoElement"
                                autoplay
                                muted
                                playsinline
                                class="max-h-[18rem] w-full rounded-xl bg-black object-cover"
                            ></video>

                            <div class="pointer-events-none absolute left-1/2 top-1/2 h-48 w-48 -translate-x-1/2 -translate-y-1/2 rounded-xl border-4 border-[#6366F1] shadow-[0_0_0_9999px_rgba(15,23,42,0.35)]"></div>

                            <button
                                type="button"
                                class="absolute bottom-6 left-1/2 z-10 -translate-x-1/2 rounded-xl border border-white bg-white px-4 py-2 text-sm font-semibold text-[#6366F1] shadow transition hover:bg-gray-100"
                                @click="stopScanner"
                            >
                                Stop scanner
                            </button>
                        </div>
                    </div>

                    <p v-if="qrLoading" class="mt-2 text-sm text-[#6366F1]">
                        Checking the QR code...
                    </p>
                </section>
            </div>

                <section class="relative min-h-[24rem] border-t border-gray-200 p-6 md:p-8 lg:border-t-0 lg:p-10">
                    <div
                        v-if="!hasSearched || !document"
                        class="absolute inset-0 flex flex-col items-center justify-center text-center"
                    >
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                            <svg
                                class="h-7 w-7"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" d="m8 8 8 8M16 8l-8 8" />
                            </svg>
                        </div>

                        <p class="mt-5 text-sm font-semibold text-gray-400">
                            No document found
                        </p>
                    </div>

                    <div v-else class="w-full max-w-[32rem]">
                        <div
                            v-if="document.timeline?.length"
                            class="pt-1"
                        >
                            <h3 class="text-base font-bold text-gray-900">
                                Status timeline
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                Follow the latest updates from the Legal Affairs Office.
                            </p>

                            <div class="mt-6">
                                <div
                                    v-for="(update, index) in document.timeline"
                                    :key="`${update.date}-${update.time}-${update.title}-${index}`"
                                    class="relative flex gap-3 pb-7 last:pb-0"
                                >
                                    <div class="w-24 shrink-0 pt-0.5 text-right">
                                        <time class="block whitespace-nowrap text-xs font-bold leading-5 text-gray-700">
                                            {{ update.date }}
                                        </time>
                                        <span class="block text-[10px] font-medium leading-4 text-gray-400">
                                            {{ update.time }}
                                        </span>
                                    </div>

                                    <div class="relative flex w-6 shrink-0 justify-center">
                                        <span
                                            v-if="index < document.timeline.length - 1"
                                            class="absolute left-1/2 top-6 h-full w-px -translate-x-1/2 bg-gray-300"
                                            aria-hidden="true"
                                        ></span>

                                        <span
                                            class="relative z-10 inline-flex h-6 w-6 items-center justify-center rounded-full shadow-sm ring-4 ring-white"
                                            :class="timelineDotClass(update.status, index === document.timeline.length - 1)"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2.5"
                                                class="h-3.5 w-3.5"
                                                aria-hidden="true"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                            </svg>
                                        </span>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-base font-bold leading-6 text-gray-900">
                                            {{ update.title }}
                                        </h4>
                                        <p class="mt-1 text-sm leading-6 text-gray-500">
                                            {{ update.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

    </main>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

const hasSearched = ref(false)
const document = ref(null)

const qrActionState = computed(() => {
    if (qrMessage.value || scannerError.value) {
        return 'error'
    }

    if (qrVerified.value) {
        return 'success'
    }

    return 'idle'
})

const qrActionColorClasses = computed(() => ({
    idle: 'border-[#6366f1] text-[#6366f1] hover:bg-[#6366f1] hover:text-white',
    success: 'border-green-600 text-green-600 hover:bg-green-600 hover:text-white',
    error: 'border-red-600 text-red-600 hover:bg-red-600 hover:text-white',
}[qrActionState.value]))

const honeypot = ref(null)
const honeypotName = ref('')
const honeypotValidFrom = ref('')

const scanning = ref(false)
const qrLoading = ref(false)
const qrVerified = ref(false)
const qrMessage = ref('')
const scannerError = ref('')
const videoElement = ref(null)
const qrFileInput = ref(null)
const turnstileContainer = ref(null)
const turnstileResponse = ref('')
const qrCaptchaOpen = ref(false)
const turnstileError = ref('')

let qrReader = null
let qrControls = null
let qrDecoderPromise = null
let qrRequestController = null
let qrRequestToken = 0
let turnstileWidgetId = null
let turnstileLoadPromise = null


const ensureTurnstile = async () => {
    if (window.turnstile?.render) {
        return
    }

    if (!turnstileLoadPromise) {
        turnstileLoadPromise = new Promise((resolve, reject) => {
            const existingScript = window.document.querySelector(
                'script[data-lextrack-turnstile]'
            )

            if (existingScript) {
                existingScript.addEventListener('load', resolve, { once: true })
                existingScript.addEventListener('error', reject, { once: true })
                return
            }

            const script = window.document.createElement('script')

            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'
            script.async = true
            script.defer = true
            script.dataset.lextrackTurnstile = 'true'
            script.onload = resolve
            script.onerror = reject

            window.document.head.appendChild(script)
        })
    }

    await turnstileLoadPromise

    if (!window.turnstile?.render) {
        throw new Error('The CAPTCHA could not be loaded.')
    }
}


const renderTurnstile = async () => {
    if (!window.LexTrack?.turnstileSiteKey || !qrCaptchaOpen.value || !turnstileContainer.value) {
        return
    }

    try {
        await ensureTurnstile()

        await nextTick()

        if (!turnstileContainer.value) {
            return
        }

        turnstileWidgetId = window.turnstile.render(turnstileContainer.value, {
            sitekey: window.LexTrack.turnstileSiteKey,
            execution: 'render',
            appearance: 'interaction-only',
            retry: 'auto',
            'refresh-expired': 'auto',
            callback: (token) => {
                turnstileResponse.value = token
                turnstileError.value = ''
                nextTick(openQrPhotoPicker)
            },
            'expired-callback': () => {
                turnstileResponse.value = ''
                turnstileError.value = 'The CAPTCHA expired. Please complete it again.'
            },
            'timeout-callback': () => {
                turnstileResponse.value = ''
                turnstileError.value = 'The CAPTCHA timed out. Please complete it again.'
            },
            'error-callback': () => {
                turnstileResponse.value = ''
                turnstileError.value = 'The CAPTCHA could not be verified. Please try again.'
            },
        })
    } catch (error) {
        console.error('Turnstile error:', error)
        turnstileError.value = 'The CAPTCHA could not be loaded. Please try again.'
    }
}


const openQrPhotoUpload = () => {
    if (turnstileResponse.value) {
        resetTurnstile()
    }

    qrMessage.value = ''
    turnstileError.value = ''
    qrCaptchaOpen.value = true

    nextTick(renderTurnstile)
}


const openQrPhotoPicker = () => {
    if (!turnstileResponse.value || !qrFileInput.value) {
        turnstileError.value = 'Please complete the CAPTCHA verification before choosing a QR photo.'

        return
    }

    const fileInput = qrFileInput.value

    // Keep the verified token for the upload request, but close the modal
    // before opening the native file picker.
    qrCaptchaOpen.value = false
    turnstileWidgetId = null

    try {
        fileInput.click()
    } catch (error) {
        console.error('QR photo picker error:', error)
        qrCaptchaOpen.value = true
        turnstileError.value = 'CAPTCHA verified, but the file picker could not be opened. Please close this message and try again.'
    }
}


const cancelQrPhotoUpload = () => {
    resetTurnstile()
}


const resetTurnstile = () => {
    turnstileResponse.value = ''
    turnstileError.value = ''

    if (turnstileWidgetId !== null && window.turnstile?.reset) {
        window.turnstile.reset(turnstileWidgetId)
    }

    turnstileWidgetId = null
    qrCaptchaOpen.value = false
}


const ensureQrDecoder = async () => {
    if (window.ZXingBrowser?.BrowserQRCodeReader) {
        return
    }

    if (!qrDecoderPromise) {
        qrDecoderPromise = new Promise((resolve, reject) => {
            const existingScript = window.document.querySelector(
                'script[data-public-qr-decoder]'
            )

            if (existingScript) {
                existingScript.addEventListener('load', resolve, { once: true })
                existingScript.addEventListener('error', reject, { once: true })
                return
            }

            const script = window.document.createElement('script')

            script.src = 'https://unpkg.com/@zxing/browser@0.2.0'
            script.async = true
            script.dataset.publicQrDecoder = 'true'
            script.onload = resolve
            script.onerror = reject

            window.document.head.appendChild(script)
        })
    }

    await qrDecoderPromise

    if (!window.ZXingBrowser?.BrowserQRCodeReader) {
        throw new Error('The QR decoder could not be loaded.')
    }
}


const getQrReader = async () => {
    await ensureQrDecoder()

    qrReader ??= new window.ZXingBrowser.BrowserQRCodeReader()

    return qrReader
}


const honeypotPayload = () => {
    const payload = {}

    if (honeypot.value?.enabled) {
        payload[honeypot.value.nameFieldName] = honeypotName.value
        payload[honeypot.value.validFromFieldName] = honeypotValidFrom.value
    }

    return payload
}


const csrfHeaders = () => ({
    'X-CSRF-TOKEN': window.document.querySelector(
        'meta[name="csrf-token"]'
    )?.content ?? '',
})


const stopScanner = () => {
    scanning.value = false

    qrControls?.stop?.()
    qrControls = null

    const stream = videoElement.value?.srcObject

    if (stream) {
        stream.getTracks?.().forEach((track) => track.stop())
    }

    if (videoElement.value) {
        videoElement.value.srcObject = null
    }
}


const clearQrState = () => {
    qrVerified.value = false
    qrMessage.value = ''
    scannerError.value = ''
}


const clearQrTracking = () => {
    qrRequestToken += 1
    qrRequestController?.abort()
    qrRequestController = null

    stopScanner()
    qrLoading.value = false
    document.value = null
    hasSearched.value = false
    clearQrState()
    resetTurnstile()

    if (qrFileInput.value) {
        qrFileInput.value.value = ''
    }
}


const qrTokenPattern = /^LEXTRACK-QR-1\.[A-Za-z0-9_-]+$/


const isRecognizedQrPayload = (payload) => {
    if (qrTokenPattern.test(payload)) {
        return true
    }

    try {
        const url = new URL(payload)

        return /^\/document-status\/[1-9][0-9]*$/.test(url.pathname)
            && url.searchParams.has('signature')
    } catch {
        return false
    }
}


const resolveQrValue = async (value, fromImage = false) => {
    const qrToken = value?.trim()

    clearQrState()

    if (!qrToken) {
        qrMessage.value = 'No QR code was found.'
        hasSearched.value = true
        document.value = null

        return
    }

    if (!isRecognizedQrPayload(qrToken)) {
        qrMessage.value = 'Invalid QR code. Please scan a LexTrack document QR code.'
        hasSearched.value = true
        document.value = null

        return
    }

    if (fromImage && !turnstileResponse.value) {
        qrMessage.value = 'Please complete the CAPTCHA verification before uploading the QR code.'
        hasSearched.value = true
        document.value = null

        return
    }

    qrLoading.value = true
    hasSearched.value = false
    document.value = null

    const requestToken = ++qrRequestToken
    const controller = new AbortController()
    qrRequestController = controller
    const timeoutId = window.setTimeout(() => controller.abort(), 5000)

    try {
        const response = await fetch('/api/track/qr', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...csrfHeaders(),
            },
            signal: controller.signal,
            body: JSON.stringify({
                qr_token: qrToken,
                ...(fromImage ? {
                    qr_source: 'image',
                    'cf-turnstile-response': turnstileResponse.value,
                } : {
                    qr_source: 'camera',
                }),
                ...honeypotPayload(),
            }),
        })

        if (response.status === 404) {
            qrMessage.value = 'Invalid QR code. Please scan a LexTrack document QR code.'

            return
        }

        if (response.status === 429) {
            qrMessage.value = 'Too many scan attempts. Please wait a moment and try again.'

            return
        }

        if (fromImage && response.status === 422) {
            const data = await response.json().catch(() => ({}))

            if (data.errors?.['cf-turnstile-response']) {
                qrMessage.value = 'Please complete the CAPTCHA verification before uploading the QR code.'

                return
            }
        }

        if (!response.ok) {
            qrMessage.value = 'The QR code could not be verified. Please try again.'

            return
        }

        const data = await response.json()

        document.value = data.document ?? null

        if (!document.value) {
            qrMessage.value = 'Invalid QR code. Please scan a LexTrack document QR code.'

            return
        }

        qrVerified.value = true
        stopScanner()
    } catch (error) {
        if (requestToken !== qrRequestToken) {
            return
        }

        qrMessage.value = error?.name === 'AbortError'
            ? 'The QR code check timed out. Please try again.'
            : 'The QR code could not be verified. Please try again.'
    } finally {
        if (requestToken !== qrRequestToken) {
            return
        }

        window.clearTimeout(timeoutId)
        qrRequestController = null
        qrLoading.value = false
        hasSearched.value = true
    }
}


const handleQrPhoto = async (event) => {
    const file = event.target.files?.[0]

    if (file) {
        await decodeQrImage(file)
    }

    event.target.value = ''
}


const decodeQrImage = async (file) => {
    clearQrState()

    if (!file) {
        return
    }

    let objectUrl = null

    try {
        const reader = await getQrReader()

        objectUrl = URL.createObjectURL(file)

        const image = await new Promise((resolve, reject) => {
            const element = new Image()

            element.onload = () => resolve(element)
            element.onerror = () => reject(new Error('The QR photo could not be read.'))
            element.src = objectUrl
        })

        const result = await reader.decodeFromImageElement(image)

        await resolveQrValue(result.getText(), true)
    } catch (error) {
        scannerError.value = error?.message === 'The QR decoder could not be loaded.'
            ? 'The QR decoder could not be loaded. Refresh the page and try again.'
            : 'No QR code was found in that photo. Try a clearer image.'
    } finally {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl)
        }

        resetTurnstile()
    }
}


const handlePaste = async (event) => {
    const items = Array.from(event.clipboardData?.items ?? [])
    const imageItem = items.find((item) => item.type.startsWith('image/'))

    if (imageItem) {
        event.preventDefault()
        await decodeQrImage(imageItem.getAsFile())
    }
}


const startScanner = async () => {
    clearQrState()

    if (!navigator.mediaDevices?.getUserMedia) {
        scannerError.value = 'Camera access is unavailable. Open this page on localhost or HTTPS, then try again.'

        return
    }

    try {
        const reader = await getQrReader()

        scanning.value = true
        await nextTick()

        qrControls = await reader.decodeFromConstraints(
            {
                video: {
                    facingMode: { ideal: 'environment' },
                },
                audio: false,
            },
            videoElement.value,
            (result, error, controls) => {
                if (controls && !qrControls) {
                    qrControls = controls
                }

                if (!result || !scanning.value) {
                    return
                }

                stopScanner()
                resolveQrValue(result.getText())
            },
        )
    } catch (error) {
        stopScanner()

        scannerError.value = error?.name === 'NotAllowedError'
            ? 'Camera permission was denied. Allow camera access and try again.'
            : 'Unable to start the camera. Try uploading a QR photo instead.'
    }
}


/*
|--------------------------------------------------------------------------
| Load Honeypot
|--------------------------------------------------------------------------
*/

const loadHoneypot = async () => {
    try {
        const response = await fetch('/api/honeypot', {
            headers: {
                Accept: 'application/json',
            },
        })

        if (!response.ok) {
            console.error('Unable to load honeypot.')
            return
        }

        const data = await response.json()

        honeypot.value = data

        honeypotName.value = ''

        honeypotValidFrom.value =
            data.encryptedValidFrom ?? ''

    } catch (error) {
        console.error('Honeypot error:', error)
    }
}


onMounted(() => {
    loadHoneypot()
})
onBeforeUnmount(stopScanner)


/*
|--------------------------------------------------------------------------
| Format Status
|--------------------------------------------------------------------------
*/

const formatStatus = (status) => {

    const labels = {

        pending: 'Pending',

        in_progress: 'In Progress',

        completed: 'Completed',

        rejected: 'Rejected',

        outgoing: 'Outgoing',

        returned: 'Returned',

        archived: 'Archived',
    }

    return labels[status]
        ?? String(status ?? '')
            .replaceAll('_', ' ')
            .replace(
                /\b\w/g,
                character =>
                    character.toUpperCase()
            )
}


/*
|--------------------------------------------------------------------------
| Status Colors
|--------------------------------------------------------------------------
*/

const statusClass = (status) => {

    const classes = {

        pending:
            'border-yellow-400 bg-yellow-100 text-yellow-800',

        in_progress:
            'border-blue-400 bg-blue-100 text-blue-800',

        completed:
            'border-green-500 bg-green-100 text-green-800',

        rejected:
            'border-red-400 bg-red-100 text-red-800',

        outgoing:
            'border-purple-400 bg-purple-100 text-purple-800',

        returned:
            'border-orange-400 bg-orange-100 text-orange-800',

        archived:
            'border-gray-400 bg-gray-100 text-gray-700',
    }

    return classes[status]
        ?? 'border-gray-300 bg-gray-100 text-gray-700'
}

const timelineDotClass = (status, isLatest = false) => {
    return isLatest
        ? 'bg-green-700 text-white'
        : 'bg-emerald-100 text-emerald-600'
}
</script>

<style scoped>
#public-tracking-number:-webkit-autofill,
#public-tracking-number:-webkit-autofill:hover,
#public-tracking-number:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
    -webkit-text-fill-color: #334155 !important;
    caret-color: #334155;
}
</style>
