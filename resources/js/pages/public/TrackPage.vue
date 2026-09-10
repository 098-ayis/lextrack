<template>
    <main
        class="flex min-h-[80vh] w-full flex-col items-center bg-[#f4f5f7] px-4 pb-16 pt-28 sm:px-6 md:pt-36"
        @paste="handlePaste"
    >
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
                                    for="public-qr-photo"
                                    class="flex h-10 cursor-pointer items-center justify-center rounded-xl border px-4 text-sm font-semibold transition"
                                    :class="qrActionColorClasses"
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
                        <div class="grid grid-cols-1 gap-x-10 gap-y-6 sm:grid-cols-2">
                            <div>
                                <p class="text-sm font-bold uppercase tracking-wide text-gray-500">LAO Number</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ document.tracking_number }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-bold uppercase tracking-wide text-gray-500">Document Type</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ document.document_type }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-bold uppercase tracking-wide text-gray-500">Particulars</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ document.particulars || 'Not specified' }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-bold uppercase tracking-wide text-gray-500">Status</p>
                                <span
                                    :class="statusClass(document.status)"
                                    class="mt-1 inline-flex rounded-full border px-3 py-1 text-sm font-bold"
                                >
                                    {{ formatStatus(document.status) }}
                                </span>
                            </div>

                            <div class="sm:col-span-2">
                                <p class="text-sm font-bold uppercase tracking-wide text-gray-500">Date Submitted</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ document.date_submitted }}</p>
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

let qrReader = null
let qrControls = null
let qrDecoderPromise = null
let qrRequestController = null
let qrRequestToken = 0


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

    if (qrFileInput.value) {
        qrFileInput.value.value = ''
    }
}


const qrTokenPattern = /^LEXTRACK-QR-1\.[A-Za-z0-9_-]+$/


const isRecognizedQrPayload = (payload) => qrTokenPattern.test(payload)


const resolveQrValue = async (value) => {
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

        await resolveQrValue(result.getText())
    } catch (error) {
        scannerError.value = error?.message === 'The QR decoder could not be loaded.'
            ? 'The QR decoder could not be loaded. Refresh the page and try again.'
            : 'No QR code was found in that photo. Try a clearer image.'
    } finally {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl)
        }
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


onMounted(loadHoneypot)
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
