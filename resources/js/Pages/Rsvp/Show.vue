<script setup>
import { reactive, computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  token:      { type: String, required: true },
  event:      { type: Object, required: true },
  guest:      { type: Object, required: true },
  subject:    { type: String, default: '' },
  body:       { type: String, default: '' },
  matches:    { type: Array, required: true },
  companions: { type: Array, default: () => [] },
  submitted:  { type: Boolean, default: false },
})

const resp = reactive(Object.fromEntries(props.matches.map((m) => [m.id, m.response || null])))

const RELATIONS = ['Spouse', 'Family', 'Aide', 'Security', 'Delegate', 'Interpreter', 'Companion']

const form = useForm({
  responses: resp,
  passport_no: '',
  personal_photo: '',
  passport_front: '',
  photo_consent: false,
  companions: props.companions.map(c => ({
    name: c.name || '', relation: c.relation || 'Companion',
    passport_no: c.passport_no || '', personal_photo: c.personal_photo || '', passport_front: c.passport_front || '',
  })),
})

const set = (id, v) => (resp[id] = resp[id] === v ? null : v)
const yesCount = computed(() => props.matches.filter((m) => resp[m.id] === 'yes').length)
const decided = computed(() => props.matches.filter((m) => resp[m.id]).length)
const lastName = computed(() => props.guest.name.split(' ').slice(-1)[0])

// 3-step flow: 1=matches, 2=your details, 3=companions
const step = ref(1)
const STEPS = ['Matches', 'Your Details', 'Companions']

function goTo(s) { step.value = s; window.scrollTo({ top: 0, behavior: 'smooth' }) }

const guestDocsComplete = computed(() =>
  form.passport_no.trim() !== '' &&
  form.personal_photo !== '' &&
  form.passport_front !== '' &&
  form.photo_consent === true
)

const canSubmit = computed(() => guestDocsComplete.value && !form.processing)

// Generic file upload
const uploading = ref({})

async function upload(file, target, key) {
  const uid = target + key
  uploading.value[uid] = true
  const fd = new FormData()
  fd.append('file', file)
  try {
    const { data } = await axios.post('/rsvp/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    if (target === 'guest') form[key] = data.path
    else form.companions[target][key] = data.path
  } catch { console.error('Upload failed') }
  uploading.value[uid] = false
}

function isUploading(target, key) { return uploading.value[target + key] || false }

function addCompanion() {
  form.companions.push({ name: '', relation: 'Companion', passport_no: '', personal_photo: '', passport_front: '' })
}

function removeCompanion(i) { form.companions.splice(i, 1) }

function confirm() {
  form.responses = { ...resp }
  form.post(`/rsvp/${props.token}`, { preserveScroll: true })
}

function previewUrl(path) { return path ? `/gms/api/document/${path}` : '' }
</script>

<template>
  <Head :title="`RSVP — ${event.name}`" />

  <div class="ginv-scrim">
    <div class="ginv">
      <div class="ginv-head">
        <div class="crest">🏆</div>
        <div class="ev">{{ event.name }}</div>
        <div class="dt">{{ event.venue }} · {{ event.dates }}</div>
      </div>

      <!-- ═══ NOT YET SUBMITTED ═══ -->
      <div v-if="!submitted && !form.recentlySuccessful" class="ginv-body">

        <!-- Step indicator -->
        <div class="rsvp-steps">
          <button
            v-for="(s, i) in STEPS" :key="i"
            class="rsvp-step"
            :class="{ done: i + 1 < step, active: i + 1 === step, future: i + 1 > step }"
            @click="i + 1 < step ? goTo(i + 1) : null"
          >{{ i + 1 }} · {{ s }}</button>
          <div class="rsvp-step-line" />
        </div>

        <!-- ═══ STEP 1: Matches ═══ -->
        <template v-if="step === 1">
          <div class="ginv-greet">{{ body }}</div>
          <div class="ginv-sec-h">Select the matches you'll attend</div>
          <div class="ginv-sec-sub">Tap "Attending" for each match you can join.</div>

          <div
            v-for="m in matches" :key="m.id"
            class="rsvp-match" :class="{ yes: resp[m.id] === 'yes', no: resp[m.id] === 'no' }"
          >
            <div class="rsvp-top"><span class="mc-stage" :class="{ final: m.stage === 'Final' }">{{ m.stage }}</span></div>
            <div class="rsvp-teams">
              <span v-if="m.homeFlag" class="fl">{{ m.homeFlag }}</span> {{ m.homeTeam }}
              <span class="vs">v</span>
              <span v-if="m.awayFlag" class="fl">{{ m.awayFlag }}</span> {{ m.awayTeam }}
            </div>
            <div class="rsvp-when">{{ m.date }} · {{ m.time }} · {{ m.venue }}</div>
            <div class="rsvp-toggle">
              <button class="rsvp-btn" :class="{ 'on-yes': resp[m.id] === 'yes' }" @click="set(m.id, 'yes')">✓ Attending</button>
              <button class="rsvp-btn" :class="{ 'on-no': resp[m.id] === 'no' }" @click="set(m.id, 'no')">✕ Can't make it</button>
            </div>
          </div>

          <div class="ginv-bar">
            <div><b>{{ yesCount }}</b> <span class="muted">attending · {{ decided }}/{{ matches.length }} answered</span></div>
            <button class="btn pri" :disabled="yesCount === 0" @click="goTo(2)">Continue →</button>
          </div>
        </template>

        <!-- ═══ STEP 2: Your Details ═══ -->
        <template v-if="step === 2">
          <div class="ginv-sec-h">Your details, {{ lastName }}</div>
          <div class="ginv-sec-sub">We need the following to process your accreditation. All documents are stored securely.</div>

          <div class="rsvp-docs">
            <div class="rsvp-doc-field">
              <label class="rsvp-doc-label">Passport Number <span class="req">*</span></label>
              <input v-model="form.passport_no" type="text" class="rsvp-doc-input" placeholder="e.g. AB1234567" />
            </div>

            <div class="rsvp-doc-field">
              <label class="rsvp-doc-label">Personal Photo <span class="req">*</span></label>
              <div v-if="!form.personal_photo && !isUploading('guest','personal_photo')" class="rsvp-upload-zone" @click="$refs.guestPhoto.click()">
                <div class="rsvp-upload-icon">📷</div>
                <div class="rsvp-upload-text">Click to upload your photo</div>
                <div class="rsvp-upload-hint">JPG, PNG — max 5MB</div>
              </div>
              <div v-else-if="isUploading('guest','personal_photo')" class="rsvp-upload-zone rsvp-uploading"><div class="rsvp-upload-text">Uploading…</div></div>
              <div v-else class="rsvp-upload-done">
                <img :src="previewUrl(form.personal_photo)" class="rsvp-preview" />
                <button type="button" class="rsvp-replace" @click="$refs.guestPhoto.click()">Replace</button>
              </div>
              <input ref="guestPhoto" type="file" accept="image/jpeg,image/png" style="display:none;" @change="e => { if(e.target.files[0]) upload(e.target.files[0], 'guest', 'personal_photo'); e.target.value='' }" />
            </div>

            <div class="rsvp-doc-field">
              <label class="rsvp-doc-label">Passport Front Copy <span class="req">*</span></label>
              <div v-if="!form.passport_front && !isUploading('guest','passport_front')" class="rsvp-upload-zone" @click="$refs.guestPassport.click()">
                <div class="rsvp-upload-icon">🪪</div>
                <div class="rsvp-upload-text">Click to upload passport scan</div>
                <div class="rsvp-upload-hint">JPG, PNG or PDF — max 5MB</div>
              </div>
              <div v-else-if="isUploading('guest','passport_front')" class="rsvp-upload-zone rsvp-uploading"><div class="rsvp-upload-text">Uploading…</div></div>
              <div v-else class="rsvp-upload-done">
                <img v-if="form.passport_front.match(/\.(jpg|jpeg|png)$/i)" :src="previewUrl(form.passport_front)" class="rsvp-preview" />
                <div v-else class="rsvp-file-badge">📄 PDF uploaded</div>
                <button type="button" class="rsvp-replace" @click="$refs.guestPassport.click()">Replace</button>
              </div>
              <input ref="guestPassport" type="file" accept="image/jpeg,image/png,application/pdf" style="display:none;" @change="e => { if(e.target.files[0]) upload(e.target.files[0], 'guest', 'passport_front'); e.target.value='' }" />
            </div>

            <label class="rsvp-consent">
              <input type="checkbox" v-model="form.photo_consent" />
              <span>I consent to my photo and passport copy being stored and used for event accreditation and security purposes.</span>
            </label>
          </div>

          <div class="ginv-bar">
            <button class="btn ghost" @click="goTo(1)">← Back</button>
            <button class="btn pri" :disabled="!guestDocsComplete" @click="goTo(3)">Continue to companions →</button>
          </div>
        </template>

        <!-- ═══ STEP 3: Companions ═══ -->
        <template v-if="step === 3">
          <div class="ginv-sec-h">Travelling companions</div>
          <div class="ginv-sec-sub">Add anyone travelling under your invitation. You can skip this step if travelling alone.</div>

          <div class="rsvp-comp-list">
            <div v-for="(c, ci) in form.companions" :key="ci" class="rsvp-comp-card">
              <div class="rsvp-comp-head">
                <span class="rsvp-comp-num">{{ ci + 1 }}</span>
                <div style="flex:1;display:flex;gap:8px;">
                  <input v-model="c.name" type="text" class="rsvp-doc-input" placeholder="Full name" style="flex:1;" />
                  <select v-model="c.relation" class="rsvp-doc-input" style="width:140px;flex:none;">
                    <option v-for="r in RELATIONS" :key="r" :value="r">{{ r }}</option>
                  </select>
                </div>
                <button type="button" class="rsvp-comp-remove" @click="removeCompanion(ci)">✕</button>
              </div>

              <div class="rsvp-comp-docs">
                <div class="rsvp-doc-field">
                  <label class="rsvp-doc-label rsvp-doc-sm">Passport No.</label>
                  <input v-model="c.passport_no" type="text" class="rsvp-doc-input rsvp-input-sm" placeholder="AB1234567" />
                </div>
                <div class="rsvp-doc-field">
                  <label class="rsvp-doc-label rsvp-doc-sm">Personal Photo</label>
                  <div v-if="!c.personal_photo && !isUploading(ci,'personal_photo')" class="rsvp-upload-zone rsvp-zone-sm" @click="$refs['compPhoto' + ci]?.[0]?.click()">
                    <div class="rsvp-upload-text">Upload photo</div>
                  </div>
                  <div v-else-if="isUploading(ci,'personal_photo')" class="rsvp-upload-zone rsvp-zone-sm rsvp-uploading"><div class="rsvp-upload-text">Uploading…</div></div>
                  <div v-else class="rsvp-upload-done rsvp-done-sm">
                    <img :src="previewUrl(c.personal_photo)" class="rsvp-preview-sm" />
                    <button type="button" class="rsvp-replace" @click="$refs['compPhoto' + ci]?.[0]?.click()">Replace</button>
                  </div>
                  <input :ref="'compPhoto' + ci" type="file" accept="image/jpeg,image/png" style="display:none;" @change="e => { if(e.target.files[0]) upload(e.target.files[0], ci, 'personal_photo'); e.target.value='' }" />
                </div>
                <div class="rsvp-doc-field">
                  <label class="rsvp-doc-label rsvp-doc-sm">Passport Front</label>
                  <div v-if="!c.passport_front && !isUploading(ci,'passport_front')" class="rsvp-upload-zone rsvp-zone-sm" @click="$refs['compPass' + ci]?.[0]?.click()">
                    <div class="rsvp-upload-text">Upload passport</div>
                  </div>
                  <div v-else-if="isUploading(ci,'passport_front')" class="rsvp-upload-zone rsvp-zone-sm rsvp-uploading"><div class="rsvp-upload-text">Uploading…</div></div>
                  <div v-else class="rsvp-upload-done rsvp-done-sm">
                    <img v-if="c.passport_front.match(/\.(jpg|jpeg|png)$/i)" :src="previewUrl(c.passport_front)" class="rsvp-preview-sm" />
                    <div v-else class="rsvp-file-badge" style="font-size:11px;">📄 PDF</div>
                    <button type="button" class="rsvp-replace" @click="$refs['compPass' + ci]?.[0]?.click()">Replace</button>
                  </div>
                  <input :ref="'compPass' + ci" type="file" accept="image/jpeg,image/png,application/pdf" style="display:none;" @change="e => { if(e.target.files[0]) upload(e.target.files[0], ci, 'passport_front'); e.target.value='' }" />
                </div>
              </div>
            </div>
          </div>

          <button type="button" class="rsvp-add-comp" @click="addCompanion">+ Add companion</button>

          <div class="ginv-bar">
            <button class="btn ghost" @click="goTo(2)">← Back</button>
            <div style="display:flex;align-items:center;gap:12px;">
              <span class="muted" style="font-size:12px;">{{ yesCount }} match{{ yesCount !== 1 ? 'es' : '' }} · {{ form.companions.filter(c => c.name.trim()).length }} companion{{ form.companions.filter(c => c.name.trim()).length !== 1 ? 's' : '' }}</span>
              <button class="btn pri" :disabled="!canSubmit" @click="confirm">
                {{ form.processing ? 'Submitting…' : 'Confirm my RSVP' }}
              </button>
            </div>
          </div>
        </template>
      </div>

      <!-- ═══ SUCCESS ═══ -->
      <div v-else class="ginv-body">
        <div class="ginv-success">
          <div class="ck">✓</div>
          <h3>Thank you, {{ lastName }}!</h3>
          <div class="muted narrow">
            Your RSVP is confirmed. You'll attend <b>{{ yesCount }}</b> match{{ yesCount === 1 ? '' : 'es' }}.
            Tickets & logistics will follow by email.
          </div>
          <div class="confirmed-matches">
            <div v-for="m in matches.filter((x) => resp[x.id] === 'yes')" :key="m.id" class="conf-match">
              <div class="mc-stage">{{ m.stage }}</div>
              <div class="conf-teams"><span v-if="m.homeFlag" class="fl">{{ m.homeFlag }}</span> {{ m.homeTeam }} v {{ m.awayTeam }}</div>
              <div class="conf-meta">{{ m.date }} · {{ m.time }} · {{ m.venue }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ginv-greet { font-size: 14px; line-height: 1.65; white-space: pre-wrap; margin-bottom: 22px; }
.ginv-sec-h { font-family: var(--gms-font-display); font-size: 23px; margin: 10px 0 6px; }
.ginv-sec-sub { font-size: 13px; color: var(--gms-text-3); margin-bottom: 16px; }

/* Steps */
.rsvp-steps { display: flex; align-items: center; gap: 6px; margin-bottom: 20px; position: relative; }
.rsvp-step { font-size: 11.5px; font-weight: 600; padding: 5px 14px; border-radius: 20px; border: none; background: var(--gms-bg); color: var(--gms-text-3); cursor: default; transition: .12s; }
.rsvp-step.done { background: #dcfce7; color: #166534; cursor: pointer; }
.rsvp-step.done:hover { background: #bbf7d0; }
.rsvp-step.active { background: var(--gms-maroon); color: #fff; }
.rsvp-step.future { opacity: .5; }
.rsvp-step-line { display: none; }

/* Match cards */
.rsvp-top { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.mc-stage { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gms-maroon); background: rgba(138, 31, 61, 0.1); padding: 3px 8px; border-radius: 6px; }
.mc-stage.final { background: var(--gms-maroon); color: #fff; }
.rsvp-teams { font-weight: 700; font-size: 15px; margin-bottom: 4px; }
.fl { font-size: 16px; margin: 0 4px; }
.vs { margin: 0 6px; font-weight: 400; color: var(--gms-text-3); }
.rsvp-when { font-size: 12px; color: var(--gms-text-2); margin-bottom: 12px; }
.rsvp-toggle { display: flex; gap: 8px; }

/* Bar */
.ginv-bar { display: flex; align-items: center; justify-content: space-between; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--gms-border); gap: 12px; flex-wrap: wrap; }
.btn { padding: 11px 20px; border-radius: 10px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; transition: all 0.15s; }
.btn.pri { background: var(--gms-maroon); color: #fff; }
.btn.pri:hover:not(:disabled) { background: #6e1830; }
.btn.ghost { background: none; color: var(--gms-text-2); border: 1px solid var(--gms-border); }
.btn.ghost:hover { border-color: var(--gms-maroon); color: var(--gms-maroon); }
.btn:disabled { opacity: 0.4; cursor: not-allowed; }
.muted { color: var(--gms-text-3); }
.req { color: #dc2626; }

/* Doc fields */
.rsvp-docs { display: flex; flex-direction: column; gap: 20px; }
.rsvp-doc-field { display: flex; flex-direction: column; gap: 6px; }
.rsvp-doc-label { font-size: 13px; font-weight: 600; color: var(--gms-text); }
.rsvp-doc-sm { font-size: 11.5px; }
.rsvp-doc-input { padding: 12px 14px; border: 1px solid var(--gms-border); border-radius: 10px; font-size: 14px; font-family: var(--gms-font-mono); background: var(--gms-surface); outline: none; transition: .12s; }
.rsvp-doc-input:focus { border-color: var(--gms-maroon); box-shadow: 0 0 0 3px rgba(138,31,61,.08); }
.rsvp-input-sm { padding: 8px 10px; font-size: 12px; }

/* Upload zones */
.rsvp-upload-zone { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 28px 20px; border: 2px dashed var(--gms-border); border-radius: 12px; cursor: pointer; transition: .15s; text-align: center; }
.rsvp-upload-zone:hover { border-color: var(--gms-maroon); background: rgba(138,31,61,.02); }
.rsvp-zone-sm { padding: 14px 10px; }
.rsvp-uploading { cursor: default; border-style: solid; }
.rsvp-upload-icon { font-size: 28px; }
.rsvp-upload-text { font-size: 13px; font-weight: 600; color: var(--gms-text-2); }
.rsvp-upload-hint { font-size: 11px; color: var(--gms-text-3); }
.rsvp-upload-done { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border: 1px solid var(--gms-border); border-radius: 10px; background: var(--gms-bg); }
.rsvp-done-sm { padding: 6px 10px; gap: 8px; }
.rsvp-preview { width: 56px; height: 56px; border-radius: 8px; object-fit: cover; }
.rsvp-preview-sm { width: 36px; height: 36px; border-radius: 6px; object-fit: cover; }
.rsvp-file-badge { font-size: 13px; font-weight: 600; }
.rsvp-replace { margin-left: auto; background: none; border: 1px solid var(--gms-border); border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; color: var(--gms-text-2); transition: .1s; }
.rsvp-replace:hover { border-color: var(--gms-maroon); color: var(--gms-maroon); }

/* Consent */
.rsvp-consent { display: flex; align-items: flex-start; gap: 10px; padding: 14px 16px; border: 1px solid var(--gms-border); border-radius: 10px; cursor: pointer; font-size: 13px; line-height: 1.5; color: var(--gms-text-2); transition: .1s; }
.rsvp-consent:hover { border-color: var(--gms-maroon); }
.rsvp-consent input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--gms-maroon); cursor: pointer; flex-shrink: 0; margin-top: 2px; }

/* Companion cards */
.rsvp-comp-list { display: flex; flex-direction: column; gap: 14px; }
.rsvp-comp-card { border: 1px solid var(--gms-border); border-radius: 12px; overflow: hidden; background: var(--gms-surface); }
.rsvp-comp-head { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-bottom: 1px solid var(--gms-border); }
.rsvp-comp-num { width: 26px; height: 26px; border-radius: 50%; background: var(--gms-maroon); color: #fff; font-size: 12px; font-weight: 700; display: grid; place-items: center; flex-shrink: 0; }
.rsvp-comp-remove { background: none; border: none; cursor: pointer; font-size: 16px; color: var(--gms-text-3); padding: 4px; border-radius: 4px; transition: .1s; flex-shrink: 0; }
.rsvp-comp-remove:hover { color: #dc2626; background: rgba(220,38,38,.06); }
.rsvp-comp-docs { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; padding: 14px; background: var(--gms-bg); }
@media (max-width: 600px) { .rsvp-comp-docs { grid-template-columns: 1fr; } .rsvp-comp-head { flex-wrap: wrap; } }

.rsvp-add-comp { display: block; width: 100%; padding: 14px; border: 2px dashed var(--gms-border); border-radius: 12px; background: none; cursor: pointer; font-size: 14px; font-weight: 600; color: var(--gms-text-3); transition: .12s; margin-top: 10px; }
.rsvp-add-comp:hover { border-color: var(--gms-maroon); color: var(--gms-maroon); }

/* Success */
.ginv-success { text-align: center; padding: 40px 20px; }
.ck { font-size: 48px; color: #3f7d52; margin-bottom: 16px; }
.ginv-success h3 { font-family: var(--gms-font-display); font-size: 32px; margin-bottom: 12px; }
.narrow { max-width: 400px; margin: 0 auto 28px; }
.confirmed-matches { display: flex; flex-direction: column; gap: 10px; max-width: 500px; margin: 0 auto; text-align: left; }
.conf-match { border: 1px solid var(--gms-border); border-radius: 10px; padding: 12px; }
.conf-teams { font-weight: 600; font-size: 15px; margin: 4px 0; }
.conf-meta { font-size: 12px; color: var(--gms-text-2); }
</style>
