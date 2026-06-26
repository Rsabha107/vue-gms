<script setup>
import { ref, nextTick } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
    guestId:   { type: Number, required: true },
    guestName: { type: String, required: true },
    eventName: { type: String, required: true },
    isSetup:   { type: Boolean, required: true },
    qrSvg:     { type: String, default: null },
    secret:    { type: String, default: null },
    portalUrl: { type: String, default: '' },
})

const form = useForm({ code: '' })
const digits = ref(['', '', '', '', '', ''])
const inputRefs = ref([])
const showSecret = ref(false)

function onDigitInput(i) {
    const val = digits.value[i].replace(/\D/g, '')
    digits.value[i] = val.slice(-1)
    if (val && i < 5) {
        nextTick(() => inputRefs.value[i + 1]?.focus())
    }
    form.code = digits.value.join('')
}

function onDigitKeydown(e, i) {
    if (e.key === 'Backspace' && !digits.value[i] && i > 0) {
        nextTick(() => inputRefs.value[i - 1]?.focus())
    }
}

function onPaste(e) {
    const text = (e.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6)
    text.split('').forEach((ch, i) => { digits.value[i] = ch })
    form.code = digits.value.join('')
    nextTick(() => inputRefs.value[Math.min(text.length, 5)]?.focus())
    e.preventDefault()
}

function submit() {
    form.post(route('portal.totp.verify', props.guestId), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head :title="`Verify — ${eventName}`" />

    <div class="totp-scrim">
        <div class="totp-card">
            <div class="totp-logo">🏆</div>
            <h1 class="totp-title">{{ eventName }}</h1>
            <p class="totp-sub">Secure Portal Access</p>

            <!-- First time: QR Setup -->
            <template v-if="!isSetup && qrSvg">
                <div class="totp-section">
                    <h2 class="totp-heading">Set up Google Authenticator</h2>
                    <p class="totp-text">Welcome, <strong>{{ guestName }}</strong>. Scan the QR code below with your Google Authenticator app to set up two-factor authentication for your portal.</p>
                </div>

                <div class="totp-qr" v-html="qrSvg" />

                <div class="totp-manual">
                    <button type="button" class="totp-manual-toggle" @click="showSecret = !showSecret">
                        {{ showSecret ? 'Hide' : 'Show' }} manual entry key
                    </button>
                    <div v-if="showSecret" class="totp-secret">{{ secret }}</div>
                </div>

                <div class="totp-section">
                    <p class="totp-text">After scanning, enter the 6-digit code from the app:</p>
                </div>
            </template>

            <!-- Returning: just verify -->
            <template v-else>
                <div class="totp-section">
                    <h2 class="totp-heading">Enter verification code</h2>
                    <p class="totp-text">Welcome back, <strong>{{ guestName }}</strong>. Open Google Authenticator and enter the 6-digit code for your event portal.</p>
                </div>
            </template>

            <!-- Code input -->
            <form @submit.prevent="submit">
                <div class="totp-digits" @paste="onPaste">
                    <input
                        v-for="(d, i) in 6" :key="i"
                        :ref="el => inputRefs[i] = el"
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        class="totp-digit"
                        :class="{ filled: digits[i] }"
                        v-model="digits[i]"
                        @input="onDigitInput(i)"
                        @keydown="onDigitKeydown($event, i)"
                        :autofocus="i === 0"
                    />
                </div>

                <p v-if="form.errors.code" class="totp-error">{{ form.errors.code }}</p>

                <button type="submit" class="totp-submit" :disabled="form.code.length !== 6 || form.processing">
                    {{ form.processing ? 'Verifying…' : 'Verify & Enter Portal' }}
                </button>
            </form>

            <p class="totp-help">
                Don't have Google Authenticator?
                <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</a> ·
                <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank">iOS</a>
            </p>
        </div>
    </div>
</template>

<style scoped>
.totp-scrim {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: #f5f0eb; padding: 20px;
    font-family: 'Hanken Grotesk', -apple-system, sans-serif;
}
.totp-card {
    background: #fff; border-radius: 16px; padding: 40px;
    max-width: 440px; width: 100%; text-align: center;
    box-shadow: 0 4px 24px rgba(0,0,0,.06);
}
.totp-logo { font-size: 40px; margin-bottom: 12px; }
.totp-title { font-family: 'Instrument Serif', Georgia, serif; font-size: 28px; margin: 0 0 4px; color: #1a1210; }
.totp-sub { font-size: 13px; color: #a09488; margin: 0 0 28px; letter-spacing: .3px; text-transform: uppercase; font-weight: 600; }

.totp-section { margin-bottom: 20px; }
.totp-heading { font-family: 'Instrument Serif', Georgia, serif; font-size: 20px; margin: 0 0 8px; color: #1a1210; }
.totp-text { font-size: 14px; color: #6b5c53; line-height: 1.6; margin: 0; }

.totp-qr { margin: 20px auto; display: flex; justify-content: center; }
.totp-qr :deep(svg) { width: 200px; height: 200px; border-radius: 12px; border: 1px solid rgba(0,0,0,.06); }

.totp-manual { margin-bottom: 20px; }
.totp-manual-toggle { background: none; border: none; cursor: pointer; font-size: 12px; font-weight: 600; color: #8a1f3d; }
.totp-manual-toggle:hover { text-decoration: underline; }
.totp-secret { margin-top: 8px; font-family: 'IBM Plex Mono', monospace; font-size: 14px; font-weight: 600; letter-spacing: 2px; color: #1a1210; padding: 10px; background: #f5f0eb; border-radius: 8px; user-select: all; }

.totp-digits { display: flex; gap: 8px; justify-content: center; margin: 20px 0; }
.totp-digit {
    width: 48px; height: 56px; border: 2px solid rgba(26,18,16,.12); border-radius: 10px;
    text-align: center; font-size: 24px; font-weight: 700; font-family: 'IBM Plex Mono', monospace;
    color: #1a1210; outline: none; transition: .15s; background: #fff;
}
.totp-digit:focus { border-color: #8a1f3d; box-shadow: 0 0 0 3px rgba(138,31,61,.1); }
.totp-digit.filled { border-color: #8a1f3d; background: rgba(138,31,61,.03); }

.totp-error { color: #dc2626; font-size: 13px; font-weight: 600; margin: -8px 0 12px; }

.totp-submit {
    width: 100%; padding: 14px; border: none; border-radius: 10px;
    background: #8a1f3d; color: #fff; font-size: 15px; font-weight: 600;
    cursor: pointer; transition: .15s; margin-bottom: 20px;
}
.totp-submit:hover:not(:disabled) { background: #6e1830; }
.totp-submit:disabled { opacity: .4; cursor: not-allowed; }

.totp-help { font-size: 12px; color: #a09488; margin: 0; }
.totp-help a { color: #8a1f3d; font-weight: 600; text-decoration: none; }
.totp-help a:hover { text-decoration: underline; }

@media (max-width: 480px) {
    .totp-card { padding: 28px 20px; }
    .totp-digit { width: 40px; height: 48px; font-size: 20px; }
}
</style>
