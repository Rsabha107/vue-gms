<script setup>
import { ref, computed, inject } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsPill from '@/Components/Gms/GmsPill.vue'
import GmsDrawer from '@/Components/Gms/GmsDrawer.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'
import GmsMiniStat from '@/Components/Gms/GmsMiniStat.vue'
import GmsBtn from '@/Components/Gms/GmsBtn.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    requests: { type: Array,  default: () => [] },
    guests:   { type: Array,  default: () => [] },
    tiers:    { type: Array,  default: () => [] },
    event:    { type: Object, default: () => ({}) },
})

const toast = inject('toast')

const localReqs = ref(props.requests.map(r => ({ ...r })))

// ── Filters ───────────────────────────────────────────────────────
const search       = ref('')
const statusFilter = ref('all')
const tabs = [
    { key: 'all',       label: 'All' },
    { key: 'new',       label: 'New' },
    { key: 'change',    label: 'Change' },
    { key: 'confirmed', label: 'Confirmed' },
    { key: 'cancelled', label: 'Cancelled' },
]

function countFor(key) {
    if (key === 'all')       return localReqs.value.length
    if (key === 'new')       return localReqs.value.filter(r => r.status === 'new' && !r.changeRequest).length
    if (key === 'change')    return localReqs.value.filter(r => r.changeRequest).length
    if (key === 'confirmed') return localReqs.value.filter(r => r.status === 'confirmed').length
    if (key === 'cancelled') return localReqs.value.filter(r => r.status === 'cancelled').length
    return 0
}

const filtered = computed(() => {
    let list = localReqs.value
    if (statusFilter.value === 'new')       list = list.filter(r => r.status === 'new' && !r.changeRequest)
    else if (statusFilter.value === 'change')    list = list.filter(r => r.changeRequest)
    else if (statusFilter.value === 'confirmed') list = list.filter(r => r.status === 'confirmed')
    else if (statusFilter.value === 'cancelled') list = list.filter(r => r.status === 'cancelled')
    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(r =>
            r.guestName.toLowerCase().includes(q) ||
            (r.pnr ?? '').toLowerCase().includes(q) ||
            r.flightNo.toLowerCase().includes(q) ||
            r.route.toLowerCase().includes(q)
        )
    }
    return list
})

// ── Display helpers ───────────────────────────────────────────────
function displayStatus(r) {
    if (r.changeRequest)        return { label: 'Change',    bg: '#dbeafe', fg: '#2563eb' }
    if (r.status === 'new')     return { label: 'New',       bg: '#fef3c7', fg: '#a16207' }
    if (r.status === 'confirmed') return { label: 'Confirmed', bg: '#dcfce7', fg: '#15803d' }
    if (r.status === 'cancelled') return { label: 'Cancelled', bg: '#f3f4f6', fg: '#6b7280' }
    return { label: r.status, bg: '#f3f4f6', fg: '#374151' }
}

function classPillClass(c) {
    if (c === 'First')    return 'flt-class-pill first'
    if (c === 'Business') return 'flt-class-pill business'
    return 'flt-class-pill economy'
}

const MONTHS = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']
function fmtDate(d, t) {
    if (!d) return '—'
    const parts = d.split('-')
    const label = `${parseInt(parts[2])} ${MONTHS[parseInt(parts[1])]}`
    return t ? `${label} · ${t}` : label
}

function fmtDateShort(d) {
    if (!d) return '—'
    const parts = d.split('-')
    return `${parseInt(parts[2])} ${MONTHS[parseInt(parts[1])]}`
}

function canConfirm(r) {
    return r.status !== 'confirmed' && r.status !== 'cancelled'
}

// ── Drawer ────────────────────────────────────────────────────────
const drawerOpen = ref(false)
const activeReq  = ref(null)

function openDrawer(r) { activeReq.value = r; drawerOpen.value = true }

function guestTier(r) {
    const g = props.guests.find(g => g.id === r.guestId)
    if (!g) return null
    return props.tiers.find(t => t.id === g.tier)
}

// ── Status change ─────────────────────────────────────────────────
function confirmRow(r, e) {
    e?.stopPropagation()
    const idx = localReqs.value.findIndex(x => x.id === r.id)
    if (idx !== -1) localReqs.value[idx] = { ...localReqs.value[idx], status: 'confirmed', changeRequest: false }
    if (activeReq.value?.id === r.id) activeReq.value = { ...activeReq.value, status: 'confirmed', changeRequest: false }
    // Close drawer if confirming from drawer (no event means it's from drawer button)
    if (!e) drawerOpen.value = false
    router.patch(route('gms.flights.status', r.id), { status: 'confirmed' }, {
        onSuccess: () => toast('Booking confirmed.'),
        onError:   () => toast('Failed to update.', 'error'),
        preserveScroll: true,
    })
}

function cancelBooking(r) {
    const idx = localReqs.value.findIndex(x => x.id === r.id)
    if (idx !== -1) localReqs.value[idx] = { ...localReqs.value[idx], status: 'cancelled' }
    if (activeReq.value?.id === r.id) activeReq.value = { ...activeReq.value, status: 'cancelled' }
    drawerOpen.value = false
    router.patch(route('gms.flights.status', r.id), { status: 'cancelled' }, {
        onSuccess: () => toast('Booking cancelled.'),
        onError:   () => toast('Failed to update.', 'error'),
        preserveScroll: true,
    })
}

function reinstateBooking(r) {
    const idx = localReqs.value.findIndex(x => x.id === r.id)
    if (idx !== -1) localReqs.value[idx] = { ...localReqs.value[idx], status: 'new', changeRequest: false }
    if (activeReq.value?.id === r.id) activeReq.value = { ...activeReq.value, status: 'new', changeRequest: false }
    drawerOpen.value = false
    router.patch(route('gms.flights.status', r.id), { status: 'new' }, {
        onSuccess: () => toast('Booking reinstated.'),
        onError:   () => toast('Failed to update.', 'error'),
        preserveScroll: true,
    })
}

// ── Edit leg modal ────────────────────────────────────────────────
const legModal    = ref(false)
const editingLeg  = ref(null) // 'inbound' | 'outbound'
const legForm = useForm({ airline: '', flightNo: '', class: 'Business', date: '', duration: '', time: '', arrivalTime: '' })

function openEditLeg(direction) {
    editingLeg.value = direction
    const r = activeReq.value
    if (direction === 'inbound') {
        legForm.airline     = r.airline ?? ''
        legForm.flightNo    = r.inboundFlight ?? r.flightNo ?? ''
        legForm.class       = r.class ?? 'Business'
        legForm.date        = r.date ?? ''
        legForm.duration    = r.duration ?? ''
        legForm.time        = r.time ?? ''
        legForm.arrivalTime = r.arrivalTime ?? ''
    } else {
        legForm.airline     = r.airline ?? ''
        legForm.flightNo    = r.outboundFlight ?? ''
        legForm.class       = r.class ?? 'Business'
        legForm.date        = r.outboundDate ?? ''
        legForm.duration    = r.duration ?? ''
        legForm.time        = r.outboundTime ?? ''
        legForm.arrivalTime = r.outboundArrivalTime ?? ''
    }
    legModal.value = true
}

function saveLeg() {
    const idx = localReqs.value.findIndex(x => x.id === activeReq.value.id)
    if (idx !== -1) {
        if (editingLeg.value === 'inbound') {
            Object.assign(localReqs.value[idx], {
                airline: legForm.airline, inboundFlight: legForm.flightNo, flightNo: legForm.flightNo,
                class: legForm.class, date: legForm.date, duration: legForm.duration,
                time: legForm.time, arrivalTime: legForm.arrivalTime,
            })
        } else {
            Object.assign(localReqs.value[idx], {
                airline: legForm.airline, outboundFlight: legForm.flightNo,
                class: legForm.class, outboundDate: legForm.date, duration: legForm.duration,
                outboundTime: legForm.time, outboundArrivalTime: legForm.arrivalTime,
            })
        }
        activeReq.value = { ...localReqs.value[idx] }
    }
    legModal.value = false
    toast('Leg updated.')
}

// ── New request modal ─────────────────────────────────────────────
const newModal = ref(false)
const form = useForm({
    guestId: '', flightNo: '', route: '', origin: '', destination: '',
    class: 'Business', pax: 1, date: '', time: '',
    outboundDate: '', outboundTime: '', airline: '', notes: '',
})

function saveNew() {
    const guest = props.guests.find(g => g.id === form.guestId)
    form.post(route('gms.flights.store'), {
        onSuccess: () => {
            localReqs.value.unshift({
                id: 'FLT-' + Date.now(), ...form.data(),
                pnr: Math.random().toString(36).slice(2, 8).toUpperCase(),
                guestName: guest?.name ?? form.guestId,
                status: 'new', changeRequest: false,
                arrival: '', arrivalTime: '',
            })
            newModal.value = false; toast('Flight request created.'); form.reset()
        },
        onError: () => toast('Failed to create.', 'error'),
        preserveScroll: true,
    })
}
</script>

<template>
  <div class="gms-view">

    <!-- Header -->
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Flights</h1>
        <p class="gms-view-subtitle">Inbound &amp; outbound air travel for international guests of {{ event?.name ?? 'this event' }}.</p>
      </div>
      <div class="gms-view-actions">
        <GmsBtn variant="ghost" icon="download" :iconSize="14">Export</GmsBtn>
        <GmsBtn variant="primary" icon="plus" :iconSize="14" @click="newModal = true">New flight request</GmsBtn>
      </div>
    </div>

    <!-- Tabs -->
    <div class="gms-seg" style="width:fit-content;margin-bottom:22px;">
      <button v-for="t in tabs" :key="t.key" :class="{ on: statusFilter === t.key }" @click="statusFilter = t.key">
        {{ t.label }}<span v-if="t.key !== 'all'" class="gms-seg-count">{{ countFor(t.key) }}</span>
      </button>
    </div>

    <!-- Stats -->
    <div class="gms-stats" style="margin-bottom:24px;">
      <GmsMiniStat :value="countFor('new')"       label="New requests"    color="#a16207" />
      <GmsMiniStat :value="countFor('change')"    label="Change requests" color="#2563eb" />
      <GmsMiniStat :value="countFor('confirmed')" label="Confirmed"       color="#15803d" />
      <GmsMiniStat :value="countFor('cancelled')" label="Cancelled"       />
    </div>

    <!-- Toolbar -->
    <div class="gms-toolbar">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search guest, PNR or flight…" />
      </div>
      <span class="mxt-count" style="margin-left:auto;">{{ filtered.length }} of {{ localReqs.length }}</span>
    </div>

    <!-- Table -->
    <div class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-table-wrap">
          <table class="gms-table">
            <thead>
              <tr>
                <th>Guest</th>
                <th>Itinerary</th>
                <th>Inbound</th>
                <th>Outbound</th>
                <th>Class</th>
                <th>Pax</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in filtered" :key="r.id" @click="openDrawer(r)" style="cursor:pointer;">
                <!-- Guest + PNR -->
                <td>
                  <div style="display:flex;align-items:center;gap:9px;">
                    <GmsAvatar :name="r.guestName" size="sm" />
                    <div>
                      <div style="font-weight:600;font-size:13px;line-height:1.2;">{{ r.guestName }}</div>
                      <div style="font-size:11px;font-family:var(--gms-font-mono);color:var(--gms-text-3);margin-top:2px;">{{ r.pnr }}</div>
                    </div>
                  </div>
                </td>
                <!-- Itinerary -->
                <td>
                  <span class="gms-small" style="font-family:var(--gms-font-mono);font-weight:600;letter-spacing:.3px;">
                    {{ r.origin }} ⇄ {{ r.destination }}
                  </span>
                </td>
                <!-- Inbound -->
                <td>
                  <span class="gms-small gms-mono">{{ fmtDate(r.date, r.time) }}</span>
                </td>
                <!-- Outbound -->
                <td>
                  <span class="gms-small gms-mono">{{ fmtDate(r.outboundDate, r.outboundTime) }}</span>
                </td>
                <!-- Class pill -->
                <td>
                  <span :class="classPillClass(r.class)">{{ r.class }}</span>
                </td>
                <!-- Pax -->
                <td><span class="gms-small">{{ r.pax }}</span></td>
                <!-- Status -->
                <td>
                  <GmsPill
                    type="custom"
                    :value="displayStatus(r).label"
                    :bg="displayStatus(r).bg"
                    :fg="displayStatus(r).fg"
                  />
                </td>
                <!-- Actions -->
                <td @click.stop>
                  <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                    <button
                      v-if="canConfirm(r)"
                      class="flt-action-btn"
                      title="Confirm booking"
                      @click="confirmRow(r, $event)"
                    >
                      <GmsIcon name="check" :size="13" />
                    </button>
                    <button class="flt-chevron-btn" title="View details" @click="openDrawer(r)">
                      <GmsIcon name="chevron-right" :size="15" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!filtered.length">
                <td colspan="8">
                  <div class="gms-empty"><div class="gms-empty-title">No flight requests</div></div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Detail drawer ── -->
  <GmsDrawer v-if="activeReq" :open="drawerOpen" @close="drawerOpen = false">

      <template #header>
        <div class="flt-dh" style="flex:1;min-width:0;">
          <GmsAvatar :name="activeReq.guestName" size="md" />
          <div class="flt-dh-info">
            <div class="flt-dh-name">
              {{ activeReq.guestName }}
              <GmsPill
                type="custom"
                :value="displayStatus(activeReq).label"
                :bg="displayStatus(activeReq).bg"
                :fg="displayStatus(activeReq).fg"
              />
            </div>
            <div class="flt-dh-meta">{{ activeReq.id }} · PNR {{ activeReq.pnr }} · {{ activeReq.pax }} {{ activeReq.pax === 1 ? 'pax' : 'pax' }} · {{ activeReq.class }}</div>
          </div>
        </div>
        <button class="gms-drawer-close" @click="drawerOpen = false">
          <GmsIcon name="x" :size="14" />
        </button>
      </template>

      <!-- Inbound leg -->
      <div class="flt-leg" style="margin-top:8px;">
        <div class="flt-leg-header">
          <div class="flt-leg-tag">
            <GmsIcon name="plane-land" :size="14" style="color:var(--gms-text-3);" />
            <span class="flt-leg-dir">Inbound</span>
            <span class="flt-leg-code">{{ activeReq.inboundFlight }}</span>
          </div>
          <span class="flt-leg-date">{{ fmtDateShort(activeReq.date) }}</span>
        </div>
        <div class="flt-leg-route">
          <div class="flt-leg-end">
            <div class="flt-leg-iata">{{ activeReq.origin }}</div>
            <div class="flt-leg-city">{{ activeReq.originCity }}</div>
            <div class="flt-leg-time">{{ activeReq.time }}</div>
          </div>
          <div class="flt-leg-mid">
            <span class="flt-leg-dur">{{ activeReq.duration }}</span>
            <div class="flt-leg-line">
              <div class="flt-leg-dash" />
              <div class="flt-leg-plane-ico"><GmsIcon name="plane-land" :size="14" /></div>
              <div class="flt-leg-dash" />
            </div>
            <span class="flt-leg-carrier">{{ activeReq.airline }}</span>
          </div>
          <div class="flt-leg-end right">
            <div class="flt-leg-iata">{{ activeReq.destination }}</div>
            <div class="flt-leg-city">{{ activeReq.destCity }}</div>
            <div class="flt-leg-time">{{ activeReq.arrivalTime }}</div>
          </div>
        </div>
        <div class="flt-leg-foot">
          <div style="display:flex;align-items:center;gap:8px;">
            <span :class="classPillClass(activeReq.class)">{{ activeReq.class }}</span>
            <span class="flt-leg-terminal">{{ activeReq.inboundTerminal }}</span>
          </div>
          <button class="flt-edit-btn" @click="openEditLeg('inbound')"><GmsIcon name="edit" :size="12" /> Edit</button>
        </div>
      </div>

      <!-- Outbound leg -->
      <div class="flt-leg">
        <div class="flt-leg-header">
          <div class="flt-leg-tag">
            <GmsIcon name="plane-depart" :size="14" style="color:var(--gms-text-3);" />
            <span class="flt-leg-dir">Outbound</span>
            <span class="flt-leg-code">{{ activeReq.outboundFlight }}</span>
          </div>
          <span class="flt-leg-date">{{ fmtDateShort(activeReq.outboundDate) }}</span>
        </div>
        <div class="flt-leg-route">
          <div class="flt-leg-end">
            <div class="flt-leg-iata">{{ activeReq.destination }}</div>
            <div class="flt-leg-city">{{ activeReq.destCity }}</div>
            <div class="flt-leg-time">{{ activeReq.outboundTime }}</div>
          </div>
          <div class="flt-leg-mid">
            <span class="flt-leg-dur">{{ activeReq.duration }}</span>
            <div class="flt-leg-line">
              <div class="flt-leg-dash" />
              <div class="flt-leg-plane-ico"><GmsIcon name="plane-depart" :size="14" /></div>
              <div class="flt-leg-dash" />
            </div>
            <span class="flt-leg-carrier">{{ activeReq.airline }}</span>
          </div>
          <div class="flt-leg-end right">
            <div class="flt-leg-iata">{{ activeReq.origin }}</div>
            <div class="flt-leg-city">{{ activeReq.originCity }}</div>
            <div class="flt-leg-time">{{ activeReq.outboundArrivalTime }}</div>
          </div>
        </div>
        <div class="flt-leg-foot">
          <div style="display:flex;align-items:center;gap:8px;">
            <span :class="classPillClass(activeReq.class)">{{ activeReq.class }}</span>
            <span class="flt-leg-terminal">{{ activeReq.outboundTerminal }}</span>
          </div>
          <button class="flt-edit-btn" @click="openEditLeg('outbound')"><GmsIcon name="edit" :size="12" /> Edit</button>
        </div>
      </div>

      <!-- Request meta -->
      <div class="gms-section-title" style="margin-top:20px;">Request</div>
      <div class="gms-detail-row">
        <span class="gms-detail-label">Submitted</span>
        <span class="gms-detail-value gms-mono">{{ activeReq.submitted }}</span>
      </div>
      <div class="gms-detail-row">
        <span class="gms-detail-label">Passengers</span>
        <span class="gms-detail-value">{{ activeReq.pax }} {{ activeReq.pax === 1 ? '(guest only)' : 'passengers' }}</span>
      </div>
      <div v-if="guestTier(activeReq)" class="gms-detail-row">
        <span class="gms-detail-label">Service level</span>
        <GmsPill
          type="custom"
          :value="guestTier(activeReq).name"
          :bg="guestTier(activeReq).bg"
          :fg="guestTier(activeReq).color"
        />
      </div>
      <div v-if="activeReq.notes" class="flt-req-note">{{ activeReq.notes }}</div>

    <template #footer>
      <GmsBtn variant="ghost" icon="user" :iconSize="13">Guest profile</GmsBtn>
      <GmsBtn v-if="canConfirm(activeReq)" variant="ghost" icon="x" :iconSize="13" @click="cancelBooking(activeReq)">Cancel</GmsBtn>
      <GmsBtn v-if="canConfirm(activeReq)" variant="primary" icon="check" :iconSize="13" @click="confirmRow(activeReq)">Confirm booking</GmsBtn>
      <GmsBtn v-if="!canConfirm(activeReq)" variant="primary" icon="refresh-cw" :iconSize="13" @click="reinstateBooking(activeReq)">Re-instate</GmsBtn>
    </template>
  </GmsDrawer>

  <!-- ── New request modal ── -->
  <GmsModal :open="newModal" title="New Flight Request" @close="newModal = false">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="gms-field">
        <label class="gms-label">Guest</label>
        <select v-model="form.guestId" class="gms-input gms-select">
          <option value="">— Select —</option>
          <option v-for="g in guests" :key="g.id" :value="g.id">{{ g.name }}</option>
        </select>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Inbound flight no.</label>
          <input v-model="form.flightNo" class="gms-input" placeholder="QR 2025" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Route</label>
          <input v-model="form.route" class="gms-input" placeholder="CDG → DOH" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Airline</label>
          <input v-model="form.airline" class="gms-input" placeholder="Qatar Airways" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Class</label>
          <select v-model="form.class" class="gms-input gms-select">
            <option>First</option><option>Business</option><option>Economy</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Passengers</label>
          <input v-model.number="form.pax" type="number" min="1" class="gms-input" />
        </div>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Inbound date</label>
          <input v-model="form.date" type="date" class="gms-input" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Inbound time</label>
          <input v-model="form.time" type="time" class="gms-input" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Outbound date</label>
          <input v-model="form.outboundDate" type="date" class="gms-input" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Outbound time</label>
          <input v-model="form.outboundTime" type="time" class="gms-input" />
        </div>
      </div>
      <div class="gms-field">
        <label class="gms-label">Notes</label>
        <textarea v-model="form.notes" class="gms-input gms-textarea" rows="2" />
      </div>
    </div>
    <template #footer>
      <GmsBtn variant="ghost" @click="newModal = false">Cancel</GmsBtn>
      <GmsBtn variant="primary" :disabled="form.processing" @click="saveNew">Create request</GmsBtn>
    </template>
  </GmsModal>

  <!-- ── Edit leg modal ── -->
  <GmsModal :open="legModal" :title="editingLeg === 'inbound' ? 'Edit inbound leg' : 'Edit outbound leg'" @close="legModal = false">
    <template v-if="activeReq">
      <!-- Route subtitle -->
      <p style="font-size:13px;color:var(--gms-text-3);margin-bottom:20px;">
        <template v-if="editingLeg === 'inbound'">{{ activeReq.origin }} → {{ activeReq.destination }} · {{ activeReq.originCity }} to {{ activeReq.destCity }}</template>
        <template v-else>{{ activeReq.destination }} → {{ activeReq.origin }} · {{ activeReq.destCity }} to {{ activeReq.originCity }}</template>
      </p>

      <!-- Airline -->
      <div class="gms-field" style="margin-bottom:14px;">
        <label class="gms-label">Airline</label>
        <input v-model="legForm.airline" class="gms-input" placeholder="Qatar Airways" />
      </div>

      <!-- Flight number + Cabin class -->
      <div class="gms-form-grid" style="margin-bottom:14px;">
        <div class="gms-field">
          <label class="gms-label">Flight number *</label>
          <input v-model="legForm.flightNo" class="gms-input" placeholder="QR9" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Cabin class</label>
          <div class="gms-seg" style="width:100%;">
            <button v-for="c in ['Economy','Business','First']" :key="c" :class="{ on: legForm.class === c }" type="button" @click="legForm.class = c">{{ c }}</button>
          </div>
        </div>
      </div>

      <!-- Date + Duration -->
      <div class="gms-form-grid" style="margin-bottom:14px;">
        <div class="gms-field">
          <label class="gms-label">Date *</label>
          <input v-model="legForm.date" class="gms-input" placeholder="23 Aug" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Duration</label>
          <input v-model="legForm.duration" class="gms-input" placeholder="6h 40m" />
        </div>
      </div>

      <!-- Departure + Arrival -->
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Departure *</label>
          <input v-model="legForm.time" class="gms-input" placeholder="08:15" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Arrival *</label>
          <input v-model="legForm.arrivalTime" class="gms-input" placeholder="13:30" />
        </div>
      </div>
      <p style="font-size:11.5px;color:var(--gms-text-3);margin-top:8px;">Use <code>+1</code> after a time to mark next-day arrival.</p>
    </template>

    <template #footer>
      <GmsBtn variant="ghost" @click="legModal = false">Cancel</GmsBtn>
      <GmsBtn variant="primary" :disabled="!legForm.flightNo" @click="saveLeg">Save leg</GmsBtn>
    </template>
  </GmsModal>
</template>
