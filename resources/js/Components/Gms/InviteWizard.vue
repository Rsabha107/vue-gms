<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useForm } from '@inertiajs/vue3'
import GmsModal from './GmsModal.vue'
import GmsAvatar from './GmsAvatar.vue'
import GmsPill from './GmsPill.vue'
import GmsIcon from './GmsIcon.vue'
import GmsBtn from './GmsBtn.vue'

const props = defineProps({
    open:            { type: Boolean, default: false },
    initialGuest:    { type: Object,  default: null },
    guests:          { type: Array,   default: () => [] },
    matches:         { type: Array,   default: () => [] },
    tiers:           { type: Array,   default: () => [] },
    emailTemplates:  { type: Array,   default: () => [] },
    event:           { type: Object,  default: () => ({}) },
})

const emit = defineEmits(['close'])

// ── Step state ──────────────────────────────────────────────
const step = ref(0)
const steps = ['Matches', 'Email', 'Review']

// ── Guest selection ─────────────────────────────────────────
const selectedGuest = ref(null)
const recipientPickerOpen = ref(false)
const guestSearch = ref('')

const filteredGuests = computed(() => {
    const q = guestSearch.value.toLowerCase()
    if (!q) return props.guests
    return props.guests.filter(g =>
        g.name.toLowerCase().includes(q) ||
        (g.email || '').toLowerCase().includes(q)
    )
})

function selectGuest(guest) {
    selectedGuest.value = guest
    recipientPickerOpen.value = false
    guestSearch.value = ''
}

// ── Match selection ─────────────────────────────────────────
const selectedMatchIds = ref([])

// Transform matches to expected format
const transformedMatches = computed(() => 
    props.matches.map(m => ({
        id: m.id,
        eventId: m.eventId || 'E01',
        featured: m.featured || false,
        stage: m.stage,
        label: m.name,
        name: m.name,
        a: { flag: m.homeCode, name: m.homeTeam },
        b: { flag: m.awayCode, name: m.awayTeam },
        homeTeam: m.homeTeam,
        homeFlag: m.homeCode,
        awayTeam: m.awayTeam,
        awayFlag: m.awayCode,
        day: m.date.split(' ')[0],
        date: m.date,
        time: m.kickoff,
        kickoff: m.kickoff,
        venue: m.venueName,
        cap: m.seatsTotal,
        capacity: m.seatsTotal,
        sold: m.seatsTotal - m.seatsLeft,
        seats_assigned: m.seatsTotal - m.seatsLeft,
    }))
)

function toggleMatch(matchId) {
    const idx = selectedMatchIds.value.indexOf(matchId)
    if (idx >= 0) selectedMatchIds.value.splice(idx, 1)
    else selectedMatchIds.value.push(matchId)
}

function selectAllMatches() {
    selectedMatchIds.value = transformedMatches.value.map(m => m.id)
}

function selectKnockouts() {
    selectedMatchIds.value = transformedMatches.value
        .filter(m => ['Quarter Final', 'Semi Final', 'Final'].includes(m.stage))
        .map(m => m.id)
}

function clearMatches() {
    selectedMatchIds.value = []
}

const selectedMatches = computed(() =>
    transformedMatches.value.filter(m => selectedMatchIds.value.includes(m.id))
)

// ── Email template ──────────────────────────────────────────
const templateName = ref('Default invite')
const subject = ref('')
const body = ref('')
const deadline = ref('5 Aug 2026')
const bodyTextarea = ref(null)

const mergeTags = [
    { tag: '{{title}}', label: 'Title' },
    { tag: '{{guest_name}}', label: 'Guest name' },
    { tag: '{{event}}', label: 'Event name' },
    { tag: '{{venue}}', label: 'Venue' },
    { tag: '{{rsvp_deadline}}', label: 'RSVP deadline' },
    { tag: '{{sender}}', label: 'Sender name' },
]

function applyTemplate(tpl) {
    templateName.value = tpl.name
    subject.value = tpl.subject
    body.value = tpl.body
}

function insertTag(tag) {
    const el = bodyTextarea.value
    if (!el) {
        body.value += tag
        return
    }
    const start = el.selectionStart
    const end = el.selectionEnd
    body.value = body.value.slice(0, start) + tag + body.value.slice(end)
    nextTick(() => {
        el.focus()
        el.setSelectionRange(start + tag.length, start + tag.length)
    })
}

// ── Context for merge tags ──────────────────────────────────
const context = computed(() => ({
    '{{title}}': selectedGuest.value?.title || 'Mr',
    '{{guest_name}}': (selectedGuest.value?.name || 'Guest').replace(/^(H\.E\.|Sheikha?|Dr|Mr|Mrs|Ms)\.?\s+/, ''),
    '{{event}}': props.event.name || 'event',
    '{{venue}}': props.event.venue || 'venue',
    '{{rsvp_deadline}}': deadline.value,
    '{{sender}}': 'Protocol Team',
}))

function resolveTokens(str) {
    if (!str) return ''
    return str.replace(/\{\{[a-z_]+\}\}/g, m => context.value[m] || m)
}

// ── Guest preview ───────────────────────────────────────────
const showPreview = ref(false)
const previewResponses = ref({}) // matchId -> 'yes' | 'no'
const previewDone = ref(false)

function openPreview() {
    previewResponses.value = {}
    previewDone.value = false
    showPreview.value = true
}

function togglePreviewResponse(matchId, response) {
    if (previewResponses.value[matchId] === response) {
        delete previewResponses.value[matchId]
    } else {
        previewResponses.value[matchId] = response
    }
    previewResponses.value = { ...previewResponses.value }
}

const previewAccepted = computed(() => 
    selectedMatches.value.filter(m => previewResponses.value[m.id] === 'yes')
)

const previewDecided = computed(() => 
    Object.keys(previewResponses.value).length
)

// ── Navigation ──────────────────────────────────────────────
const canGoNext = computed(() => {
    if (step.value === 0) return selectedGuest.value && selectedMatchIds.value.length > 0
    if (step.value === 1) return subject.value.trim() && body.value.trim()
    return true
})

function goNext() {
    if (canGoNext.value) step.value++
}

function goBack() {
    if (step.value > 0) step.value--
}

// ── Submit ──────────────────────────────────────────────────
const form = useForm({
    guestId: null,
    matchIds: [],
    subject: '',
    body: '',
    rsvpDeadline: '',
})

function send() {
    if (!selectedGuest.value) return
    
    form.guestId = selectedGuest.value.id
    form.matchIds = selectedMatchIds.value
    form.subject = subject.value
    form.body = body.value
    form.rsvpDeadline = deadline.value

    form.post(route('gms.invitations.send'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close')
            // Toast will be shown by parent
        },
        onError: () => {
            // Error handling
        },
    })
}

// ── Reset on open ───────────────────────────────────────────
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        step.value = 0
        selectedGuest.value = props.initialGuest || null
        recipientPickerOpen.value = false
        guestSearch.value = ''
        
        // Pre-select featured matches
        selectedMatchIds.value = transformedMatches.value
            .filter(m => m.featured)
            .map(m => m.id)
        
        // Load default template
        const defaultTpl = props.emailTemplates.find(t => t.name === 'Default invite') || props.emailTemplates[0]
        if (defaultTpl) {
            templateName.value = defaultTpl.name
            subject.value = defaultTpl.subject
            body.value = defaultTpl.body
        }
        
        deadline.value = '5 Aug 2026'
    }
})

// ── Helpers ─────────────────────────────────────────────────
function tierLabel(id) {
    return props.tiers.find(t => t.id === id)?.name ?? id
}

function tierStyle(id) {
    const t = props.tiers.find(t => t.id === id)
    return { background: t?.bg ?? '#f3f4f6', color: t?.color ?? '#374151', fontSize: '10.5px' }
}

function formatMatchStatus(match) {
    const left = match.capacity - (match.seats_assigned || 0)
    const fillPct = Math.round((match.seats_assigned || 0) / match.capacity * 100)
    return { left, fillPct }
}
</script>

<template>
  <GmsModal :open="open" :title="'Invite to ' + (event.name || 'event')" size="xl" @close="emit('close')">
    <!-- Stepper -->
    <div class="gms-wizard-steps">
      <div v-for="(s, i) in steps" :key="s" class="gms-wizard-step" :class="{ active: i === step, done: i < step }">
        <span class="gms-wizard-step-num">{{ i < step ? '✓' : i + 1 }}</span>
        <span class="gms-wizard-step-label">{{ s }}</span>
        <div v-if="i < steps.length - 1" class="gms-wizard-step-line" :class="{ done: i < step }"></div>
      </div>
    </div>

    <!-- Recipient bar (always visible) -->
    <div class="gms-wizard-recipient">
      <span class="gms-wizard-recipient-label">Recipient</span>
      
      <template v-if="selectedGuest">
        <GmsAvatar :name="selectedGuest.name" size="md" />
        <div style="flex: 1; min-width: 0;">
          <div style="font-weight: 600; font-size: 13.5px;">{{ selectedGuest.name }}</div>
          <div style="font-size: 11.5px; color: var(--gms-text-3);">{{ selectedGuest.email }} · {{ selectedGuest.group }}</div>
        </div>
        <span class="gms-pill" :style="tierStyle(selectedGuest.tier)">{{ tierLabel(selectedGuest.tier) }}</span>
        <div style="position: relative;">
          <GmsBtn variant="ghost" @click="recipientPickerOpen = !recipientPickerOpen">Change</GmsBtn>
          
          <!-- Guest picker dropdown -->
          <div v-if="recipientPickerOpen" class="gms-wizard-guest-picker">
            <div class="gms-search-wrap" style="margin-bottom: 8px;">
              <GmsIcon name="search" :size="14" class="gms-search-icon" />
              <input v-model="guestSearch" class="gms-search-input" placeholder="Search guests..." />
            </div>
            <div class="gms-wizard-guest-list">
              <button
                v-for="g in filteredGuests"
                :key="g.id"
                class="gms-wizard-guest-item"
                @click="selectGuest(g)"
              >
                <GmsAvatar :name="g.name" size="sm" />
                <div style="flex: 1; min-width: 0;">
                  <div style="font-weight: 600; font-size: 13px;">{{ g.name }}</div>
                  <div style="font-size: 11px; color: var(--gms-text-3);">{{ g.email }}</div>
                </div>
              </button>
            </div>
          </div>
        </div>
      </template>
      
      <template v-else>
        <div style="position: relative; flex: 1;">
          <GmsBtn icon="plus" @click="recipientPickerOpen = !recipientPickerOpen">Select a guest</GmsBtn>
          
          <!-- Guest picker dropdown -->
          <div v-if="recipientPickerOpen" class="gms-wizard-guest-picker">
            <div class="gms-search-wrap" style="margin-bottom: 8px;">
              <GmsIcon name="search" :size="14" class="gms-search-icon" />
              <input v-model="guestSearch" class="gms-search-input" placeholder="Search guests..." />
            </div>
            <div class="gms-wizard-guest-list">
              <button
                v-for="g in filteredGuests"
                :key="g.id"
                class="gms-wizard-guest-item"
                @click="selectGuest(g)"
              >
                <GmsAvatar :name="g.name" size="sm" />
                <div style="flex: 1; min-width: 0;">
                  <div style="font-weight: 600; font-size: 13px;">{{ g.name }}</div>
                  <div style="font-size: 11px; color: var(--gms-text-3);">{{ g.email }}</div>
                </div>
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Step content -->
    <div class="gms-wizard-content">
      <!-- STEP 0: Matches -->
      <div v-if="step === 0">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;">
          <div style="font-size: 13px; color: var(--gms-text-2);">Choose which matches to offer — the guest picks from these.</div>
          <div style="margin-left: auto; display: flex; gap: 6px;">
            <button class="gms-chip" @click="selectAllMatches">Select all</button>
            <button class="gms-chip" @click="selectKnockouts">Knockouts</button>
            <button class="gms-chip" @click="clearMatches">Clear</button>
          </div>
        </div>
        <div class="gms-wizard-match-grid">
          <div
            v-for="m in transformedMatches"
            :key="m.id"
            class="gms-wizard-match-card"
            :class="{ sel: selectedMatchIds.includes(m.id) }"
            @click="toggleMatch(m.id)"
          >
            <div class="gms-wizard-match-top">
              <span class="gms-wizard-match-stage" :class="{ final: m.stage === 'Final' }">{{ m.stage }}</span>
              <span style="font-size: 11.5px; color: var(--gms-text-3);">{{ m.name }}</span>
              <span class="gms-wizard-match-check">
                <GmsIcon v-if="selectedMatchIds.includes(m.id)" name="check" :size="12" />
              </span>
            </div>
            <div class="gms-wizard-match-teams">
              <span class="team">
                <span v-if="m.homeFlag" class="fl">{{ m.homeFlag }}</span>
                {{ m.homeTeam }}
              </span>
              <span class="vs">v</span>
              <span class="team r">
                {{ m.awayTeam }}
                <span v-if="m.awayFlag" class="fl">{{ m.awayFlag }}</span>
              </span>
            </div>
            <div class="gms-wizard-match-meta">
              <span>{{ m.day }} {{ m.date }}</span>
              <span class="dot"></span>
              <span>{{ m.kickoff }}</span>
              <span class="gms-wizard-match-seats">
                <span class="gms-wizard-match-bar">
                  <i :style="{ width: formatMatchStatus(m).fillPct + '%' }"></i>
                </span>
                {{ formatMatchStatus(m).left }} left
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- STEP 1: Email template -->
      <div v-if="step === 1" class="gms-wizard-email-grid">
        <div>
          <div class="gms-field">
            <label class="gms-label">Template</label>
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
              <button
                v-for="tpl in emailTemplates"
                :key="tpl.id"
                class="gms-chip"
                :class="{ on: templateName === tpl.name }"
                @click="applyTemplate(tpl)"
              >
                {{ tpl.name }}
              </button>
            </div>
          </div>
          
          <div class="gms-field">
            <label class="gms-label">Subject <span style="color: var(--bad);">*</span></label>
            <input v-model="subject" type="text" class="gms-input" placeholder="Email subject" />
          </div>
          
          <div class="gms-field">
            <label class="gms-label">Message body <span style="color: var(--bad);">*</span></label>
            <textarea
              ref="bodyTextarea"
              v-model="body"
              class="gms-wizard-textarea"
              rows="8"
            ></textarea>
          </div>
          
          <div>
            <div style="font-size: 12px; color: var(--gms-text-3); margin-bottom: 8px;">Insert a merge tag:</div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
              <button
                v-for="tag in mergeTags"
                :key="tag.tag"
                class="gms-wizard-tag-chip"
                :title="tag.label"
                @click="insertTag(tag.tag)"
              >
                {{ tag.tag }}
              </button>
            </div>
          </div>
          
          <div class="gms-field">
            <label class="gms-label">RSVP deadline <span style="color: var(--bad);">*</span></label>
            <input v-model="deadline" type="text" class="gms-input" />
          </div>
        </div>

        <!-- Live preview -->
        <div>
          <div class="gms-wizard-preview-label">
            <GmsIcon name="mail" :size="16" />
            Live preview
          </div>
          <div class="gms-wizard-email-preview">
            <div class="gms-wizard-email-header">
              <div class="crest">🏆</div>
              <h3>{{ event.name || 'Event' }}</h3>
              <div class="sub">{{ event.venue || 'Venue' }}</div>
            </div>
            <div class="gms-wizard-email-body">
              <div class="subject">{{ resolveTokens(subject) }}</div>
              <div class="text">{{ resolveTokens(body) }}</div>
              <div v-if="selectedMatches.length > 0" class="matches">
                <div
                  v-for="m in selectedMatches"
                  :key="m.id"
                  class="mini-match"
                >
                  <span class="stage">{{ m.stage }}</span>
                  <div style="flex: 1;">
                    <div class="teams">{{ m.homeFlag }} {{ m.homeTeam }} <span style="color: var(--gms-text-3);">v</span> {{ m.awayFlag }} {{ m.awayTeam }}</div>
                    <div class="when">{{ m.day }} {{ m.date }} · {{ m.kickoff }} · {{ m.venue }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- STEP 2: Review -->
      <div v-if="step === 2">
        <div style="margin-bottom: 20px;">
          <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--gms-text-3); margin-bottom: 10px;">Summary</div>
          <div class="gms-detail-row"><span class="gms-detail-label">Recipient</span><span class="gms-detail-value">{{ selectedGuest?.name }}</span></div>
          <div class="gms-detail-row"><span class="gms-detail-label">Email</span><span class="gms-detail-value">{{ selectedGuest?.email }}</span></div>
          <div class="gms-detail-row"><span class="gms-detail-label">Template</span><span class="gms-detail-value">{{ templateName }}</span></div>
          <div class="gms-detail-row"><span class="gms-detail-label">Subject</span><span class="gms-detail-value">{{ resolveTokens(subject) }}</span></div>
          <div class="gms-detail-row"><span class="gms-detail-label">Matches offered</span><span class="gms-detail-value">{{ selectedMatches.length }}</span></div>
          <div class="gms-detail-row"><span class="gms-detail-label">RSVP by</span><span class="gms-detail-value">{{ deadline }}</span></div>
        </div>

        <div style="background: var(--gms-maroon-tint); border: 1px solid var(--gms-maroon-soft); border-radius: 8px; padding: 14px 16px; margin-bottom: 20px;">
          <div style="font-size: 13px; color: var(--gms-text-2); margin-bottom: 8px;">See exactly what the guest receives and how they choose matches.</div>
          <GmsBtn variant="ghost" icon="arrow-right" style="font-size: 12px; padding: 6px 12px;" @click="openPreview">Preview as guest</GmsBtn>
        </div>

        <div>
          <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--gms-text-3); margin-bottom: 10px;">Matches the guest can choose from</div>
          <div style="display: flex; flex-direction: column; gap: 8px;">
            <div
              v-for="m in selectedMatches"
              :key="m.id"
              class="mini-match"
            >
              <span class="gms-pill" style="background: var(--gms-maroon-tint); color: var(--gms-maroon); font-size: 10px; font-weight: 700; text-transform: uppercase;">{{ m.stage }}</span>
              <div style="flex: 1;">
                <div style="font-weight: 600; font-size: 13px;">{{ m.homeTeam }} vs {{ m.awayTeam }}</div>
                <div style="font-size: 11px; color: var(--gms-text-3);">{{ m.venue }} · {{ m.kickoff }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div style="display: flex; align-items: center; width: 100%;">
        <span style="font-size: 12.5px; color: var(--gms-text-3);">
          {{ selectedGuest ? selectedGuest.name.split(' ').pop() : 'No recipient' }} · {{ selectedMatchIds.length }} match{{ selectedMatchIds.length !== 1 ? 'es' : '' }}
        </span>
        <div style="margin-left: auto; display: flex; gap: 10px;">
          <GmsBtn v-if="step > 0" @click="goBack">Back</GmsBtn>
          <GmsBtn v-if="step < 2" variant="primary" :disabled="!canGoNext" @click="goNext">Continue</GmsBtn>
          <GmsBtn v-else variant="primary" icon="mail" :processing="form.processing" :disabled="form.processing" @click="send">Send invitation</GmsBtn>
        </div>
      </div>
    </template>
  </GmsModal>

  <!-- Guest Preview Modal -->
  <Teleport to="body">
    <div v-if="showPreview" class="gms-preview-scrim" @mousedown="e => { if (e.target === e.currentTarget) showPreview = false }">
      <button class="gms-preview-close" @click="showPreview = false">
        <GmsIcon name="x" :size="18" />
      </button>
      
      <div class="gms-preview-modal">
        <!-- Event Header -->
        <div class="gms-preview-header">
          <div class="gms-preview-crest">🏆</div>
          <div class="gms-preview-event">{{ event.name }}</div>
          <div class="gms-preview-dates">{{ event.location }} · {{ event.date_start }} - {{ event.date_end }}</div>
        </div>

        <div v-if="!previewDone" class="gms-preview-body">
          <!-- Email body -->
          <div class="gms-preview-greeting">{{ resolveTokens(body) }}</div>

          <!-- Match Selection -->
          <div class="gms-preview-section-header">Select the matches you'll attend</div>
          <div class="gms-preview-section-sub">Tap "Attending" for each match you can join. Confirm by {{ deadline }}.</div>

          <div class="gms-preview-matches">
            <div 
              v-for="m in selectedMatches" 
              :key="m.id"
              class="gms-rsvp-match"
              :class="{ yes: previewResponses[m.id] === 'yes', no: previewResponses[m.id] === 'no' }"
            >
              <div class="gms-rsvp-top">
                <span class="gms-wizard-match-stage" :class="{ final: m.stage === 'Final' }">{{ m.stage }}</span>
                <span style="font-size: 11.5px; color: var(--gms-text-3);">{{ m.name }}</span>
              </div>
              <div class="gms-rsvp-teams">
                <span v-if="m.homeFlag" class="fl">{{ m.homeFlag }}</span>
                {{ m.homeTeam }}
                <span class="vs">v</span>
                <span v-if="m.awayFlag" class="fl">{{ m.awayFlag }}</span>
                {{ m.awayTeam }}
              </div>
              <div class="gms-rsvp-when">{{ m.day }} {{ m.date }} · {{ m.kickoff }} · {{ m.venue }}</div>
              <div class="gms-rsvp-toggle">
                <button 
                  class="gms-rsvp-btn"
                  :class="{ 'on-yes': previewResponses[m.id] === 'yes' }"
                  @click="togglePreviewResponse(m.id, 'yes')"
                >
                  <GmsIcon name="check" :size="14" /> Attending
                </button>
                <button 
                  class="gms-rsvp-btn"
                  :class="{ 'on-no': previewResponses[m.id] === 'no' }"
                  @click="togglePreviewResponse(m.id, 'no')"
                >
                  <GmsIcon name="x" :size="14" /> Can't make it
                </button>
              </div>
            </div>
          </div>

          <div class="gms-preview-bar">
            <div style="font-size: 13px;">
              <strong>{{ previewAccepted.length }}</strong> 
              <span class="muted">attending · {{ previewDecided }}/{{ selectedMatches.length }} answered</span>
            </div>
            <button 
              class="gms-btn gms-btn-primary" 
              style="margin-left: auto;"
              :disabled="previewDecided === 0"
              @click="previewDone = true"
            >
              Confirm my selection
            </button>
          </div>
        </div>

        <div v-else class="gms-preview-body">
          <div class="gms-preview-success">
            <div class="gms-preview-check">
              <GmsIcon name="check-circle" :size="48" />
            </div>
            <h3>Thank you, {{ selectedGuest?.name.split(' ').pop() }}!</h3>
            <div class="muted" style="font-size: 13.5px; max-width: 360px; margin: 0 auto;">
              Your selection is confirmed. You'll attend <strong>{{ previewAccepted.length }}</strong> match{{ previewAccepted.length !== 1 ? 'es' : '' }}. 
              Tickets &amp; logistics will follow by email.
            </div>
            <div class="gms-preview-confirmed-matches">
              <div v-if="previewAccepted.length" style="display: flex; flex-direction: column; gap: 8px;">
                <div v-for="m in previewAccepted" :key="m.id" class="mini-match">
                  <span class="gms-pill" style="background: var(--gms-maroon-tint); color: var(--gms-maroon); font-size: 10px; font-weight: 700; text-transform: uppercase;">{{ m.stage }}</span>
                  <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 13px;">{{ m.homeTeam }} vs {{ m.awayTeam }}</div>
                    <div style="font-size: 11px; color: var(--gms-text-3);">{{ m.venue }} · {{ m.kickoff }}</div>
                  </div>
                </div>
              </div>
              <div v-else class="gms-empty" style="padding: 18px;">No matches selected.</div>
            </div>
            <button class="gms-btn" style="margin-top: 20px;" @click="showPreview = false">Close preview</button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style>
/* Wizard stepper */
.gms-wizard-steps {
  display: flex;
  align-items: center;
  gap: 0;
  margin-bottom: 20px;
  padding: 0 28px 18px;
  border-bottom: 1px solid var(--gms-border);
}

.gms-wizard-step {
  display: flex;
  align-items: center;
  gap: 9px;
  color: var(--gms-text-3);
  font-size: 13px;
  font-weight: 600;
  position: relative;
}

.gms-wizard-step.active {
  color: var(--gms-maroon);
}

.gms-wizard-step.done {
  color: var(--good);
}

.gms-wizard-step-num {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 1.5px solid var(--gms-border);
  display: grid;
  place-items: center;
  font-size: 12px;
  background: var(--gms-surface);
}

.gms-wizard-step.active .gms-wizard-step-num {
  border-color: var(--gms-maroon);
  background: var(--gms-maroon);
  color: #fff;
}

.gms-wizard-step.done .gms-wizard-step-num {
  border-color: var(--good);
  background: var(--good);
  color: #fff;
}

.gms-wizard-step-line {
  flex: 1;
  height: 1.5px;
  background: var(--gms-border);
  margin: 0 12px;
  min-width: 18px;
}

.gms-wizard-step-line.done {
  background: var(--good);
}

/* Recipient bar */
.gms-wizard-recipient {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: var(--r);
  background: var(--gms-surface-2);
  border: 1px solid var(--gms-border);
  margin: 0 28px 18px;
}

.gms-wizard-recipient-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: var(--gms-text-3);
}

/* Guest picker dropdown */
.gms-wizard-guest-picker {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  z-index: 50;
  min-width: 320px;
  max-width: 400px;
  background: var(--gms-surface);
  border: 1px solid var(--gms-border);
  border-radius: var(--r);
  box-shadow: var(--sh-lg);
  padding: 8px;
}

.gms-wizard-guest-list {
  max-height: 240px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.gms-wizard-guest-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: var(--r);
  border: none;
  background: transparent;
  text-align: left;
  cursor: pointer;
  transition: 0.12s;
}

.gms-wizard-guest-item:hover {
  background: var(--gms-surface-2);
}

/* Content area */
.gms-wizard-content {
  padding: 20px 28px;
  overflow-y: auto;
  min-height: 300px;
  max-height: 58vh;
}

/* Match grid */
.gms-wizard-match-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 11px;
}

.gms-wizard-match-card {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px;
  border-radius: var(--r);
  border: 1px solid var(--gms-border);
  background: var(--gms-surface);
  cursor: pointer;
  transition: 0.13s;
  position: relative;
}

.gms-wizard-match-card:hover {
  border-color: var(--ink-3);
}

.gms-wizard-match-card.sel {
  border-color: var(--gms-maroon);
  background: var(--gms-maroon-tint);
  box-shadow: 0 0 0 1px var(--gms-maroon);
}

.gms-wizard-match-top {
  display: flex;
  align-items: center;
  gap: 8px;
}

.gms-wizard-match-stage {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: var(--gms-maroon);
  background: var(--gms-maroon-soft);
  padding: 2px 8px;
  border-radius: 6px;
}

.gms-wizard-match-stage.final {
  background: var(--gms-gold-soft);
  color: var(--gms-gold);
}

.gms-wizard-match-check {
  margin-left: auto;
  width: 20px;
  height: 20px;
  border-radius: 6px;
  border: 1.5px solid var(--gms-border);
  background: #fff;
  display: grid;
  place-items: center;
  color: #fff;
  flex: none;
  transition: 0.12s;
}

.gms-wizard-match-card.sel .gms-wizard-match-check {
  background: var(--gms-maroon);
  border-color: var(--gms-maroon);
}

.gms-wizard-match-teams {
  display: flex;
  align-items: center;
  gap: 10px;
}

.gms-wizard-match-teams .team {
  display: flex;
  align-items: center;
  gap: 7px;
  font-weight: 700;
  font-size: 14px;
  flex: 1;
  min-width: 0;
}

.gms-wizard-match-teams .team.r {
  justify-content: flex-end;
  text-align: right;
}

.gms-wizard-match-teams .fl {
  font-size: 20px;
  line-height: 1;
}

.gms-wizard-match-teams .vs {
  font-family: var(--gms-font-display);
  font-size: 15px;
  color: var(--gms-text-3);
  font-style: italic;
}

.gms-wizard-match-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 12px;
  color: var(--gms-text-2);
}

.gms-wizard-match-meta .dot {
  width: 3px;
  height: 3px;
  border-radius: 50%;
  background: var(--gms-text-3);
}

.gms-wizard-match-seats {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--gms-text-3);
}

.gms-wizard-match-bar {
  width: 48px;
  height: 5px;
  border-radius: 5px;
  background: var(--gms-surface-3);
  overflow: hidden;
}

.gms-wizard-match-bar i {
  display: block;
  height: 100%;
  background: var(--gms-gold);
}

/* Email grid */
.gms-wizard-email-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 22px;
  align-items: start;
}

@media (max-width: 860px) {
  .gms-wizard-email-grid {
    grid-template-columns: 1fr;
  }
}

.gms-wizard-textarea {
  border: 1px solid var(--gms-border);
  border-radius: 10px;
  background: var(--gms-surface);
  padding: 11px 13px;
  width: 100%;
  resize: vertical;
  outline: none;
  font-size: 13.5px;
  line-height: 1.6;
  color: var(--gms-text);
  font-family: inherit;
}

.gms-wizard-textarea:focus {
  border-color: var(--gms-maroon);
  box-shadow: 0 0 0 3px var(--gms-maroon-soft);
}

.gms-wizard-tag-chip {
  font-family: var(--gms-font-mono);
  font-size: 11px;
  padding: 3px 8px;
  border-radius: 7px;
  background: var(--gms-surface-2);
  border: 1px solid var(--gms-border);
  color: var(--gms-maroon);
  cursor: pointer;
  transition: 0.12s;
}

.gms-wizard-tag-chip:hover {
  background: var(--gms-maroon-soft);
  border-color: var(--gms-maroon);
}

.gms-wizard-preview-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: var(--gms-text-3);
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Email preview */
.gms-wizard-email-preview {
  border: 1px solid var(--gms-border);
  border-radius: var(--r-lg);
  overflow: hidden;
  background: var(--gms-surface);
  box-shadow: var(--sh-sm);
}

.gms-wizard-email-header {
  background: linear-gradient(150deg, var(--gms-maroon), var(--gms-maroon-700));
  color: #fff;
  padding: 22px 24px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.gms-wizard-email-header::after {
  content: '';
  position: absolute;
  right: -30px;
  top: -30px;
  width: 140px;
  height: 140px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
}

.gms-wizard-email-header .crest {
  font-size: 26px;
  margin-bottom: 8px;
}

.gms-wizard-email-header h3 {
  font-family: var(--gms-font-display);
  font-size: 27px;
  line-height: 1.05;
  font-weight: 400;
}

.gms-wizard-email-header .sub {
  font-size: 12.5px;
  opacity: 0.8;
  margin-top: 5px;
  letter-spacing: 0.3px;
}

.gms-wizard-email-body {
  padding: 22px 24px;
}

.gms-wizard-email-body .subject {
  font-weight: 700;
  font-size: 15px;
  margin-bottom: 14px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--gms-border);
}

.gms-wizard-email-body .text {
  font-size: 13.5px;
  line-height: 1.65;
  color: var(--gms-text);
  white-space: pre-wrap;
}

.gms-wizard-email-body .matches {
  margin-top: 18px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.mini-match {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 10px 12px;
  border: 1px solid var(--gms-border);
  border-radius: 10px;
}

.mini-match .stage {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  padding: 2px 6px;
  border-radius: 5px;
  background: var(--gms-maroon-soft);
  color: var(--gms-maroon);
}

.mini-match .teams {
  font-weight: 700;
  font-size: 13px;
}

.mini-match .when {
  font-size: 11.5px;
  color: var(--gms-text-2);
  margin-top: 2px;
}

/* Chip button */
.gms-chip {
  font-size: 12px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 8px;
  border: 1px solid var(--gms-border);
  background: var(--gms-surface);
  color: var(--gms-text-2);
  cursor: pointer;
  transition: 0.12s;
}

.gms-chip:hover {
  border-color: var(--gms-maroon);
  color: var(--gms-maroon);
  background: var(--gms-maroon-tint);
}

.gms-chip.on {
  background: var(--gms-maroon);
  color: #fff;
  border-color: var(--gms-maroon);
}

/* Guest Preview Modal */
.gms-preview-scrim {
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  overflow-y: auto;
}

.gms-preview-close {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10002;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: rgba(0, 0, 0, 0.5);
  color: #fff;
  display: grid;
  place-items: center;
  cursor: pointer;
  transition: 0.15s;
}

.gms-preview-close:hover {
  background: rgba(0, 0, 0, 0.7);
}

.gms-preview-modal {
  background: var(--gms-surface);
  border-radius: 12px;
  width: 100%;
  max-width: 640px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  overflow: hidden;
  position: relative;
  z-index: 10001;
}

.gms-preview-header {
  background: linear-gradient(135deg, var(--gms-maroon) 0%, #6d1830 100%);
  color: #fff;
  padding: 32px 24px;
  text-align: center;
}

.gms-preview-crest {
  font-size: 48px;
  margin-bottom: 12px;
}

.gms-preview-event {
  font-family: var(--gms-font-display);
  font-size: 26px;
  font-weight: 400;
  margin-bottom: 6px;
}

.gms-preview-dates {
  font-size: 13px;
  opacity: 0.85;
}

.gms-preview-body {
  padding: 24px;
  max-height: 60vh;
  overflow-y: auto;
}

.gms-preview-greeting {
  font-size: 14px;
  line-height: 1.6;
  color: var(--gms-text-2);
  margin-bottom: 24px;
  white-space: pre-wrap;
}

.gms-preview-section-header {
  font-weight: 700;
  font-size: 16px;
  color: var(--gms-text);
  margin-bottom: 6px;
}

.gms-preview-section-sub {
  font-size: 13px;
  color: var(--gms-text-3);
  margin-bottom: 16px;
}

.gms-preview-matches {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
}

.gms-rsvp-match {
  border: 1.5px solid var(--gms-border);
  border-radius: 10px;
  padding: 14px 16px;
  background: var(--gms-surface);
  transition: 0.15s;
}

.gms-rsvp-match.yes {
  border-color: var(--good);
  background: var(--good-soft);
}

.gms-rsvp-match.no {
  border-color: var(--gms-border);
  background: #f9fafb;
  opacity: 0.6;
}

.gms-rsvp-top {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.gms-rsvp-teams {
  font-weight: 700;
  font-size: 15px;
  color: var(--gms-text);
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.gms-rsvp-teams .fl {
  font-size: 18px;
}

.gms-rsvp-teams .vs {
  color: var(--gms-text-3);
  font-weight: 400;
  font-size: 13px;
}

.gms-rsvp-when {
  font-size: 12px;
  color: var(--gms-text-3);
  margin-bottom: 12px;
}

.gms-rsvp-toggle {
  display: flex;
  gap: 8px;
}

.gms-rsvp-btn {
  flex: 1;
  padding: 8px 12px;
  border-radius: 6px;
  border: 1.5px solid var(--gms-border);
  background: var(--gms-surface);
  color: var(--gms-text-2);
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.12s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.gms-rsvp-btn:hover {
  border-color: var(--gms-maroon);
  color: var(--gms-maroon);
}

.gms-rsvp-btn.on-yes {
  border-color: var(--good);
  background: var(--good);
  color: #fff;
}

.gms-rsvp-btn.on-no {
  border-color: var(--gms-border);
  background: #f3f4f6;
  color: var(--gms-text-3);
}

.gms-preview-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid var(--gms-border);
}

.gms-preview-success {
  text-align: center;
  padding: 20px 0;
}

.gms-preview-check {
  color: var(--good);
  margin-bottom: 16px;
}

.gms-preview-success h3 {
  font-family: var(--gms-font-display);
  font-size: 24px;
  font-weight: 400;
  margin-bottom: 12px;
  color: var(--gms-text);
}

.gms-preview-confirmed-matches {
  margin-top: 20px;
  text-align: left;
  max-width: 440px;
  margin-left: auto;
  margin-right: auto;
}
</style>
