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
import GmsFilterDropdown from '@/Components/Gms/GmsFilterDropdown.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    requests:      { type: Array,  default: () => [] },
    guestRequests: { type: Array,  default: () => [] },
    vehicleBlocks: { type: Array,  default: () => [] },
    guests:        { type: Array,  default: () => [] },
    tiers:         { type: Array,  default: () => [] },
    event:         { type: Object, default: () => ({}) },
})

const toast = inject('toast')
const localReqs = ref(props.requests.map(r => ({ ...r })))
const search       = ref('')
const statusFilter = ref('all')
const sourceFilter = ref('all')
const statuses     = ['all', 'confirmed', 'pending', 'cancelled']

const sourceOptions = [
    { id: 'all', name: 'All Sources' },
    { id: 'portal', name: 'Portal' },
    { id: 'manual', name: 'Manual' },
    { id: 'phone', name: 'Phone' },
    { id: 'email', name: 'Email' },
]
const statusColors = {
    requested: { bg: '#ede9fe', fg: '#7c3aed' },
    confirmed: { bg: '#dcfce7', fg: '#15803d' },
    pending:   { bg: '#fef9c3', fg: '#a16207' },
    cancelled: { bg: '#f3f4f6', fg: '#6b7280' },
    new:       { bg: '#e2edf3', fg: '#3a6a8a' },
}

const filtered = computed(() => {
    let list = localReqs.value
    if (statusFilter.value !== 'all') list = list.filter(r => r.status === statusFilter.value)
    
    if (sourceFilter.value !== 'all') {
        list = list.filter(r => r.source === sourceFilter.value)
    }
    
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

// Filter guests who have confirmed invitations
const confirmedGuests = computed(() => {
    return sortedGuestList.value.filter(g => g.hasConfirmedInvitation)
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
        only: ['requests', 'guestRequests'],
        onFinish: () => {
            setTimeout(() => {
                isRefreshing.value = false
                localReqs.value = props.requests.map(r => ({ ...r }))
                localGuestReqs.value = props.guestRequests.map(r => ({ ...r }))
                toast('Requests refreshed')
            }, 500)
        }
    })
}

// ── Guest request inbox ──────────────────────────────────────────
const localGuestReqs = ref(props.guestRequests.map(r => ({ ...r })))
const pendingGuestRequests = computed(() => localGuestReqs.value.filter(r => !r.fulfilledById))
const fulfilledGuestRequests = computed(() => localGuestReqs.value.filter(r => r.fulfilledById))
const showFulfilled = ref(false)
const bookingFromGuestRequest = ref(null)

function bookFromRequest(r) {
    bookingFromGuestRequest.value = r
    selectedGuestId.value = r.guestId
    form.guestId = r.guestId
    form.type = r.type || 'VIP Transfer'
    form.pickupLocation = r.pickupLocation || ''
    form.dropoffLocation = r.dropoffLocation || ''
    form.datetime = r.datetime || ''
    form.notes = r.notes || ''
    form.vehicle = ''
    newModal.value = true
}

function saveNew() {
    const guest = props.guests.find(g => g.id === form.guestId)

    const targetRoute = bookingFromGuestRequest.value
        ? route('gms.transport.book-guest-request', bookingFromGuestRequest.value.id)
        : route('gms.transport.store')

    form.post(targetRoute, {
        onSuccess: () => {
            if (bookingFromGuestRequest.value) {
                const idx = localGuestReqs.value.findIndex(x => x.id === bookingFromGuestRequest.value.id)
                if (idx !== -1) localGuestReqs.value[idx] = { ...localGuestReqs.value[idx], fulfilledById: 'pending' }
                bookingFromGuestRequest.value = null
            }
            localReqs.value.unshift({ id:'TRN-'+Date.now(), ...form.data(), guestName: guest?.name ?? bookingFromGuestRequest?.value?.guestName ?? '', driver:'TBD', status:'pending' })
            newModal.value = false
            selectedGuestId.value = null
            form.reset()
            form.type = 'VIP Transfer'
            form.clearErrors()
            toast('Transport request created.')

            router.reload({
                only: ['requests', 'guestRequests'],
                preserveScroll: true,
                onSuccess: () => {
                    localReqs.value = props.requests.map(r => ({ ...r }))
                    localGuestReqs.value = props.guestRequests.map(r => ({ ...r }))
                },
            })
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

// ══════════════════════════════════════════════
// VEHICLE BLOCKS TAB
// ══════════════════════════════════════════════
const localBlocks = ref(props.vehicleBlocks.map(b => ({ ...b })))
const providerFilter = ref('all')

function daysBetween(a, b) {
    if (!a || !b) return 0
    return Math.round((new Date(b) - new Date(a)) / 86400000)
}

const blockProviders = computed(() => {
    const seen = new Set()
    return localBlocks.value.filter(b => {
        if (seen.has(b.provider)) return false
        seen.add(b.provider)
        return true
    }).map(b => b.provider)
})

const filteredBlocks = computed(() => {
    if (providerFilter.value === 'all') return localBlocks.value
    return localBlocks.value.filter(b => b.provider === providerFilter.value)
})

const blockStats = computed(() => {
    const blocks = localBlocks.value
    const fleet = blocks.reduce((s, b) => s + b.fleetSize, 0)
    const assigned = blocks.reduce((s, b) => s + b.assigned, 0)
    const available = fleet - assigned
    const today = new Date()
    today.setHours(0,0,0,0)
    const nearCutoff = blocks.filter(b => {
        if (!b.cutoffDate) return false
        const diff = Math.ceil((new Date(b.cutoffDate) - today) / 86400000)
        return diff <= 7 && diff >= 0 && b.assigned < b.fleetSize
    }).length
    return { fleet, assigned, available, nearCutoff }
})

const vbContractSummary = computed(() => {
    const blocks = filteredBlocks.value
    let totalValue = 0
    let totalVehicleDays = 0
    blocks.forEach(b => {
        const days = daysBetween(b.startDate, b.endDate)
        const vd = b.fleetSize * days
        totalVehicleDays += vd
        totalValue += vd * b.dailyRate
    })
    const currency = blocks[0]?.currency ?? 'QAR'
    return { totalValue, totalVehicleDays, currency }
})

function blockDays(b) { return daysBetween(b.startDate, b.endDate) }
function blockRemaining(b) { return b.fleetSize - b.assigned }
function blockPct(b) { return b.fleetSize > 0 ? Math.round((b.assigned / b.fleetSize) * 100) : 0 }

function cutoffDays(b) {
    if (!b.cutoffDate) return 999
    const today = new Date()
    today.setHours(0,0,0,0)
    return Math.ceil((new Date(b.cutoffDate) - today) / 86400000)
}

function fmtCurrency(val, currency) {
    return `${currency} ${val.toLocaleString()}`
}

function adjustAssign(block, delta) {
    const idx = localBlocks.value.findIndex(b => b.id === block.id)
    if (idx === -1) return
    const newVal = Math.max(0, Math.min(block.fleetSize, block.assigned + delta))
    localBlocks.value[idx] = { ...localBlocks.value[idx], assigned: newVal }
    router.patch(route('gms.transport.blocks.assign', block.id), { assigned: newVal }, {
        preserveScroll: true,
        onError: () => {
            localBlocks.value[idx] = { ...localBlocks.value[idx], assigned: block.assigned }
            toast('Failed to update.', 'error')
        },
    })
}

const blockModal = ref(false)
const editingBlock = ref(null)
const blockForm = useForm({
    provider: '', vehicleType: '', vehicleClass: '', dailyRate: '', currency: 'QAR',
    startDate: '', endDate: '', fleetSize: '', assigned: 0, cutoffDate: '', notes: ''
})

function openNewBlock() {
    editingBlock.value = null
    blockForm.reset()
    blockForm.currency = 'QAR'
    blockForm.assigned = 0
    blockModal.value = true
}

function openEditBlock(b) {
    editingBlock.value = b
    blockForm.provider = b.provider
    blockForm.vehicleType = b.vehicleType
    blockForm.vehicleClass = b.vehicleClass
    blockForm.dailyRate = b.dailyRate
    blockForm.currency = b.currency
    blockForm.startDate = b.startDate
    blockForm.endDate = b.endDate
    blockForm.fleetSize = b.fleetSize
    blockForm.assigned = b.assigned
    blockForm.cutoffDate = b.cutoffDate
    blockForm.notes = b.notes || ''
    blockModal.value = true
}

const vbFormDays = computed(() => daysBetween(blockForm.startDate, blockForm.endDate))
const vbFormVehicleDays = computed(() => (parseInt(blockForm.fleetSize) || 0) * vbFormDays.value)
const vbFormContractValue = computed(() => vbFormVehicleDays.value * (parseFloat(blockForm.dailyRate) || 0))

function saveBlock() {
    if (editingBlock.value) {
        const editId = editingBlock.value.id
        const idx = localBlocks.value.findIndex(b => b.id === editId)
        if (idx !== -1) {
            localBlocks.value[idx] = {
                ...localBlocks.value[idx],
                provider: blockForm.provider,
                vehicleType: blockForm.vehicleType,
                vehicleClass: blockForm.vehicleClass,
                dailyRate: parseFloat(blockForm.dailyRate) || 0,
                currency: blockForm.currency,
                startDate: blockForm.startDate,
                endDate: blockForm.endDate,
                fleetSize: parseInt(blockForm.fleetSize) || 0,
                assigned: parseInt(blockForm.assigned) || 0,
                cutoffDate: blockForm.cutoffDate,
                notes: blockForm.notes,
            }
        }
        blockForm.put(route('gms.transport.blocks.update', editId), {
            preserveScroll: true,
            onSuccess: () => toast('Vehicle block updated.'),
            onError: () => toast('Failed to update.', 'error'),
        })
    } else {
        localBlocks.value.push({
            id: 'tmp-' + Date.now(),
            provider: blockForm.provider,
            vehicleType: blockForm.vehicleType,
            vehicleClass: blockForm.vehicleClass,
            dailyRate: parseFloat(blockForm.dailyRate) || 0,
            currency: blockForm.currency,
            startDate: blockForm.startDate,
            endDate: blockForm.endDate,
            fleetSize: parseInt(blockForm.fleetSize) || 0,
            assigned: parseInt(blockForm.assigned) || 0,
            cutoffDate: blockForm.cutoffDate,
            notes: blockForm.notes,
        })
        blockForm.post(route('gms.transport.blocks.store'), {
            preserveScroll: true,
            onSuccess: () => {
                localBlocks.value = props.vehicleBlocks.map(b => ({ ...b }))
                toast('Vehicle block created.')
            },
            onError: () => toast('Failed to create.', 'error'),
        })
    }
    blockModal.value = false
    editingBlock.value = null
}

const deleteBlockConfirm = ref(null)

function deleteBlock(b) {
    if (deleteBlockConfirm.value !== b.id) {
        deleteBlockConfirm.value = b.id
        setTimeout(() => { deleteBlockConfirm.value = null }, 3000)
        return
    }
    localBlocks.value = localBlocks.value.filter(x => x.id !== b.id)
    router.delete(route('gms.transport.blocks.destroy', b.id), {
        preserveScroll: true,
        onSuccess: () => toast('Vehicle block deleted.'),
        onError: () => toast('Failed to delete.', 'error'),
    })
    deleteBlockConfirm.value = null
}
</script>

<template>
  <div>
  <div class="gms-view">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Transport</h1>
        <p class="gms-view-subtitle" v-if="currentTab === 'blocks'">Contracted vehicle fleets and assignment across providers for <strong>{{ props.event?.name || 'event' }}</strong>.</p>
        <p class="gms-view-subtitle" v-else>Ground transport &amp; chauffeur assignments for {{ props.event?.name || 'event' }}.</p>
      </div>
      <div class="gms-view-actions">
        <button
          class="gms-btn gms-btn-ghost gms-btn-sm"
          @click="refreshRequests"
          :disabled="isRefreshing"
          title="Refresh requests"
          style="margin-right: 8px;"
          v-if="currentTab !== 'blocks'"
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
        <template v-else-if="currentTab === 'blocks'">
          <button class="gms-btn gms-btn-primary" @click="openNewBlock">
            <GmsIcon name="plus" :size="14" /> New vehicle block
          </button>
        </template>
        <template v-else>
          <button class="gms-btn gms-btn-primary" @click="newModal = true">
            <GmsIcon name="plus" :size="14" /> New Request
          </button>
        </template>
      </div>
    </div>

    <!-- Tab selector -->
    <div class="gms-tabs" style="margin-bottom: 20px;">
      <button class="gms-tab" :class="{ active: currentTab === 'requests' }" @click="currentTab = 'requests'">Requests</button>
      <button class="gms-tab" :class="{ active: currentTab === 'inbox' }" @click="currentTab = 'inbox'">
        Guest requests
        <span v-if="pendingGuestRequests.length" class="gms-tab-badge">{{ pendingGuestRequests.length }}</span>
      </button>
      <button class="gms-tab" :class="{ active: currentTab === 'movements' }" @click="currentTab = 'movements'">Movements plan</button>
      <button class="gms-tab" :class="{ active: currentTab === 'blocks' }" @click="currentTab = 'blocks'">Vehicle blocks</button>
    </div>

    <!-- Stats (requests/movements tabs) -->
    <div v-if="currentTab !== 'blocks'" class="gms-stats" style="margin-bottom:24px;">
      <GmsMiniStat :value="countFor('confirmed')" label="Confirmed" color="#3f7d52" />
      <GmsMiniStat :value="countFor('pending')" label="Pending" color="#a16207" />
      <GmsMiniStat :value="countFor('cancelled')" label="Cancelled" />
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
      
      <GmsFilterDropdown
        v-model="sourceFilter"
        label="Source"
        all-label="All Sources"
        :options="sourceOptions"
        style="margin-left: 12px;"
      />
      
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
                    <div style="display:flex;align-items:center;gap:6px;">
                      <span style="font-weight:600;font-size:13px;">{{ r.guestName }}</span>
                      <span v-if="r.source === 'portal'" class="portal-badge">
                        <GmsIcon name="globe" :size="10" />
                      </span>
                    </div>
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

    <!-- ═══════════ GUEST REQUESTS INBOX ═══════════ -->
    <template v-if="currentTab === 'inbox'">
      <div class="gms-stats" style="margin-bottom:24px;">
        <GmsMiniStat :value="pendingGuestRequests.length" label="Pending requests" color="#a16207" />
        <GmsMiniStat :value="fulfilledGuestRequests.length" label="Booked" color="#3f7d52" />
      </div>

      <div v-if="fulfilledGuestRequests.length > 0" style="margin-bottom:16px;">
        <button class="gms-btn gms-btn-ghost gms-btn-sm" @click="showFulfilled = !showFulfilled">
          <GmsIcon name="eye" :size="13" />
          {{ showFulfilled ? 'Hide' : 'Show' }} booked ({{ fulfilledGuestRequests.length }})
        </button>
      </div>

      <div class="gr-list">
        <div v-for="r in pendingGuestRequests" :key="r.id" class="gr-card">
          <div class="gr-main">
            <div style="display:flex;align-items:center;gap:9px;">
              <GmsAvatar :name="r.guestName" size="sm" />
              <div>
                <div class="gr-guest-name">{{ r.guestName }}</div>
                <div class="gr-code">{{ r.id }} · submitted {{ formatDateTime(r.submitted) }}</div>
              </div>
            </div>
            <div class="gr-prefs">
              <div class="gr-pref">
                <span class="gr-pref-label">Type</span>
                <span class="gr-pref-value">{{ r.type }}</span>
              </div>
              <div class="gr-pref">
                <span class="gr-pref-label">Pick-up</span>
                <span class="gr-pref-value">{{ r.pickupLocation || '—' }}</span>
              </div>
              <div class="gr-pref">
                <span class="gr-pref-label">Drop-off</span>
                <span class="gr-pref-value">{{ r.dropoffLocation || '—' }}</span>
              </div>
              <div class="gr-pref">
                <span class="gr-pref-label">Date & time</span>
                <span class="gr-pref-value">{{ formatDateTime(r.datetime) }}</span>
              </div>
            </div>
            <div v-if="r.notes" class="gr-notes">"{{ r.notes }}"</div>
          </div>
          <div class="gr-actions">
            <GmsBtn variant="primary" icon="car" :iconSize="13" @click="bookFromRequest(r)">Book transport</GmsBtn>
          </div>
        </div>

        <template v-if="showFulfilled">
          <div v-for="r in fulfilledGuestRequests" :key="r.id" class="gr-card fulfilled">
            <div class="gr-main">
              <div style="display:flex;align-items:center;gap:9px;">
                <GmsAvatar :name="r.guestName" size="sm" />
                <div>
                  <div class="gr-guest-name">{{ r.guestName }}</div>
                  <div class="gr-code">{{ r.id }}</div>
                </div>
              </div>
              <div class="gr-prefs">
                <div class="gr-pref">
                  <span class="gr-pref-label">Type</span>
                  <span class="gr-pref-value">{{ r.type }}</span>
                </div>
                <div class="gr-pref">
                  <span class="gr-pref-label">Route</span>
                  <span class="gr-pref-value">{{ r.pickupLocation }} → {{ r.dropoffLocation }}</span>
                </div>
              </div>
            </div>
            <div class="gr-actions">
              <span class="gr-fulfilled-tag"><GmsIcon name="check" :size="12" /> Booked</span>
            </div>
          </div>
        </template>

        <div v-if="!pendingGuestRequests.length && !showFulfilled" class="gms-empty" style="padding:40px 20px;">
          <GmsIcon name="car" :size="32" style="opacity:.3;margin-bottom:12px;" />
          <div class="gms-empty-title">No pending guest requests</div>
          <div class="gms-empty-sub">Guest transport preferences submitted via the portal will appear here.</div>
        </div>
      </div>
    </template>

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

    <!-- ═══════════ VEHICLE BLOCKS TAB ═══════════ -->
    <template v-if="currentTab === 'blocks'">
      <div class="gms-stats" style="margin-bottom:24px;">
        <GmsMiniStat :value="blockStats.fleet"     label="Fleet size"      color="var(--gms-maroon)" />
        <GmsMiniStat :value="blockStats.assigned"  label="Assigned"        color="#2d5f3a" />
        <GmsMiniStat :value="blockStats.available" label="Available"       color="#3a6a8a" />
        <GmsMiniStat :value="blockStats.nearCutoff" label="Near cut-off"  :color="blockStats.nearCutoff > 0 ? '#c53030' : 'var(--gms-text-3)'" />
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div class="gms-seg">
          <button :class="{ on: providerFilter === 'all' }" @click="providerFilter = 'all'">All providers</button>
          <button v-for="p in blockProviders" :key="p" :class="{ on: providerFilter === p }" @click="providerFilter = p">{{ p }}</button>
        </div>
        <span class="rb-summary" v-if="vbContractSummary.totalVehicleDays > 0">
          {{ fmtCurrency(vbContractSummary.totalValue, vbContractSummary.currency) }} contract · {{ vbContractSummary.totalVehicleDays.toLocaleString() }} vehicle-days
        </span>
      </div>

      <div class="rb-list">
        <div v-for="b in filteredBlocks" :key="b.id" class="rb-card" style="cursor:pointer;" @click="openEditBlock(b)">
          <div class="rb-main">
            <div class="rb-room-type">{{ b.vehicleType }}</div>
            <div class="rb-hotel-info">{{ b.provider }} · {{ b.vehicleClass }} · {{ b.currency }} {{ b.dailyRate.toLocaleString() }}/day · {{ blockDays(b) }} days</div>
            <div class="rb-dates">{{ b.startDate }} → {{ b.endDate }}</div>
          </div>

          <div class="rb-pickup">
            <div class="rb-bar"><span class="rb-bar-fill" :class="{ full: blockRemaining(b) === 0 }" :style="{ width: blockPct(b) + '%' }" /></div>
            <div class="rb-pickup-meta">
              <span class="rb-pickup-count">{{ b.assigned }} / {{ b.fleetSize }} assigned</span>
              <span class="rb-rem" :class="{ full: blockRemaining(b) === 0 }">
                {{ blockRemaining(b) === 0 ? 'Fully assigned' : blockRemaining(b) + ' available' }}
              </span>
              <span v-if="b.cutoffDate" class="rb-cutoff" :class="{ urgent: cutoffDays(b) <= 7 }">
                Cut-off in {{ cutoffDays(b) }}d
              </span>
            </div>
          </div>

          <div class="rb-actions" @click.stop>
            <div class="rb-step">
              <button @click="adjustAssign(b, -1)" :disabled="b.assigned <= 0"><GmsIcon name="minus" :size="12" /></button>
              <span class="rb-step-label">assign</span>
              <button @click="adjustAssign(b, 1)" :disabled="b.assigned >= b.fleetSize"><GmsIcon name="plus" :size="12" /></button>
            </div>
            <button class="rb-action-btn" title="Edit" @click="openEditBlock(b)"><GmsIcon name="edit" :size="14" /></button>
            <button class="rb-action-btn" title="Delete" @click="deleteBlock(b)" :style="deleteBlockConfirm === b.id ? 'color:#c53030;' : ''">
              <GmsIcon name="trash" :size="14" />
            </button>
          </div>
        </div>

        <div v-if="!filteredBlocks.length" class="gms-empty" style="padding:40px 20px;">
          <GmsIcon name="car" :size="32" style="opacity:.3;margin-bottom:12px;" />
          <div class="gms-empty-title">No vehicle blocks</div>
          <div class="gms-empty-sub">Create a vehicle block to track contracted fleet inventory.</div>
        </div>
      </div>
    </template>
  </div>

  <!-- ═══════════ VEHICLE BLOCK MODAL ═══════════ -->
  <GmsModal :open="blockModal" :title="editingBlock ? 'Edit vehicle block' : 'New vehicle block'" @close="blockModal = false; blockForm.reset(); editingBlock = null">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Provider *</label>
          <input v-model="blockForm.provider" class="gms-input" placeholder="Al Maha Limousines" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Vehicle type *</label>
          <input v-model="blockForm.vehicleType" class="gms-input" placeholder="Mercedes S-Class" />
        </div>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Vehicle class</label>
          <input v-model="blockForm.vehicleClass" class="gms-input" placeholder="VIP Sedan" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Currency</label>
          <select v-model="blockForm.currency" class="gms-input gms-select">
            <option value="QAR">QAR</option>
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
            <option value="GBP">GBP</option>
          </select>
        </div>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Daily rate *</label>
          <input v-model="blockForm.dailyRate" type="number" class="gms-input" placeholder="1200" min="0" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Fleet size *</label>
          <input v-model="blockForm.fleetSize" type="number" class="gms-input" placeholder="20" min="1" />
        </div>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Start date *</label>
          <GmsDatePicker v-model="blockForm.startDate" placeholder="Select date" dateFormat="Y-m-d" />
        </div>
        <div class="gms-field">
          <label class="gms-label">End date *</label>
          <GmsDatePicker v-model="blockForm.endDate" placeholder="Select date" dateFormat="Y-m-d" />
        </div>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Assigned</label>
          <input v-model="blockForm.assigned" type="number" class="gms-input" placeholder="0" min="0" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Cut-off date</label>
          <GmsDatePicker v-model="blockForm.cutoffDate" placeholder="Select cut-off date" dateFormat="Y-m-d" />
        </div>
      </div>

      <div class="rb-calc-strip" v-if="vbFormDays > 0 && blockForm.fleetSize > 0">
        <div class="rb-calc-item">
          <span class="rb-calc-label">Vehicle-days</span>
          <span class="rb-calc-value">{{ vbFormVehicleDays.toLocaleString() }}</span>
        </div>
        <div class="rb-calc-item">
          <span class="rb-calc-label">Days</span>
          <span class="rb-calc-value">{{ vbFormDays }}</span>
        </div>
        <div class="rb-calc-item">
          <span class="rb-calc-label">Contract value</span>
          <span class="rb-calc-value">{{ blockForm.currency }} {{ vbFormContractValue.toLocaleString() }}</span>
        </div>
      </div>

      <div class="gms-field">
        <label class="gms-label">Notes</label>
        <textarea v-model="blockForm.notes" class="gms-input gms-textarea" rows="2" placeholder="Block notes…" />
      </div>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="blockModal = false; blockForm.reset(); editingBlock = null">Cancel</button>
      <button
        class="gms-btn gms-btn-primary"
        :disabled="!blockForm.provider || !blockForm.vehicleType || !blockForm.dailyRate || !blockForm.startDate || !blockForm.endDate || !blockForm.fleetSize"
        @click="saveBlock"
      >
        {{ editingBlock ? 'Save changes' : 'Create block' }}
      </button>
    </template>
  </GmsModal>

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

  <GmsModal :open="newModal" :title="bookingFromGuestRequest ? 'Book transport' : 'New Transport Request'" @close="newModal = false; selectedGuestId = null; bookingFromGuestRequest = null; form.reset(); form.clearErrors()">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div v-if="bookingFromGuestRequest" class="gr-booking-banner">
        <GmsIcon name="globe" :size="14" />
        <span>Booking from guest request {{ bookingFromGuestRequest.id }} — assign vehicle and confirm details</span>
      </div>
      <div class="gms-field">
        <label class="gms-label">Guest</label>
        <div v-if="bookingFromGuestRequest" style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--gms-bg);border-radius:8px;border:1px solid var(--gms-border);">
          <GmsAvatar :name="bookingFromGuestRequest.guestName" size="sm" />
          <div style="flex:1;">
            <div style="font-weight:600;font-size:13px;">{{ bookingFromGuestRequest.guestName }}</div>
            <div style="font-size:11px;color:var(--gms-text-3);">Guest pre-selected from request {{ bookingFromGuestRequest.id }}</div>
          </div>
        </div>
        <template v-else>
          <div v-if="confirmedGuests.length === 0" class="gms-empty" style="padding: 40px 20px; background: var(--gms-surface-2); border-radius: 8px; margin-top: 10px;">
            <GmsIcon name="users" :size="32" style="opacity: 0.3; margin-bottom: 12px;" />
            <div class="gms-empty-title">No guests with confirmed invitations</div>
            <div class="gms-empty-sub" style="max-width: 400px; margin: 8px auto 0;">
              Only guests who have confirmed their invitations can request transport. Confirm guest invitations first.
            </div>
          </div>
          <GmsGuestPicker
            v-else
            :guests="confirmedGuests"
            :selected-guest-id="selectedGuestId"
            :tiers="props.tiers"
            :show-existing-indicator="true"
            :existing-guest-ids="existingTransportGuestIds"
            existing-label="has transport"
            @select="pickGuest"
          />
        </template>
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

<style scoped>
.portal-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--gms-maroon);
    color: white;
}
</style>
