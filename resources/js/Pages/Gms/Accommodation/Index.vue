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
import GmsGuestPicker from '@/Components/Gms/GmsGuestPicker.vue'
import GmsDatePicker from '@/Components/Gms/GmsDatePicker.vue'
import GmsFilterDropdown from '@/Components/Gms/GmsFilterDropdown.vue'

defineOptions({ layout: GmsLayout })

const MONTHS = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

const props = defineProps({
    requests:      { type: Array,  default: () => [] },
    guestRequests: { type: Array,  default: () => [] },
    roomBlocks:    { type: Array,  default: () => [] },
    guests:        { type: Array,  default: () => [] },
    hotels:        { type: Array,  default: () => [] },
    tiers:         { type: Array,  default: () => [] },
    event:         { type: Object, default: () => ({}) },
})

const toast = inject('toast')

// ── Tab state ──
const activeTab = ref('requests')

// ══════════════════════════════════════════════
// REQUESTS TAB
// ══════════════════════════════════════════════
const localReqs = ref(props.requests.map(r => ({ ...r })))
const search       = ref('')
const statusFilter = ref('all')
const sourceFilter = ref('all')

const sourceOptions = [
    { id: 'all', name: 'All Sources' },
    { id: 'portal', name: 'Portal' },
    { id: 'manual', name: 'Manual' },
    { id: 'phone', name: 'Phone' },
    { id: 'email', name: 'Email' },
]

const statuses = ['all', 'requested', 'new', 'change', 'confirmed', 'cancelled']
const statusColors = {
    requested: { bg: '#ede9fe', fg: '#7c3aed' },
    new:       { bg: '#fef9c3', fg: '#a16207' },
    change:    { bg: '#e2edf3', fg: '#3a6a8a' },
    confirmed: { bg: '#e6f0e7', fg: '#3f7d52' },
    cancelled: { bg: '#f3f4f6', fg: '#6b7280' },
}

const filtered = computed(() => {
    let list = localReqs.value
    if (statusFilter.value !== 'all') list = list.filter(r => r.status === statusFilter.value)
    if (sourceFilter.value !== 'all') list = list.filter(r => r.source === sourceFilter.value)
    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(r => r.guestName.toLowerCase().includes(q) || r.hotelName.toLowerCase().includes(q))
    }
    return list
})

function countFor(s) {
    if (s === 'all') return localReqs.value.length
    return localReqs.value.filter(r => r.status === s).length
}

function fmtDateShort(d) {
    if (!d) return '—'
    const parts = d.split('-')
    const year = parts[0].slice(-2)
    return `${parseInt(parts[2])} ${MONTHS[parseInt(parts[1])]} ${year}`
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

// ══════════════════════════════════════════════
// GUEST REQUESTS INBOX
// ══════════════════════════════════════════════
const localGuestReqs = ref(props.guestRequests.map(r => ({ ...r })))
const pendingGuestRequests = computed(() => localGuestReqs.value.filter(r => !r.fulfilledById))
const fulfilledGuestRequests = computed(() => localGuestReqs.value.filter(r => r.fulfilledById))
const showFulfilled = ref(false)
const bookingFromGuestRequest = ref(null)

const drawerOpen = ref(false)
const activeReq  = ref(null)
const confirmDelete = ref(false)

function openDrawer(r) {
    activeReq.value = r
    drawerOpen.value = true
    confirmDelete.value = false
}

function canConfirm(r) {
    return r.status !== 'confirmed' && r.status !== 'cancelled'
}

function changeStatus(id, status) {
    const idx = localReqs.value.findIndex(r => r.id === id)
    if (idx !== -1) localReqs.value[idx] = { ...localReqs.value[idx], status }
    router.patch(route('gms.accommodation.status', id), { status }, {
        onSuccess: () => toast(`Status updated to ${status}.`),
        preserveScroll: true,
    })
}

function reinstateRequest(r) {
    const idx = localReqs.value.findIndex(x => x.id === r.id)
    if (idx !== -1) localReqs.value[idx] = { ...localReqs.value[idx], status: 'new' }
    if (activeReq.value?.id === r.id) activeReq.value = { ...activeReq.value, status: 'new' }
    drawerOpen.value = false
    router.patch(route('gms.accommodation.status', r.id), { status: 'new' }, {
        onSuccess: () => toast('Request reinstated.'),
        onError:   () => toast('Failed to update.', 'error'),
        preserveScroll: true,
    })
}

function deleteReq(r) {
    if (!confirmDelete.value) {
        confirmDelete.value = true
        setTimeout(() => { confirmDelete.value = false }, 3000)
        return
    }
    const id = r.id
    localReqs.value = localReqs.value.filter(req => req.id !== id)
    drawerOpen.value = false
    router.delete(route('gms.accommodation.destroy', id), { preserveScroll: true })
    toast('Request deleted.')
    confirmDelete.value = false
}

const newModal = ref(false)
const selectedGuestId = ref(null)
const editModal = ref(false)
const editingReq = ref(null)
const form = useForm({ guestId: '', hotel: '', roomType: '', checkIn: '', checkOut: '', notes: '' })

const sortedGuestList = computed(() => {
    return props.guests.slice().sort((a, b) => {
        const aIntl = a.guestType === 'international' ? 0 : 1
        const bIntl = b.guestType === 'international' ? 0 : 1
        return aIntl - bIntl
    })
})

const confirmedGuests = computed(() => {
    return sortedGuestList.value.filter(g => g.hasConfirmedInvitation)
})

const existingAccommodationGuestIds = computed(() => {
    return localReqs.value
        .filter(r => r.status !== 'cancelled')
        .map(r => r.guestId)
})

function pickGuest(guest) {
    selectedGuestId.value = guest.id
    form.guestId = guest.id
}

function nightsBetween(a, b) {
    if (!a || !b) return 0
    return Math.round((new Date(b) - new Date(a)) / 86400000)
}

function openEdit(r) {
    editingReq.value = r
    form.guestId = r.guestId
    form.hotel = r.hotel
    form.roomType = r.roomType
    form.checkIn = r.checkIn
    form.checkOut = r.checkOut
    form.notes = r.notes || ''
    editModal.value = true
    drawerOpen.value = false
}

function bookFromRequest(r) {
    bookingFromGuestRequest.value = r
    selectedGuestId.value = r.guestId
    form.guestId = r.guestId
    form.roomType = r.roomType || ''
    form.checkIn = r.checkIn || ''
    form.checkOut = r.checkOut || ''
    form.notes = r.notes || ''
    form.hotel = ''
    newModal.value = true
}

function saveNew() {
    const guest = props.guests.find(g => g.id === form.guestId)
    const hotel = props.hotels.find(h => h.id === form.hotel)

    const targetRoute = bookingFromGuestRequest.value
        ? route('gms.accommodation.book-guest-request', bookingFromGuestRequest.value.id)
        : route('gms.accommodation.store')

    form.post(targetRoute, {
        onSuccess: () => {
            if (bookingFromGuestRequest.value) {
                const idx = localGuestReqs.value.findIndex(x => x.id === bookingFromGuestRequest.value.id)
                if (idx !== -1) localGuestReqs.value[idx] = { ...localGuestReqs.value[idx], fulfilledById: 'pending' }
                bookingFromGuestRequest.value = null
            }
            localReqs.value.unshift({
                id: 'ACC-' + Date.now(),
                ...form.data(),
                guestName: guest?.name ?? '',
                hotelName: hotel?.name ?? '',
                nights: nightsBetween(form.checkIn, form.checkOut),
                status: 'new',
            })
            newModal.value = false
            selectedGuestId.value = null
            form.reset()
            toast('Accommodation request created.')

            router.reload({
                only: ['requests', 'guestRequests'],
                preserveScroll: true,
                onSuccess: () => {
                    localReqs.value = props.requests.map(r => ({ ...r }))
                    localGuestReqs.value = props.guestRequests.map(r => ({ ...r }))
                },
            })
        },
        onError: () => toast('Failed.', 'error'),
        preserveScroll: true,
    })
}

function saveEdit() {
    const guest = props.guests.find(g => g.id === form.guestId)
    const hotel = props.hotels.find(h => h.id === form.hotel)
    form.patch(route('gms.accommodation.update', editingReq.value.id), {
        onSuccess: () => {
            const idx = localReqs.value.findIndex(r => r.id === editingReq.value.id)
            if (idx !== -1) {
                localReqs.value[idx] = {
                    ...localReqs.value[idx],
                    ...form.data(),
                    guestName: guest?.name ?? '',
                    hotelName: hotel?.name ?? '',
                    nights: nightsBetween(form.checkIn, form.checkOut),
                }
            }
            editModal.value = false
            form.reset()
            editingReq.value = null
            toast('Accommodation request updated.')
        },
        onError: () => toast('Failed to update.', 'error'),
        preserveScroll: true,
    })
}

// ══════════════════════════════════════════════
// ROOM BLOCKS TAB
// ══════════════════════════════════════════════
const localBlocks = ref(props.roomBlocks.map(b => ({ ...b })))
const hotelFilter = ref('all')

const blockHotels = computed(() => {
    const seen = new Map()
    localBlocks.value.forEach(b => {
        if (!seen.has(b.hotelId)) seen.set(b.hotelId, b.hotelName)
    })
    return Array.from(seen, ([id, name]) => ({ id, name }))
})

const filteredBlocks = computed(() => {
    if (hotelFilter.value === 'all') return localBlocks.value
    return localBlocks.value.filter(b => b.hotelId === hotelFilter.value)
})

const blockStats = computed(() => {
    const blocks = localBlocks.value
    const held = blocks.reduce((s, b) => s + b.allotment, 0)
    const picked = blocks.reduce((s, b) => s + b.pickedUp, 0)
    const remaining = held - picked
    const today = new Date()
    today.setHours(0,0,0,0)
    const nearCutoff = blocks.filter(b => {
        const cutoff = new Date(b.cutoffDate)
        const diff = Math.ceil((cutoff - today) / 86400000)
        return diff <= 7 && diff >= 0 && b.pickedUp < b.allotment
    }).length
    return { held, picked, remaining, nearCutoff }
})

const contractSummary = computed(() => {
    const blocks = filteredBlocks.value
    let totalValue = 0
    let totalRoomNights = 0
    blocks.forEach(b => {
        const nights = nightsBetween(b.checkIn, b.checkOut)
        const rn = b.allotment * nights
        totalRoomNights += rn
        totalValue += rn * b.rate
    })
    const currency = blocks[0]?.currency ?? 'QAR'
    return { totalValue, totalRoomNights, currency }
})

function blockNights(b) {
    return nightsBetween(b.checkIn, b.checkOut)
}

function blockRemaining(b) {
    return b.allotment - b.pickedUp
}

function blockPct(b) {
    return b.allotment > 0 ? Math.round((b.pickedUp / b.allotment) * 100) : 0
}

function cutoffDays(b) {
    const today = new Date()
    today.setHours(0,0,0,0)
    const cutoff = new Date(b.cutoffDate)
    return Math.ceil((cutoff - today) / 86400000)
}

function fmtCurrency(val, currency) {
    return `${currency} ${val.toLocaleString()}`
}

function adjustPickup(block, delta) {
    const idx = localBlocks.value.findIndex(b => b.id === block.id)
    if (idx === -1) return
    const newVal = Math.max(0, Math.min(block.allotment, block.pickedUp + delta))
    localBlocks.value[idx] = { ...localBlocks.value[idx], pickedUp: newVal }
    router.patch(route('gms.accommodation.blocks.pickup', block.id), { pickedUp: newVal }, {
        preserveScroll: true,
        onError: () => {
            localBlocks.value[idx] = { ...localBlocks.value[idx], pickedUp: block.pickedUp }
            toast('Failed to update pickup.', 'error')
        },
    })
}

// ── Room Block CRUD ──
const blockModal = ref(false)
const editingBlock = ref(null)
const blockForm = useForm({
    hotelId: '', hotelName: '', roomType: '', rate: '', currency: 'QAR',
    checkIn: '', checkOut: '', allotment: '', pickedUp: 0, cutoffDate: '', notes: ''
})

const blockHotelOptions = computed(() => {
    const unique = new Map()
    localBlocks.value.forEach(b => unique.set(b.hotelId, b.hotelName))
    props.hotels.forEach(h => { if (!unique.has(h.id)) unique.set(h.id, h.name) })
    return Array.from(unique, ([id, name]) => ({ id, name }))
})

function openNewBlock() {
    editingBlock.value = null
    blockForm.reset()
    blockForm.currency = 'QAR'
    blockForm.pickedUp = 0
    blockModal.value = true
}

function openEditBlock(b) {
    editingBlock.value = b
    blockForm.hotelId = b.hotelId
    blockForm.roomType = b.roomType
    blockForm.rate = b.rate
    blockForm.currency = b.currency
    blockForm.checkIn = b.checkIn
    blockForm.checkOut = b.checkOut
    blockForm.allotment = b.allotment
    blockForm.pickedUp = b.pickedUp
    blockForm.cutoffDate = b.cutoffDate
    blockForm.notes = b.notes || ''
    blockModal.value = true
}

const blockFormNights = computed(() => nightsBetween(blockForm.checkIn, blockForm.checkOut))
const blockFormRoomNights = computed(() => (parseInt(blockForm.allotment) || 0) * blockFormNights.value)
const blockFormContractValue = computed(() => blockFormRoomNights.value * (parseFloat(blockForm.rate) || 0))

function saveBlock() {
    const hotelName = blockHotelOptions.value.find(h => h.id === blockForm.hotelId)?.name ?? ''
    blockForm.hotelName = hotelName

    if (editingBlock.value) {
        const editId = editingBlock.value.id
        const idx = localBlocks.value.findIndex(b => b.id === editId)
        if (idx !== -1) {
            localBlocks.value[idx] = {
                ...localBlocks.value[idx],
                hotelId: blockForm.hotelId,
                hotelName,
                roomType: blockForm.roomType,
                rate: parseFloat(blockForm.rate) || 0,
                currency: blockForm.currency,
                checkIn: blockForm.checkIn,
                checkOut: blockForm.checkOut,
                allotment: parseInt(blockForm.allotment) || 0,
                pickedUp: parseInt(blockForm.pickedUp) || 0,
                cutoffDate: blockForm.cutoffDate,
                notes: blockForm.notes,
            }
        }
        blockForm.put(route('gms.accommodation.blocks.update', editId), {
            preserveScroll: true,
            onSuccess: () => toast('Room block updated.'),
            onError: () => toast('Failed to update.', 'error'),
        })
    } else {
        localBlocks.value.push({
            id: 'tmp-' + Date.now(),
            hotelId: blockForm.hotelId,
            hotelName,
            roomType: blockForm.roomType,
            rate: parseFloat(blockForm.rate) || 0,
            currency: blockForm.currency,
            checkIn: blockForm.checkIn,
            checkOut: blockForm.checkOut,
            allotment: parseInt(blockForm.allotment) || 0,
            pickedUp: parseInt(blockForm.pickedUp) || 0,
            cutoffDate: blockForm.cutoffDate,
            notes: blockForm.notes,
        })
        blockForm.post(route('gms.accommodation.blocks.store'), {
            preserveScroll: true,
            onSuccess: () => {
                localBlocks.value = props.roomBlocks.map(b => ({ ...b }))
                toast('Room block created.')
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
    router.delete(route('gms.accommodation.blocks.destroy', b.id), {
        preserveScroll: true,
        onSuccess: () => toast('Room block deleted.'),
        onError: () => toast('Failed to delete.', 'error'),
    })
    deleteBlockConfirm.value = null
}

// ── Add Hotel modal ──
const hotelModal = ref(false)
const hotelForm = useForm({ name: '', area: '', stars: 5 })

function saveHotel() {
    hotelForm.post(route('gms.accommodation.hotels.store'), {
        preserveScroll: true,
        onSuccess: () => {
            hotelModal.value = false
            hotelForm.reset()
            hotelForm.stars = 5
            toast('Hotel added.')
        },
        onError: () => toast('Failed to add hotel.', 'error'),
    })
}
</script>

<template>
  <div>
  <div class="gms-view">
    <div class="gms-view-pad">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Accommodation</h1>
        <p class="gms-view-subtitle" v-if="activeTab === 'blocks'">Contracted room blocks and pickup across partner hotels for <strong>{{ event?.name ?? 'this event' }}</strong>.</p>
        <p class="gms-view-subtitle" v-else>Hotel bookings &amp; room nights for guests of {{ event?.name ?? 'this event' }}.</p>
      </div>
      <div class="gms-view-actions" v-if="activeTab === 'requests'">
        <button class="gms-btn gms-btn-ghost gms-btn-sm" @click="refreshRequests" :disabled="isRefreshing" title="Refresh requests" style="margin-right: 8px;">
          <GmsIcon name="refresh-cw" :size="14" :class="{ 'gms-spin': isRefreshing }" />
        </button>
        <button class="gms-btn gms-btn-ghost" @click="toast('Export coming soon.')">
          <GmsIcon name="upload" :size="14" /> Export
        </button>
        <button class="gms-btn gms-btn-primary" @click="newModal = true">
          <GmsIcon name="plus" :size="14" /> New booking
        </button>
      </div>
      <div class="gms-view-actions" v-else>
        <button class="gms-btn gms-btn-ghost" @click="hotelModal = true">
          <GmsIcon name="plus" :size="14" /> Hotel
        </button>
        <button class="gms-btn gms-btn-primary" @click="openNewBlock">
          <GmsIcon name="plus" :size="14" /> New room block
        </button>
      </div>
    </div>

    <!-- Tab switcher -->
    <div class="gms-tabs" style="margin-bottom: 20px;">
      <button class="gms-tab" :class="{ active: activeTab === 'requests' }" @click="activeTab = 'requests'">Requests</button>
      <button class="gms-tab" :class="{ active: activeTab === 'inbox' }" @click="activeTab = 'inbox'">
        Guest requests
        <span v-if="pendingGuestRequests.length" class="gms-tab-badge">{{ pendingGuestRequests.length }}</span>
      </button>
      <button class="gms-tab" :class="{ active: activeTab === 'blocks' }" @click="activeTab = 'blocks'">Room blocks</button>
    </div>

    <!-- ═══════════ REQUESTS TAB ═══════════ -->
    <template v-if="activeTab === 'requests'">
      <div class="gms-stats" style="margin-bottom:24px;">
        <GmsMiniStat :value="countFor('new')"        label="New requests"     color="#a9772a" />
        <GmsMiniStat :value="countFor('change')"     label="Change requests"  color="#3a6a8a" />
        <GmsMiniStat :value="countFor('confirmed')"  label="Confirmed"        color="#3f7d52" />
        <GmsMiniStat :value="countFor('cancelled')"  label="Cancelled" />
      </div>

      <div class="gms-toolbar">
        <div class="gms-search-wrap">
          <GmsIcon name="search" :size="14" class="gms-search-icon" />
          <input v-model="search" class="gms-search-input" placeholder="Search guest, hotel or confirmation…" />
        </div>
        <div class="gms-seg">
          <button v-for="s in statuses" :key="s" :class="{ on: statusFilter===s }" @click="statusFilter=s">
            {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
            <span class="gms-seg-count" v-if="countFor(s) > 0">{{ countFor(s) }}</span>
          </button>
        </div>
        <GmsFilterDropdown v-model="sourceFilter" label="Source" all-label="All Sources" :options="sourceOptions" style="margin-left: 12px;" />
        <span class="mxt-count" style="margin-left: auto;">{{ filtered.length }} of {{ localReqs.length }}</span>
      </div>

      <div class="gms-card">
        <div class="gms-card-body-0">
          <div class="gms-table-wrap">
            <table class="gms-table">
              <thead>
                <tr>
                  <th>Guest</th>
                  <th>Hotel</th>
                  <th>Room</th>
                  <th>Stay</th>
                  <th>Nights</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in filtered" :key="r.id" @click="openDrawer(r)" style="cursor:pointer;">
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
                  <td><span class="gms-small">{{ r.hotelName }}</span></td>
                  <td><GmsPill type="custom" :value="r.roomType" bg="#f0ebe6" fg="var(--gms-text-2)" /></td>
                  <td><span class="gms-small gms-muted" style="white-space:nowrap;">{{ fmtDateShort(r.checkIn) }} → {{ fmtDateShort(r.checkOut) }}</span></td>
                  <td><span class="gms-small">{{ r.nights }}</span></td>
                  <td>
                    <GmsPill type="custom" :value="r.status" :bg="statusColors[r.status]?.bg ?? '#f3f4f6'" :fg="statusColors[r.status]?.fg ?? '#374151'" />
                  </td>
                  <td @click.stop>
                    <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                      <button v-if="canConfirm(r)" class="flt-chevron-btn" title="Confirm" @click="changeStatus(r.id, 'confirmed')" style="color:#3f7d52;">
                        <GmsIcon name="check" :size="15" />
                      </button>
                      <button class="flt-chevron-btn" title="View details" @click="openDrawer(r)">
                        <GmsIcon name="chevron-right" :size="15" />
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!filtered.length"><td colspan="7"><div class="gms-empty"><div class="gms-empty-title">No requests</div></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════════ GUEST REQUESTS INBOX ═══════════ -->
    <template v-if="activeTab === 'inbox'">
      <div class="gms-stats" style="margin-bottom:24px;">
        <GmsMiniStat :value="pendingGuestRequests.length" label="Pending requests" color="#a9772a" />
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
                <div class="gr-code">{{ r.id }} · submitted {{ fmtDateShort(r.submitted?.split(' ')[0]) }}</div>
              </div>
            </div>
            <div class="gr-prefs">
              <div class="gr-pref">
                <span class="gr-pref-label">Hotel preference</span>
                <span class="gr-pref-value">{{ r.hotelName || '—' }}</span>
              </div>
              <div class="gr-pref">
                <span class="gr-pref-label">Room type</span>
                <span class="gr-pref-value">{{ r.roomType || '—' }}</span>
              </div>
              <div class="gr-pref">
                <span class="gr-pref-label">Check-in</span>
                <span class="gr-pref-value">{{ fmtDateShort(r.checkIn) }}</span>
              </div>
              <div class="gr-pref">
                <span class="gr-pref-label">Check-out</span>
                <span class="gr-pref-value">{{ fmtDateShort(r.checkOut) }}</span>
              </div>
              <div class="gr-pref">
                <span class="gr-pref-label">Nights</span>
                <span class="gr-pref-value">{{ r.nights }}</span>
              </div>
            </div>
            <div v-if="r.notes" class="gr-notes">"{{ r.notes }}"</div>
          </div>
          <div class="gr-actions">
            <GmsBtn variant="primary" icon="building" :iconSize="13" @click="bookFromRequest(r)">Book room</GmsBtn>
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
                  <span class="gr-pref-label">Hotel</span>
                  <span class="gr-pref-value">{{ r.hotelName || '—' }}</span>
                </div>
                <div class="gr-pref">
                  <span class="gr-pref-label">Room</span>
                  <span class="gr-pref-value">{{ r.roomType }}</span>
                </div>
                <div class="gr-pref">
                  <span class="gr-pref-label">Nights</span>
                  <span class="gr-pref-value">{{ r.nights }}</span>
                </div>
              </div>
            </div>
            <div class="gr-actions">
              <span class="gr-fulfilled-tag"><GmsIcon name="check" :size="12" /> Booked</span>
            </div>
          </div>
        </template>

        <div v-if="!pendingGuestRequests.length && !showFulfilled" class="gms-empty" style="padding:40px 20px;">
          <GmsIcon name="building" :size="32" style="opacity:.3;margin-bottom:12px;" />
          <div class="gms-empty-title">No pending guest requests</div>
          <div class="gms-empty-sub">Guest accommodation preferences submitted via the portal will appear here.</div>
        </div>
      </div>
    </template>

    <!-- ═══════════ ROOM BLOCKS TAB ═══════════ -->
    <template v-if="activeTab === 'blocks'">
      <div class="gms-stats" style="margin-bottom:24px;">
        <GmsMiniStat :value="blockStats.held"      label="Rooms held"       color="var(--gms-maroon)" />
        <GmsMiniStat :value="blockStats.picked"    label="Picked up"        color="#2d5f3a" />
        <GmsMiniStat :value="blockStats.remaining" label="Rooms remaining"  color="#3a6a8a" />
        <GmsMiniStat :value="blockStats.nearCutoff" label="Near cut-off"    :color="blockStats.nearCutoff > 0 ? '#c53030' : 'var(--gms-text-3)'" />
      </div>

      <!-- Hotel filter tabs -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div class="gms-seg">
          <button :class="{ on: hotelFilter === 'all' }" @click="hotelFilter = 'all'">All hotels</button>
          <button v-for="h in blockHotels" :key="h.id" :class="{ on: hotelFilter === h.id }" @click="hotelFilter = h.id">{{ h.name }}</button>
        </div>
        <span class="rb-summary" v-if="contractSummary.totalRoomNights > 0">
          {{ fmtCurrency(contractSummary.totalValue, contractSummary.currency) }} contract · {{ contractSummary.totalRoomNights.toLocaleString() }} room-nights
        </span>
      </div>

      <!-- Block cards -->
      <div class="rb-list">
        <div v-for="b in filteredBlocks" :key="b.id" class="rb-card" style="cursor:pointer;" @click="openEditBlock(b)">
          <!-- Left: room info -->
          <div class="rb-main">
            <div class="rb-room-type">{{ b.roomType }}</div>
            <div class="rb-hotel-info">{{ b.hotelName }} · {{ b.currency }} {{ b.rate.toLocaleString() }}/night · {{ blockNights(b) }} nights</div>
            <div class="rb-dates">{{ b.checkIn }} → {{ b.checkOut }}</div>
          </div>

          <!-- Center: pickup progress -->
          <div class="rb-pickup">
            <div class="rb-bar"><span class="rb-bar-fill" :class="{ full: blockRemaining(b) === 0 }" :style="{ width: blockPct(b) + '%' }" /></div>
            <div class="rb-pickup-meta">
              <span class="rb-pickup-count">{{ b.pickedUp }} / {{ b.allotment }} picked</span>
              <span class="rb-rem" :class="{ full: blockRemaining(b) === 0 }">
                {{ blockRemaining(b) === 0 ? 'Fully drawn' : blockRemaining(b) + ' left' }}
              </span>
              <span class="rb-cutoff" :class="{ urgent: cutoffDays(b) <= 7 }">
                Cut-off in {{ cutoffDays(b) }}d
              </span>
            </div>
          </div>

          <!-- Right: actions -->
          <div class="rb-actions" @click.stop>
            <div class="rb-step">
              <button @click="adjustPickup(b, -1)" :disabled="b.pickedUp <= 0"><GmsIcon name="minus" :size="12" /></button>
              <span class="rb-step-label">pickup</span>
              <button @click="adjustPickup(b, 1)" :disabled="b.pickedUp >= b.allotment"><GmsIcon name="plus" :size="12" /></button>
            </div>
            <button class="rb-action-btn" title="Edit" @click="openEditBlock(b)"><GmsIcon name="edit" :size="14" /></button>
            <button class="rb-action-btn" title="Delete" @click="deleteBlock(b)" :style="deleteBlockConfirm === b.id ? 'color:#c53030;' : ''">
              <GmsIcon name="trash" :size="14" />
            </button>
          </div>
        </div>

        <div v-if="!filteredBlocks.length" class="gms-empty" style="padding:40px 20px;">
          <GmsIcon name="building" :size="32" style="opacity:.3;margin-bottom:12px;" />
          <div class="gms-empty-title">No room blocks</div>
          <div class="gms-empty-sub">Create a room block to track contracted inventory.</div>
        </div>
      </div>
    </template>

    </div>
  </div>

  <!-- ═══════════ REQUEST DRAWER ═══════════ -->
  <GmsDrawer :open="drawerOpen" :title="activeReq?.hotelName ?? ''" :subtitle="activeReq?.roomType" @close="drawerOpen = false">
    <template v-if="activeReq">
      <div style="display:flex;gap:8px;margin-bottom:16px;">
        <GmsPill type="custom" :value="activeReq.status" :bg="statusColors[activeReq.status]?.bg" :fg="statusColors[activeReq.status]?.fg" />
      </div>
      <div class="gms-section-title">Booking Details</div>
      <div class="gms-detail-row"><span class="gms-detail-label">Guest</span><span class="gms-detail-value">{{ activeReq.guestName }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Hotel</span><span class="gms-detail-value">{{ activeReq.hotelName }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Room</span><span class="gms-detail-value">{{ activeReq.roomType }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Check-in</span><span class="gms-detail-value gms-mono">{{ fmtDateShort(activeReq.checkIn) }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Check-out</span><span class="gms-detail-value gms-mono">{{ fmtDateShort(activeReq.checkOut) }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Nights</span><span class="gms-detail-value">{{ activeReq.nights }}</span></div>
      <div v-if="activeReq.notes" class="gms-detail-row"><span class="gms-detail-label">Notes</span><span class="gms-detail-value">{{ activeReq.notes }}</span></div>
    </template>
    <template #footer>
      <GmsBtn variant="ghost" icon="user" :iconSize="13">Guest profile</GmsBtn>
      <GmsBtn icon="edit" :iconSize="13" style="background:var(--gms-surface);border-color:var(--gms-border);" @click="openEdit(activeReq)">Edit</GmsBtn>
      <div style="margin-left:auto;display:flex;gap:8px;">
        <GmsBtn variant="danger" icon="trash-2" :iconSize="13" @click="deleteReq(activeReq)">
          {{ confirmDelete ? 'Confirm delete?' : 'Delete' }}
        </GmsBtn>
        <GmsBtn v-if="canConfirm(activeReq)" variant="ghost" icon="x" :iconSize="13" @click="changeStatus(activeReq.id,'cancelled'); drawerOpen=false">Cancel</GmsBtn>
        <GmsBtn v-if="canConfirm(activeReq)" variant="primary" icon="check" :iconSize="13" @click="changeStatus(activeReq.id,'confirmed'); drawerOpen=false">Confirm</GmsBtn>
        <GmsBtn v-if="!canConfirm(activeReq)" variant="primary" icon="refresh-cw" :iconSize="13" @click="reinstateRequest(activeReq)">Re-instate</GmsBtn>
      </div>
    </template>
  </GmsDrawer>

  <!-- ═══════════ NEW REQUEST MODAL ═══════════ -->
  <GmsModal :open="newModal" :title="bookingFromGuestRequest ? 'Book accommodation' : 'New Accommodation Request'" @close="newModal = false; selectedGuestId = null; bookingFromGuestRequest = null; form.reset()">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div v-if="bookingFromGuestRequest" class="gr-booking-banner">
        <GmsIcon name="globe" :size="14" />
        <span>Booking from guest request {{ bookingFromGuestRequest.id }} — select hotel and confirm details</span>
      </div>
      <div class="gms-field">
        <label class="gms-label">Guest</label>
        <!-- Locked guest when booking from guest request -->
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
              Only guests who have confirmed their invitations can request accommodation. Confirm guest invitations first.
            </div>
          </div>
          <GmsGuestPicker
            v-else
            :guests="confirmedGuests"
            :selected-guest-id="selectedGuestId"
            :tiers="props.tiers"
            :show-existing-indicator="true"
            :existing-guest-ids="existingAccommodationGuestIds"
            existing-label="has accommodation"
            @select="pickGuest"
          />
        </template>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Hotel</label>
          <select v-model="form.hotel" class="gms-input gms-select">
            <option value="">— Select —</option>
            <option v-for="h in hotels" :key="h.id" :value="h.id">{{ h.name }}</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Room Type</label>
          <input v-model="form.roomType" class="gms-input" placeholder="Presidential Suite" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Check-in</label>
          <GmsDatePicker v-model="form.checkIn" placeholder="Select check-in date" dateFormat="Y-m-d" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Check-out</label>
          <GmsDatePicker v-model="form.checkOut" placeholder="Select check-out date" dateFormat="Y-m-d" />
        </div>
      </div>
      <div class="gms-field">
        <label class="gms-label">Notes</label>
        <textarea v-model="form.notes" class="gms-input gms-textarea" rows="2" />
      </div>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="newModal = false; selectedGuestId = null">Cancel</button>
      <button class="gms-btn gms-btn-primary" :disabled="form.processing" @click="saveNew">Create Request</button>
    </template>
  </GmsModal>

  <!-- ═══════════ EDIT REQUEST MODAL ═══════════ -->
  <GmsModal :open="editModal" title="Edit Accommodation Request" @close="editModal = false; form.reset(); editingReq = null">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="gms-field">
        <label class="gms-label">Guest</label>
        <input :value="editingReq?.guestName ?? ''" class="gms-input" disabled style="background: var(--gms-bg); color: var(--gms-text-2); cursor: not-allowed;" />
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Hotel</label>
          <select v-model="form.hotel" class="gms-input gms-select">
            <option value="">— Select —</option>
            <option v-for="h in hotels" :key="h.id" :value="h.id">{{ h.name }}</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Room Type</label>
          <input v-model="form.roomType" class="gms-input" placeholder="Presidential Suite" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Check-in</label>
          <GmsDatePicker v-model="form.checkIn" placeholder="Select check-in date" dateFormat="Y-m-d" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Check-out</label>
          <GmsDatePicker v-model="form.checkOut" placeholder="Select check-out date" dateFormat="Y-m-d" />
        </div>
      </div>
      <div class="gms-field">
        <label class="gms-label">Notes</label>
        <textarea v-model="form.notes" class="gms-input gms-textarea" rows="2" />
      </div>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="editModal = false; form.reset(); editingReq = null">Cancel</button>
      <button class="gms-btn gms-btn-primary" :disabled="form.processing" @click="saveEdit">Save Changes</button>
    </template>
  </GmsModal>

  <!-- ═══════════ ROOM BLOCK MODAL ═══════════ -->
  <GmsModal :open="blockModal" :title="editingBlock ? 'Edit room block' : 'New room block'" @close="blockModal = false; blockForm.reset(); editingBlock = null">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Hotel *</label>
          <select v-model="blockForm.hotelId" class="gms-input gms-select">
            <option value="">— Select —</option>
            <option v-for="h in blockHotelOptions" :key="h.id" :value="h.id">{{ h.name }}</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Room type *</label>
          <input v-model="blockForm.roomType" class="gms-input" placeholder="Deluxe King" />
        </div>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Nightly rate *</label>
          <input v-model="blockForm.rate" type="number" class="gms-input" placeholder="850" min="0" />
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
          <label class="gms-label">Check-in *</label>
          <GmsDatePicker v-model="blockForm.checkIn" placeholder="Select date" dateFormat="Y-m-d" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Check-out *</label>
          <GmsDatePicker v-model="blockForm.checkOut" placeholder="Select date" dateFormat="Y-m-d" />
        </div>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Allotment (rooms) *</label>
          <input v-model="blockForm.allotment" type="number" class="gms-input" placeholder="80" min="0" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Picked up</label>
          <input v-model="blockForm.pickedUp" type="number" class="gms-input" placeholder="0" min="0" />
        </div>
      </div>
      <div class="gms-field">
        <label class="gms-label">Cut-off date</label>
        <GmsDatePicker v-model="blockForm.cutoffDate" placeholder="Select cut-off date" dateFormat="Y-m-d" />
      </div>

      <!-- Calculated summary -->
      <div class="rb-calc-strip" v-if="blockFormNights > 0 && blockForm.allotment > 0">
        <div class="rb-calc-item">
          <span class="rb-calc-label">Room-nights</span>
          <span class="rb-calc-value">{{ blockFormRoomNights.toLocaleString() }}</span>
        </div>
        <div class="rb-calc-item">
          <span class="rb-calc-label">Nights</span>
          <span class="rb-calc-value">{{ blockFormNights }}</span>
        </div>
        <div class="rb-calc-item">
          <span class="rb-calc-label">Contract value</span>
          <span class="rb-calc-value">{{ blockForm.currency }} {{ blockFormContractValue.toLocaleString() }}</span>
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
        :disabled="!blockForm.hotelId || !blockForm.roomType || !blockForm.rate || !blockForm.checkIn || !blockForm.checkOut || !blockForm.allotment"
        @click="saveBlock"
      >
        {{ editingBlock ? 'Save changes' : 'Create block' }}
      </button>
    </template>
  </GmsModal>

  <!-- ═══════════ ADD HOTEL MODAL ═══════════ -->
  <GmsModal :open="hotelModal" title="Add hotel" size="sm" @close="hotelModal = false; hotelForm.reset()">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="gms-field">
        <label class="gms-label">Hotel name *</label>
        <input v-model="hotelForm.name" class="gms-input" placeholder="The Ned Doha" />
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Area</label>
          <input v-model="hotelForm.area" class="gms-input" placeholder="West Bay" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Stars</label>
          <select v-model="hotelForm.stars" class="gms-input gms-select">
            <option :value="5">5 Stars</option>
            <option :value="4">4 Stars</option>
            <option :value="3">3 Stars</option>
          </select>
        </div>
      </div>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="hotelModal = false; hotelForm.reset()">Cancel</button>
      <button class="gms-btn gms-btn-primary" :disabled="!hotelForm.name" @click="saveHotel">Add hotel</button>
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
