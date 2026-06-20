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
import GmsGuestPicker from '@/Components/Gms/GmsGuestPicker.vue'
import GmsDatePicker from '@/Components/Gms/GmsDatePicker.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    requests: { type: Array,  default: () => [] },
    guests:   { type: Array,  default: () => [] },
    tiers:    { type: Array,  default: () => [] },
    event:    { type: Object, default: () => ({}) },
})

const toast = inject('toast')
const localReqs = ref(props.requests.map(r => ({ ...r })))
const search       = ref('')
const statusFilter = ref('all')
const statuses     = ['all', 'confirmed', 'pending', 'cancelled']
const statusColors = {
    confirmed: { bg: '#dcfce7', fg: '#15803d' },
    pending:   { bg: '#fef9c3', fg: '#a16207' },
    cancelled: { bg: '#f3f4f6', fg: '#6b7280' },
    new:       { bg: '#e2edf3', fg: '#3a6a8a' },
}

const filtered = computed(() => {
    let list = localReqs.value
    if (statusFilter.value !== 'all') list = list.filter(r => r.status === statusFilter.value)
    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(r => r.guestName.toLowerCase().includes(q) || r.type.toLowerCase().includes(q) || r.vehicle.toLowerCase().includes(q))
    }
    return list
})

function countFor(s) {
    if (s === 'all') return localReqs.value.length
    return localReqs.value.filter(r => r.status === s).length
}

// Format datetime as "20 Jun 26, 14:41"
function formatDateTime(dateStr) {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    const day = d.getDate().toString().padStart(2, '0')
    const month = d.toLocaleDateString('en-GB', { month: 'short' })
    const year = d.getFullYear().toString().slice(-2)
    const time = d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false })
    return `${day} ${month} ${year}, ${time}`
}

const drawerOpen = ref(false)
const activeReq  = ref(null)
function openDrawer(r) { activeReq.value = r; drawerOpen.value = true }

function changeStatus(id, status) {
    const idx = localReqs.value.findIndex(r => r.id === id)
    if (idx !== -1) {
        localReqs.value = localReqs.value.map((r, i) => 
            i === idx ? { ...r, status } : r
        )
    }
    router.patch(route('gms.transport.status', id), { status }, { preserveScroll: true })
    toast(`Status updated to ${status}.`)
}

function deleteReq(id) {
    localReqs.value = localReqs.value.filter(r => r.id !== id)
    if (activeReq.value?.id === id) drawerOpen.value = false
    router.delete(route('gms.transport.destroy', id), { preserveScroll: true })
    toast('Request deleted.')
}

// ── Tab state ────────────────────────────────────────────────────
const currentTab = ref('requests') // 'requests' | 'movements'

const newModal = ref(false)
const selectedGuestId = ref(null)
const form = useForm({ guestId:'', type:'VIP Transfer', vehicle:'', pickupLocation:'', dropoffLocation:'', datetime:'', notes:'' })

// Sort guests: international first
const sortedGuestList = computed(() => {
    return props.guests.slice().sort((a, b) => {
        const aIntl = a.guestType === 'international' ? 0 : 1
        const bIntl = b.guestType === 'international' ? 0 : 1
        return aIntl - bIntl
    })
})

// Guest IDs that already have transport
const existingTransportGuestIds = computed(() => {
    return localReqs.value
        .filter(r => r.status !== 'cancelled')
        .map(r => r.guestId)
})

// Get guest details for active request
const activeGuest = computed(() => {
    if (!activeReq.value) return null
    return props.guests.find(g => g.id === activeReq.value.guestId)
})

function pickGuest(guest) {
    selectedGuestId.value = guest.id
    form.guestId = guest.id
    form.clearErrors('guestId')
}

const isRefreshing = ref(false)

function refreshRequests() {
    isRefreshing.value = true
    router.reload({
        only: ['requests'],
        onFinish: () => {
            setTimeout(() => {
                isRefreshing.value = false
                localReqs.value = props.requests.map(r => ({ ...r }))
                toast('Requests refreshed')
            }, 500)
        }
    })
}

function saveNew() {
    const guest = props.guests.find(g => g.id === form.guestId)
    form.post(route('gms.transport.store'), {
        onSuccess: () => {
            localReqs.value.unshift({ id:'TRN-'+Date.now(), ...form.data(), guestName: guest?.name ?? '', driver:'TBD', status:'pending' })
            newModal.value = false
            selectedGuestId.value = null
            form.reset()
            form.type = 'VIP Transfer'
            form.clearErrors()
            toast('Transport request created.')
        },
        onError: (errors) => {
            toast('Please check the form for errors.', 'error')
        },
        preserveScroll: true,
    })
}

// ── Movements plan ──────────────────────────────────────────────
const mvSearch = ref('')
const mvSelectedGuestId = ref(null)

function mvKind(label, from, to) {
    const s = ((label || '') + ' ' + (to || '') + ' ' + (from || '')).toLowerCase()
    if (/stadium|match|tribune/.test(s)) return { icon: 'trophy', accent: 'var(--gms-maroon)', tag: 'Match transfer' }
    if (/pickup|arriv/.test(s))          return { icon: 'plane', accent: 'var(--gms-info, #3a6a8a)', tag: 'Arrival pickup' }
    if (/depart|drop|return/.test(s))    return { icon: 'plane', accent: 'var(--gms-gold, #a9844a)', tag: 'Departure' }
    if (/hotel|check|residence/.test(s)) return { icon: 'building', accent: 'var(--gms-good, #3f7d52)', tag: 'Hotel transfer' }
    if (/dinner|gala|banquet|event/.test(s)) return { icon: 'mail', accent: '#7a5a8a', tag: 'Event transfer' }
    return { icon: 'car', accent: 'var(--gms-text-2)', tag: 'Transfer' }
}

function parseMvDT(dateStr, timeStr) {
    if (!dateStr) return new Date(0)
    const parts = dateStr.match(/(\d+)\s+(\w+)/)
    if (parts) {
        const months = { Jan:0,Feb:1,Mar:2,Apr:3,May:4,Jun:5,Jul:6,Aug:7,Sep:8,Oct:9,Nov:10,Dec:11 }
        const [hh, mm] = (timeStr || '00:00').split(':').map(Number)
        return new Date(2026, months[parts[2]] ?? 0, parseInt(parts[1]), hh || 0, mm || 0)
    }
    const d = new Date(dateStr + (timeStr ? ' ' + timeStr : ''))
    return isNaN(d.getTime()) ? new Date(0) : d
}

const mvGuestRequests = computed(() => {
    const active = localReqs.value.filter(r => r.status !== 'cancelled')
    const byGuest = {}
    for (const r of active) {
        if (!byGuest[r.guestId]) {
            const g = props.guests.find(x => x.id === r.guestId) || { id: r.guestId, name: r.guestName, tier: '' }
            byGuest[r.guestId] = { guest: g, requests: [], vehicle: r.vehicle, driver: r.driver || 'Unassigned', status: r.status, plate: '' }
        }
        byGuest[r.guestId].requests.push(r)
    }
    return Object.values(byGuest)
})

const mvRail = computed(() => {
    const q = mvSearch.value.toLowerCase()
    return mvGuestRequests.value.filter(({ guest, vehicle, driver }) => {
        if (!q) return true
        return guest.name.toLowerCase().includes(q) || (vehicle || '').toLowerCase().includes(q) || (driver || '').toLowerCase().includes(q)
    })
})

const mvSelected = computed(() => {
    if (!mvSelectedGuestId.value) return null
    return mvGuestRequests.value.find(x => x.guest.id === mvSelectedGuestId.value) || null
})

const mvMovements = computed(() => {
    if (!mvSelected.value) return []
    return mvSelected.value.requests.map((r, idx) => {
        const dt = new Date(r.datetime)
        const kind = mvKind(r.type + ' ' + (r.pickupLocation || ''), r.pickupLocation, r.dropoffLocation)
        const time = !isNaN(dt.getTime()) ? dt.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false }) : ''
        return { ...r, idx, dt, kind, time, from: r.pickupLocation, to: r.dropoffLocation, label: r.type }
    }).sort((a, b) => a.dt - b.dt)
})

const mvDays = computed(() => {
    const days = []
    const byKey = {}
    for (const m of mvMovements.value) {
        const k = m.dt.toDateString()
        if (!byKey[k]) {
            const day = m.dt.getDate().toString().padStart(2, '0')
            const month = m.dt.toLocaleDateString('en-GB', { month: 'short' })
            const year = m.dt.getFullYear().toString().slice(-2)
            byKey[k] = { key: k, dt: m.dt, date: `${day} ${month} ${year}`, items: [] }
            days.push(byKey[k])
        }
        byKey[k].items.push(m)
    }
    return days
})

const mvSpan = computed(() => {
    const m = mvMovements.value
    if (!m.length) return '—'
    const formatDate = (dt) => {
        const day = dt.getDate().toString().padStart(2, '0')
        const month = dt.toLocaleDateString('en-GB', { month: 'short' })
        const year = dt.getFullYear().toString().slice(-2)
        return `${day} ${month} ${year}`
    }
    const first = formatDate(m[0].dt)
    const last = formatDate(m[m.length - 1].dt)
    return first === last ? first : `${first} – ${last}`
})

const addMovementModal = ref(false)
const mvForm = useForm({ label: '', from: '', to: '', datetime: '' })

function saveMovement() {
    if (!mvSelected.value) return
    const guest = mvSelected.value.guest
    mvForm.post(route('gms.transport.store'), {
        data: { guestId: guest.id, type: mvForm.label, vehicle: mvSelected.value.vehicle || '', pickupLocation: mvForm.from, dropoffLocation: mvForm.to, datetime: mvForm.datetime, notes: '' },
        onSuccess: () => {
            localReqs.value.unshift({
                id: 'TRN-' + Date.now(), guestId: guest.id, guestName: guest.name,
                status: 'pending', type: mvForm.label, vehicle: mvSelected.value.vehicle || '',
                pickupLocation: mvForm.from, dropoffLocation: mvForm.to, datetime: mvForm.datetime,
                driver: mvSelected.value.driver || 'TBD', notes: ''
            })
            addMovementModal.value = false
            mvForm.reset()
            toast(`Movement added to ${guest.name}'s plan.`)
        },
        preserveScroll: true,
    })
}

function removeMovement(reqId) {
    localReqs.value = localReqs.value.filter(r => r.id !== reqId)
    router.delete(route('gms.transport.destroy', reqId), { preserveScroll: true })
    toast('Movement removed.')
}

function tierForGuest(guestId) {
    const g = props.guests.find(x => x.id === guestId)
    return g?.tier || ''
}
</script>

<template>
  <div>
  <div class="gms-view">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Transport</h1>
        <p class="gms-view-subtitle">Ground transport &amp; chauffeur assignments for {{ props.event?.name || 'event' }}.</p>
      </div>
      <div class="gms-view-actions">
        <button
          class="gms-btn gms-btn-ghost gms-btn-sm"
          @click="refreshRequests"
          :disabled="isRefreshing"
          title="Refresh requests"
          style="margin-right: 8px;"
        >
          <GmsIcon name="refresh-cw" :size="14" :class="{ 'gms-spin': isRefreshing }" />
        </button>
        <template v-if="currentTab === 'movements'">
          <button class="gms-btn gms-btn-ghost" @click="toast(mvSelected ? `${mvSelected.guest.name}'s movement order exported to PDF` : 'Movement orders exported')">
            <GmsIcon name="download" :size="14" /> Export plan
          </button>
          <button class="gms-btn gms-btn-primary" :disabled="!mvSelected" @click="addMovementModal = true">
            <GmsIcon name="plus" :size="14" /> Add movement
          </button>
        </template>
        <template v-else>
          <button class="gms-btn gms-btn-primary" @click="newModal = true">
            <GmsIcon name="plus" :size="14" /> New Request
          </button>
        </template>
      </div>
    </div>

    <!-- Stats -->
    <div class="gms-stats" style="margin-bottom:24px;">
      <GmsMiniStat :value="countFor('confirmed')" label="Confirmed" color="#3f7d52" />
      <GmsMiniStat :value="countFor('pending')" label="Pending" color="#a16207" />
      <GmsMiniStat :value="countFor('cancelled')" label="Cancelled" />
    </div>

    <!-- Tab selector -->
    <div class="gms-seg" style="width: fit-content; margin-bottom: 20px;">
      <button :class="{ on: currentTab === 'requests' }" @click="currentTab = 'requests'">
        <GmsIcon name="list" :size="14" style="margin-right: 6px;" /> Requests
      </button>
      <button :class="{ on: currentTab === 'movements' }" @click="currentTab = 'movements'">
        <GmsIcon name="calendar" :size="14" style="margin-right: 6px;" /> Movements plan
      </button>
    </div>

    <!-- Requests view -->
    <div v-if="currentTab === 'requests'">
    <div class="gms-toolbar">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search guest, vehicle…" />
      </div>
      <div class="gms-seg">
        <button v-for="s in statuses" :key="s" :class="{ on: statusFilter===s }" @click="statusFilter=s">
          {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
          <span class="gms-seg-count" v-if="countFor(s) > 0">{{ countFor(s) }}</span>
        </button>
      </div>
      
      <span class="mxt-count" style="margin-left: auto;">{{ filtered.length }} of {{ localReqs.length }}</span>
    </div>

    <div class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-table-wrap">
          <table class="gms-table">
            <thead>
              <tr><th>Guest</th><th>Type</th><th>Vehicle</th><th>From</th><th>To</th><th>Date/Time</th><th>Driver</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="r in filtered" :key="r.id" @click="openDrawer(r)">
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <GmsAvatar :name="r.guestName" size="sm" />
                    <span style="font-weight:600;font-size:13px;">{{ r.guestName }}</span>
                  </div>
                </td>
                <td><span class="gms-small">{{ r.type }}</span></td>
                <td><span class="gms-small gms-muted">{{ r.vehicle }}</span></td>
                <td><span class="gms-small gms-truncate" style="max-width:120px;display:block;">{{ r.pickupLocation }}</span></td>
                <td><span class="gms-small gms-truncate" style="max-width:120px;display:block;">{{ r.dropoffLocation }}</span></td>
                <td><span class="gms-small gms-mono">{{ formatDateTime(r.datetime) }}</span></td>
                <td><span class="gms-small gms-muted">{{ r.driver }}</span></td>
                <td><GmsPill type="custom" :value="r.status" :bg="statusColors[r.status]?.bg ?? '#f3f4f6'" :fg="statusColors[r.status]?.fg ?? '#374151'" /></td>
                <td @click.stop>
                  <button class="gms-btn gms-btn-danger gms-btn-sm gms-btn-icon" @click="deleteReq(r.id)"><GmsIcon name="trash" :size="13" /></button>
                </td>
              </tr>
              <tr v-if="!filtered.length"><td colspan="9"><div class="gms-empty"><div class="gms-empty-title">No requests</div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    </div>

    <!-- Movements plan view -->
    <div v-if="currentTab === 'movements'">
      <div class="gms-mv-wrap">
        <!-- Left rail: guests with transport -->
        <aside class="gms-mv-rail">
          <div class="gms-mv-rail-search">
            <div class="gms-search-wrap">
              <GmsIcon name="search" :size="13" class="gms-search-icon" />
              <input v-model="mvSearch" class="gms-search-input" placeholder="Search guest, vehicle or driver…" style="font-size:13px;padding:8px 10px 8px 32px;" />
            </div>
          </div>
          <div class="gms-mv-rail-list">
            <button
              v-for="entry in mvRail"
              :key="entry.guest.id"
              type="button"
              :class="['gms-mv-rail-item', { on: mvSelectedGuestId === entry.guest.id }]"
              @click="mvSelectedGuestId = entry.guest.id"
            >
              <GmsAvatar :name="entry.guest.name" size="sm" />
              <div style="flex:1;min-width:0;text-align:left;">
                <div class="gms-mv-ri-name">{{ entry.guest.name }}</div>
                <div class="gms-mv-ri-sub">{{ entry.vehicle }} · {{ entry.requests.length }} movement{{ entry.requests.length !== 1 ? 's' : '' }}</div>
              </div>
              <GmsPill type="custom" :value="entry.status" :bg="statusColors[entry.status]?.bg ?? '#f3f4f6'" :fg="statusColors[entry.status]?.fg ?? '#374151'" />
            </button>
            <div v-if="mvRail.length === 0" class="gms-muted" style="font-size:12.5px;padding:16px;text-align:center;">
              No transport guests match "{{ mvSearch }}".
            </div>
          </div>
        </aside>

        <!-- Right: movement plan -->
        <div v-if="!mvSelected" class="gms-mv-plan gms-mv-empty-plan">
          <div class="gms-empty" style="padding:60px 20px;">
            <div class="gms-empty-title">Select a guest</div>
            <div class="gms-muted" style="font-size:13px;margin-top:8px;">Choose a guest from the list to view their movement plan.</div>
          </div>
        </div>
        <section v-else class="gms-mv-plan">
          <!-- Guest header -->
          <div class="gms-mv-head">
            <GmsAvatar :name="mvSelected.guest.name" size="lg" />
            <div style="flex:1;min-width:0;">
              <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap;">
                <h3 class="gms-mv-guest-name">{{ mvSelected.guest.name }}</h3>
                <GmsPill v-if="mvSelected.guest.tier" type="tier" :value="mvSelected.guest.tier" :tiers="props.tiers" />
                <GmsPill type="custom" :value="mvSelected.status" :bg="statusColors[mvSelected.status]?.bg" :fg="statusColors[mvSelected.status]?.fg" />
              </div>
              <div class="gms-mv-head-meta">
                <span class="gms-mv-chip"><GmsIcon name="car" :size="13" /> {{ mvSelected.vehicle }}</span>
                <span class="gms-mv-chip">
                  <template v-if="mvSelected.driver === 'Unassigned' || mvSelected.driver === 'TBD'"><em class="gms-mv-unassigned">Driver unassigned</em></template>
                  <template v-else>{{ mvSelected.driver }}</template>
                </span>
              </div>
            </div>
            <div class="gms-mv-head-actions">
              <div class="gms-mv-counters">
                <span><b>{{ mvMovements.length }}</b> movements</span>
                <span class="gms-mv-dot"></span>
                <span><b>{{ mvDays.length }}</b> day{{ mvDays.length !== 1 ? 's' : '' }}</span>
                <span class="gms-mv-dot"></span>
                <span class="gms-muted">{{ mvSpan }}</span>
              </div>
            </div>
          </div>

          <!-- Movement days -->
          <div v-if="mvMovements.length === 0" class="gms-empty" style="margin:20px 0;">
            <div class="gms-empty-title">No movements yet</div>
            <div class="gms-muted" style="font-size:13px;margin-top:6px;">Add the first leg of {{ mvSelected.guest.name.split(' ')[0] }}'s plan.</div>
          </div>
          <div v-else class="gms-mv-days">
            <div v-for="(d, di) in mvDays" :key="d.key" class="gms-mv-day">
              <div class="gms-mv-day-h">
                <span class="gms-mv-day-n">{{ di + 1 }}</span>
                <span class="gms-mv-day-wd">{{ d.dt.toLocaleDateString('en-GB', { weekday: 'long' }) }}</span>
                <span class="gms-mv-day-date">{{ d.date }}</span>
                <span class="gms-mv-day-line"></span>
                <span class="gms-muted gms-mv-day-cnt">{{ d.items.length }} movement{{ d.items.length !== 1 ? 's' : '' }}</span>
              </div>
              <div class="gms-mv-legs">
                <div v-for="m in d.items" :key="m.id" class="gms-mv-leg">
                  <div class="gms-mv-time"><span class="gms-mono">{{ m.time }}</span></div>
                  <div class="gms-mv-spine">
                    <span class="gms-mv-node" :style="{ borderColor: m.kind.accent, color: m.kind.accent }">
                      <GmsIcon :name="m.kind.icon" :size="15" />
                    </span>
                  </div>
                  <div class="gms-mv-card">
                    <div class="gms-mv-card-top">
                      <span class="gms-mv-tag" :style="{ color: m.kind.accent, background: `color-mix(in srgb, ${m.kind.accent} 12%, transparent)` }">{{ m.kind.tag }}</span>
                      <span class="gms-mv-card-lbl">{{ m.label }}</span>
                      <button class="gms-mv-del" title="Remove movement" @click="removeMovement(m.id)">
                        <GmsIcon name="x" :size="14" />
                      </button>
                    </div>
                    <div class="gms-mv-route">
                      <span class="gms-mv-pt">{{ m.from }}</span>
                      <span class="gms-mv-arrow"><GmsIcon name="chevron-right" :size="14" /></span>
                      <span class="gms-mv-pt gms-mv-pt-to">{{ m.to }}</span>
                    </div>
                    <div class="gms-mv-card-meta">
                      <span><GmsIcon name="car" :size="12" /> {{ mvSelected.vehicle }}</span>
                      <span class="gms-mv-dot"></span>
                      <span v-if="mvSelected.driver === 'Unassigned' || mvSelected.driver === 'TBD'"><em class="gms-mv-unassigned">Unassigned</em></span>
                      <span v-else>{{ mvSelected.driver }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="gms-mv-foot">
            <button class="gms-btn gms-btn-ghost" @click="openDrawer(mvSelected.requests[0])">
              <GmsIcon name="users" :size="13" /> Guest profile
            </button>
            <button
              v-if="mvSelected.status === 'confirmed'"
              class="gms-btn gms-btn-ghost"
              style="margin-left:auto;"
              @click="toast(`Itinerary sent to ${mvSelected.guest.name}'s driver`)"
            >
              <GmsIcon name="mail" :size="13" /> Notify driver
            </button>
          </div>
        </section>
      </div>

      <!-- Add movement modal -->
      <GmsModal :open="addMovementModal" title="Add movement" @close="addMovementModal = false; mvForm.reset()">
        <template v-if="mvSelected">
          <div class="gms-muted" style="font-size:12.5px;margin-bottom:14px;">for {{ mvSelected.guest.name }}</div>
          <div style="display:flex;flex-direction:column;gap:14px;">
            <div class="gms-field">
              <label class="gms-label">Movement</label>
              <input v-model="mvForm.label" class="gms-input" placeholder="e.g. Match transfer" />
            </div>
            <div class="gms-form-grid">
              <div class="gms-field">
                <label class="gms-label">From</label>
                <input v-model="mvForm.from" class="gms-input" placeholder="Pickup location" />
              </div>
              <div class="gms-field">
                <label class="gms-label">To</label>
                <input v-model="mvForm.to" class="gms-input" placeholder="Drop-off location" />
              </div>
              <div class="gms-field gms-form-full">
                <label class="gms-label">Date &amp; Time</label>
                <GmsDatePicker
                  v-model="mvForm.datetime"
                  placeholder="Select date and time"
                  date-format="d/m/Y H:i"
                  :enable-time="true"
                />
              </div>
            </div>
          </div>
        </template>
        <template #footer>
          <button class="gms-btn gms-btn-ghost" @click="addMovementModal = false; mvForm.reset()">Cancel</button>
          <button class="gms-btn gms-btn-primary" :disabled="!mvForm.label || !mvForm.from || !mvForm.to || !mvForm.datetime" @click="saveMovement">Add movement</button>
        </template>
      </GmsModal>
    </div>
  </div>

  <GmsDrawer :open="drawerOpen" :title="activeReq?.type ?? ''" :subtitle="activeReq?.guestName" @close="drawerOpen = false">
    <template v-if="activeReq">
      <div style="display:flex;gap:8px;margin-bottom:16px;">
        <GmsPill type="custom" :value="activeReq.status" :bg="statusColors[activeReq.status]?.bg" :fg="statusColors[activeReq.status]?.fg" />
      </div>

      <div class="gms-sec">
        <div class="gms-sec-h">Guest Details</div>
        <div class="gms-kv">
          <span class="gms-k">Service level</span>
          <span class="gms-v">{{ activeGuest?.tier || '—' }}</span>
        </div>
        <div class="gms-kv">
          <span class="gms-k">Group</span>
          <span class="gms-v">{{ activeGuest?.group || '—' }}</span>
        </div>
        <div class="gms-kv">
          <span class="gms-k">Hosted by</span>
          <span class="gms-v">{{ activeGuest?.host || '—' }}</span>
        </div>
      </div>
      
      <div class="gms-sec">
        <div class="gms-sec-h">Vehicle & Driver</div>
        <div class="gms-kv">
          <span class="gms-k">Vehicle</span>
          <span class="gms-v">{{ activeReq.vehicle || 'Not assigned' }}</span>
        </div>
        <div class="gms-kv">
          <span class="gms-k">Driver</span>
          <span class="gms-v">{{ activeReq.driver || 'TBD' }}</span>
        </div>
      </div>

      <div class="gms-sec">
        <div class="gms-sec-h">Movement Details</div>
        <div class="gms-tr-trips">
          <div class="gms-tr-trip">
            <span class="gms-tr-dot"></span>
            <div style="flex:1;min-width:0;">
              <div class="gms-tr-lbl">{{ activeReq.type }}</div>
              <div class="gms-tr-route">{{ activeReq.pickupLocation }} → {{ activeReq.dropoffLocation }}</div>
            </div>
            <div class="gms-tr-when">
              <span class="gms-tr-time">{{ formatDateTime(activeReq.datetime) }}</span>
            </div>
          </div>
        </div>
        <div v-if="activeReq.notes" style="margin-top:12px;padding-top:12px;border-top:1px solid var(--gms-border);">
          <div class="gms-k" style="margin-bottom:6px;">Notes</div>
          <div class="gms-v" style="font-weight:400;font-size:12.5px;line-height:1.5;">{{ activeReq.notes }}</div>
        </div>
      </div>
    </template>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="changeStatus(activeReq.id,'confirmed'); drawerOpen=false">
        <GmsIcon name="check" :size="13" /> Confirm
      </button>
      <button class="gms-btn gms-btn-danger gms-btn-sm" @click="changeStatus(activeReq.id,'cancelled'); drawerOpen=false">Cancel</button>
    </template>
  </GmsDrawer>

  <GmsModal :open="newModal" title="New Transport Request" @close="newModal = false; selectedGuestId = null; form.reset(); form.clearErrors()">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="gms-field">
        <label class="gms-label">Guest</label>
        <GmsGuestPicker
          :guests="sortedGuestList"
          :selected-guest-id="selectedGuestId"
          :tiers="props.tiers"
          :show-existing-indicator="true"
          :existing-guest-ids="existingTransportGuestIds"
          existing-label="has transport"
          @select="pickGuest"
        />
        <span v-if="form.errors.guestId" class="gms-error" style="display: block; margin-top: 6px; font-size: 12px; color: #dc2626;">{{ form.errors.guestId }}</span>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Transfer Type</label>
          <select v-model="form.type" :class="['gms-input gms-select', { 'gms-input-error': form.errors.type }]" @change="form.clearErrors('type')">
            <option>VIP Transfer</option><option>VIP Escort</option><option>Motorcade</option><option>Private Car</option><option>Royal Convoy</option>
          </select>
          <span v-if="form.errors.type" class="gms-error" style="display: block; margin-top: 4px; font-size: 12px; color: #dc2626;">{{ form.errors.type }}</span>
        </div>
        <div class="gms-field">
          <label class="gms-label">Vehicle</label>
          <input v-model="form.vehicle" :class="['gms-input', { 'gms-input-error': form.errors.vehicle }]" placeholder="Mercedes S-Class" @input="form.clearErrors('vehicle')" />
          <span v-if="form.errors.vehicle" class="gms-error" style="display: block; margin-top: 4px; font-size: 12px; color: #dc2626;">{{ form.errors.vehicle }}</span>
        </div>
        <div class="gms-field gms-form-full">
          <label class="gms-label">Pick-up Location</label>
          <input v-model="form.pickupLocation" :class="['gms-input', { 'gms-input-error': form.errors.pickupLocation }]" placeholder="HIA Terminal 1" @input="form.clearErrors('pickupLocation')" />
          <span v-if="form.errors.pickupLocation" class="gms-error" style="display: block; margin-top: 4px; font-size: 12px; color: #dc2626;">{{ form.errors.pickupLocation }}</span>
        </div>
        <div class="gms-field gms-form-full">
          <label class="gms-label">Drop-off Location</label>
          <input v-model="form.dropoffLocation" :class="['gms-input', { 'gms-input-error': form.errors.dropoffLocation }]" placeholder="Four Seasons Doha" @input="form.clearErrors('dropoffLocation')" />
          <span v-if="form.errors.dropoffLocation" class="gms-error" style="display: block; margin-top: 4px; font-size: 12px; color: #dc2626;">{{ form.errors.dropoffLocation }}</span>
        </div>
        <div class="gms-field gms-form-full">
          <label class="gms-label">Date &amp; Time</label>
          <GmsDatePicker 
            v-model="form.datetime" 
            placeholder="Select date and time"
            date-format="d/m/Y H:i"
            :enable-time="true"
            :error="!!form.errors.datetime"
            @update:model-value="form.clearErrors('datetime')"
          />
          <span v-if="form.errors.datetime" class="gms-error" style="display: block; margin-top: 4px; font-size: 12px; color: #dc2626;">{{ form.errors.datetime }}</span>
        </div>
      </div>
      <div class="gms-field">
        <label class="gms-label">Notes</label>
        <textarea v-model="form.notes" :class="['gms-input gms-textarea', { 'gms-input-error': form.errors.notes }]" rows="2" @input="form.clearErrors('notes')" />
        <span v-if="form.errors.notes" class="gms-error" style="display: block; margin-top: 4px; font-size: 12px; color: #dc2626;">{{ form.errors.notes }}</span>
      </div>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="newModal = false; selectedGuestId = null; form.reset(); form.clearErrors()">Cancel</button>
      <button class="gms-btn gms-btn-primary" :disabled="form.processing" @click="saveNew">Create Request</button>
    </template>
  </GmsModal>
  </div>
</template>
