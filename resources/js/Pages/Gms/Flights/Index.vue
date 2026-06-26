<script setup>
import { ref, computed, inject, watch, nextTick } from 'vue'
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
import GmsTimePicker from '@/Components/Gms/GmsTimePicker.vue'
import GmsFilterDropdown from '@/Components/Gms/GmsFilterDropdown.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    requests:      { type: Array,  default: () => [] },
    guestRequests: { type: Array,  default: () => [] },
    guests:        { type: Array,  default: () => [] },
    tiers:         { type: Array,  default: () => [] },
    event:         { type: Object, default: () => ({}) },
})

const toast = inject('toast')

const localReqs = ref(props.requests.map(r => ({ ...r })))
const localGuestReqs = ref(props.guestRequests.map(r => ({ ...r })))
const viewMode = ref('queue') // queue | inbox | schedule

// ── Filters ───────────────────────────────────────────────────────
const search       = ref('')
const statusFilter = ref('all')
const sourceFilter = ref('all')

const tabs = [
    { key: 'all',       label: 'All' },
    { key: 'new',       label: 'New' },
    { key: 'change',    label: 'Change' },
    { key: 'confirmed', label: 'Confirmed' },
    { key: 'cancelled', label: 'Cancelled' },
]

const sourceOptions = [
    { id: 'all', name: 'All Sources' },
    { id: 'portal', name: 'Portal' },
    { id: 'manual', name: 'Manual' },
    { id: 'phone', name: 'Phone' },
    { id: 'email', name: 'Email' },
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
    
    if (sourceFilter.value !== 'all') {
        list = list.filter(r => r.source === sourceFilter.value)
    }
    
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
    return `${parseInt(parts[2])} ${MONTHS[parseInt(parts[1])]} ${parts[0]}`
}

function canConfirm(r) {
    return r.status !== 'confirmed' && r.status !== 'cancelled'
}

// ── Refresh ───────────────────────────────────────────────────────
const isRefreshing = ref(false)

function refreshFlights() {
    isRefreshing.value = true
    router.reload({
        only: ['requests', 'guestRequests'],
        onFinish: () => {
            setTimeout(() => {
                isRefreshing.value = false
                localReqs.value = props.requests.map(r => ({ ...r }))
                localGuestReqs.value = props.guestRequests.map(r => ({ ...r }))
                toast('Flights refreshed')
            }, 500)
        }
    })
}

// ── Drawer ────────────────────────────────────────────────────────
const drawerOpen = ref(false)
const activeReq  = ref(null)
const confirmDelete = ref(false)

function openDrawer(r) { 
    activeReq.value = r
    drawerOpen.value = true
    confirmDelete.value = false
}

// ── Schedule view ─────────────────────────────────────────────────
const scheduleData = computed(() => {
    const events = []
    const tidy = (t) => t.replace(/\s*\+1$/, '')
    
    // Build events from non-cancelled flights
    localReqs.value.filter(f => f.status !== 'cancelled').forEach(f => {
        const g = props.guests.find(x => x.id === f.guestId) || {}
        if (f.legs && f.legs.length >= 2) {
            // Use the leg's date field and format it consistently
            const inboundLeg = f.legs[0]
            const outboundLeg = f.legs[1]
            
            const inboundDate = inboundLeg.date || f.inboundDate
            const outboundDate = outboundLeg.date || f.outboundDate
            
            events.push({
                kind: 'arr',
                rawDate: inboundDate, // Keep raw date for sorting
                date: fmtDateShort(inboundDate),
                time: inboundLeg.arr || f.inboundTime || '—',
                leg: inboundLeg,
                guest: g,
                flight: f
            })
            events.push({
                kind: 'dep',
                rawDate: outboundDate, // Keep raw date for sorting
                date: fmtDateShort(outboundDate),
                time: outboundLeg.dep || f.outboundTime || '—',
                leg: outboundLeg,
                guest: g,
                flight: f
            })
        }
    })
    
    // Get unique days, sort by raw date, then map to display dates
    const uniqueDates = [...new Set(events.map(e => e.rawDate))].sort()
    
    return uniqueDates.map(rawDate => {
        const displayDate = fmtDateShort(rawDate)
        const arr = events
            .filter(e => e.rawDate === rawDate && e.kind === 'arr')
            .sort((a, b) => tidy(a.time).localeCompare(tidy(b.time)))
        const dep = events
            .filter(e => e.rawDate === rawDate && e.kind === 'dep')
            .sort((a, b) => tidy(a.time).localeCompare(tidy(b.time)))
        return { date: displayDate, arrivals: arr, departures: dep }
    })
})

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

function deleteFlightRequest(r) {
    if (!confirmDelete.value) {
        confirmDelete.value = true
        return
    }
    
    router.delete(route('gms.flights.destroy', r.id), {
        onSuccess: () => {
            const idx = localReqs.value.findIndex(x => x.id === r.id)
            if (idx !== -1) localReqs.value.splice(idx, 1)
            drawerOpen.value = false
            confirmDelete.value = false
            toast('Flight request deleted.')
        },
        onError: () => {
            confirmDelete.value = false
            toast('Failed to delete.', 'error')
        },
        preserveScroll: true,
    })
}

// ── Edit leg modal ────────────────────────────────────────────────
const legModal    = ref(false)
const editingLeg  = ref(null) // 'inbound' | 'outbound'
const isSavingLeg = ref(false)
const legForm = useForm({ 
    airline: '', flightNo: '', class: 'Business', date: '', duration: '', time: '', arrivalTime: '',
    fromCode: '', fromCity: '', toCode: '', toCity: ''
})

function openEditLeg(direction) {
    editingLeg.value = direction
    const r = activeReq.value
    
    // Find the specific leg from the legs array
    const leg = r.legs.find(l => l.dir === (direction === 'inbound' ? 'Inbound' : 'Outbound'))
    
    // Prepare form data
    let formData = {}
    
    if (direction === 'inbound') {
        formData = {
            airline:     leg?.airline ?? r.airline ?? '',
            flightNo:    leg?.flightNo ?? r.inboundFlight ?? r.flightNo ?? '',
            class:       leg?.cls ?? r.class ?? 'Business',
            date:        leg?.date ?? r.date ?? '',
            duration:    leg?.dur ?? r.duration ?? '',
            time:        leg?.dep ?? r.time ?? '',
            arrivalTime: leg?.arr ?? r.arrivalTime ?? '',
            fromCode:    leg?.fromCode ?? r.origin ?? '',
            fromCity:    leg?.fromCity ?? r.originCity ?? '',
            toCode:      leg?.toCode ?? r.destination ?? '',
            toCity:      leg?.toCity ?? r.destCity ?? '',
        }
    } else {
        formData = {
            airline:     leg?.airline ?? r.airline ?? '',
            flightNo:    leg?.flightNo ?? r.outboundFlight ?? '',
            class:       leg?.cls ?? 'Business',
            date:        leg?.date ?? r.outboundDate ?? '',
            duration:    leg?.dur ?? r.duration ?? '',
            time:        leg?.dep ?? r.outboundTime ?? '',
            arrivalTime: leg?.arr ?? r.outboundArrivalTime ?? '',
            fromCode:    leg?.fromCode ?? r.destination ?? '',
            fromCity:    leg?.fromCity ?? r.destCity ?? '',
            toCode:      leg?.toCode ?? r.origin ?? '',
            toCity:      leg?.toCity ?? r.originCity ?? '',
        }
    }
    
    // Open modal first
    legModal.value = true
    
    // Then populate form data after DOM update
    nextTick(() => {
        Object.assign(legForm, formData)
    })
}

function saveLeg() {
    const r = activeReq.value
    const leg = r.legs.find(l => l.dir === (editingLeg.value === 'inbound' ? 'Inbound' : 'Outbound'))
    
    if (!leg || !leg.id) {
        toast('Leg not found', 'error')
        return
    }

    isSavingLeg.value = true

    const legData = {
        airline: legForm.airline,
        flight_no: legForm.flightNo,
        cls: legForm.class,
        date: legForm.date,
        dep: legForm.time,
        arr: legForm.arrivalTime,
        dur: legForm.duration,
        from_code: legForm.fromCode,
        from_city: legForm.fromCity,
        to_code: legForm.toCode,
        to_city: legForm.toCity,
    }

    router.patch(route('gms.flights.legs.update', { id: r.id, legId: leg.id }), legData, {
        onSuccess: () => {
            // Update local state
            const idx = localReqs.value.findIndex(x => x.id === r.id)
            if (idx !== -1) {
                // Update the specific leg in the legs array
                const legIdx = localReqs.value[idx].legs.findIndex(l => l.id === leg.id)
                if (legIdx !== -1) {
                    Object.assign(localReqs.value[idx].legs[legIdx], {
                        airline: legForm.airline,
                        flightNo: legForm.flightNo,
                        cls: legForm.class,
                        date: legForm.date,
                        dep: legForm.time,
                        arr: legForm.arrivalTime,
                        dur: legForm.duration,
                        fromCode: legForm.fromCode,
                        fromCity: legForm.fromCity,
                        toCode: legForm.toCode,
                        toCity: legForm.toCity,
                    })
                }

                if (editingLeg.value === 'inbound') {
                    // Update inbound-specific fields (including shared class)
                    Object.assign(localReqs.value[idx], {
                        airline: legForm.airline,
                        inboundFlight: legForm.flightNo,
                        flightNo: legForm.flightNo,
                        class: legForm.class, // Only inbound updates the shared class
                        date: legForm.date,
                        duration: legForm.duration,
                        time: legForm.time,
                        arrivalTime: legForm.arrivalTime,
                        origin: legForm.fromCode,
                        originCity: legForm.fromCity,
                        destination: legForm.toCode,
                        destCity: legForm.toCity,
                    })
                } else {
                    // Update outbound-specific fields (NOT the shared class)
                    Object.assign(localReqs.value[idx], {
                        airline: legForm.airline,
                        outboundFlight: legForm.flightNo,
                        outboundDate: legForm.date,
                        duration: legForm.duration,
                        outboundTime: legForm.time,
                        outboundArrivalTime: legForm.arrivalTime,
                    })
                }
                activeReq.value = { ...localReqs.value[idx] }
            }
            isSavingLeg.value = false
            legModal.value = false
            toast('Leg updated.')
        },
        onError: () => {
            isSavingLeg.value = false
            toast('Failed to update leg.', 'error')
        },
        preserveScroll: true,
    })
}

// ── New request modal ─────────────────────────────────────────────
const newModal = ref(false)
const selectedGuestId = ref(null)

const form = useForm({
    guestId: '', origin: 'DOH', destination: '',
    class: 'Business', pax: 1,
    // Inbound leg
    inboundFlightNo: '', date: '', inboundDepTime: '', inboundArrTime: '', inboundDuration: '',
    // Outbound leg
    outboundFlightNo: '', outboundDate: '', outboundDepTime: '', outboundArrTime: '', outboundDuration: '',
    isChange: false,
})

// Calculate duration between two times in HH:MM format
function calculateDuration(depTime, arrTime) {
    if (!depTime || !arrTime) return ''
    
    const [depHour, depMin] = depTime.split(':').map(Number)
    const [arrHour, arrMin] = arrTime.split(':').map(Number)
    
    if (isNaN(depHour) || isNaN(depMin) || isNaN(arrHour) || isNaN(arrMin)) return ''
    
    let totalMinutes = (arrHour * 60 + arrMin) - (depHour * 60 + depMin)
    
    // Handle next-day arrivals (e.g., depart 23:00, arrive 02:00)
    if (totalMinutes < 0) {
        totalMinutes += 24 * 60
    }
    
    const hours = Math.floor(totalMinutes / 60)
    const minutes = totalMinutes % 60
    
    return `${hours}h ${minutes.toString().padStart(2, '0')}m`
}

// Auto-calculate inbound duration
watch(() => [form.inboundDepTime, form.inboundArrTime], ([depTime, arrTime]) => {
    form.inboundDuration = calculateDuration(depTime, arrTime)
})

// Auto-calculate outbound duration
watch(() => [form.outboundDepTime, form.outboundArrTime], ([depTime, arrTime]) => {
    form.outboundDuration = calculateDuration(depTime, arrTime)
})

// Auto-calculate duration for edit leg modal
watch(() => [legForm.time, legForm.arrivalTime], ([depTime, arrTime]) => {
    if (legModal.value && depTime && arrTime) {
        legForm.duration = calculateDuration(depTime, arrTime)
    }
})

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

// Guest IDs that already have flights
const existingFlightGuestIds = computed(() => {
    return localReqs.value
        .filter(f => f.status !== 'cancelled')
        .map(f => f.guestId)
})

const selectedGuest = computed(() => {
    if (bookingFromGuestRequest.value) {
        return { id: bookingFromGuestRequest.value.guestId, name: bookingFromGuestRequest.value.guestName, guestType: 'international', nationality: '' }
    }
    return selectedGuestId.value ? props.guests.find(g => g.id === selectedGuestId.value) : null
})

const selectedGuestTier = computed(() => {
    if (!selectedGuest.value) return null
    return props.tiers.find(t => t.id === selectedGuest.value.tier)
})

const defaultClassForTier = (tierName) => {
    if (tierName === 'Platinum') return 'First'
    if (tierName === 'Gold') return 'Business'
    return 'Economy'
}

function pickGuest(guest) {
    selectedGuestId.value = guest.id
    form.guestId = guest.id
    form.class = defaultClassForTier(selectedGuestTier.value?.name || '')
    form.pax = 1 + (guest.companionList?.length || 0)
    // Set destination from guest's nationality if available
    if (guest.nationality) {
        form.destination = guest.nationality.substring(0, 3).toUpperCase()
    }
}

function incrementPax() {
    if (form.pax < 9) form.pax++
}

function decrementPax() {
    if (form.pax > 1) form.pax--
}

// ── Guest request inbox ──────────────────────────────────────────
const pendingGuestRequests = computed(() =>
    localGuestReqs.value.filter(r => !r.fulfilledById)
)
const fulfilledGuestRequests = computed(() =>
    localGuestReqs.value.filter(r => r.fulfilledById)
)
const showFulfilled = ref(false)

const bookingFromGuestRequest = ref(null)

function bookFromRequest(r) {
    bookingFromGuestRequest.value = r

    selectedGuestId.value = r.guestId
    form.guestId = r.guestId

    const outboundLeg = r.legs?.find(l => l.dir === 'Outbound')
    const inboundLeg = r.legs?.find(l => l.dir === 'Inbound')

    form.origin = 'DOH'
    form.destination = outboundLeg?.fromCode !== 'XXX' ? outboundLeg?.fromCode : ''
    form.class = outboundLeg?.cls || inboundLeg?.cls || 'Business'
    form.pax = r.pax || 1
    form.date = inboundLeg?.date || ''
    form.outboundDate = outboundLeg?.date || ''
    form.inboundFlightNo = ''
    form.outboundFlightNo = ''
    form.inboundDepTime = ''
    form.inboundArrTime = ''
    form.outboundDepTime = ''
    form.outboundArrTime = ''

    newModal.value = true
}

function formatDateForBackend(dateStr) {
    if (!dateStr) return ''
    if (dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) return dateStr
    const parts = dateStr.split('/')
    if (parts.length === 3) {
        return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`
    }
    return dateStr
}

function saveNew() {
    const formData = {
        ...form.data(),
        date: formatDateForBackend(form.date),
        outboundDate: formatDateForBackend(form.outboundDate),
    }

    const targetRoute = bookingFromGuestRequest.value
        ? route('gms.flights.book-guest-request', bookingFromGuestRequest.value.id)
        : route('gms.flights.store')

    form.transform(() => formData).post(targetRoute, {
        onSuccess: () => {
            if (bookingFromGuestRequest.value) {
                const idx = localGuestReqs.value.findIndex(x => x.id === bookingFromGuestRequest.value.id)
                if (idx !== -1) {
                    localGuestReqs.value[idx] = { ...localGuestReqs.value[idx], fulfilledById: 'pending' }
                }
                bookingFromGuestRequest.value = null
            }
            newModal.value = false
            selectedGuestId.value = null
            toast('Flight request created.')
            form.reset()
            form.origin = 'DOH'
            form.isChange = false

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
            const firstError = Object.keys(errors)[0]
            const errorMessage = firstError ? `${firstError}: ${errors[firstError]}` : 'Failed to create flight request.'
            toast(errorMessage, 'error')
        },
    })
}
</script>

<template>
  <div class="gms-view">
    <div class="gms-view-pad">
    <!-- Header -->
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Flights</h1>
        <p class="gms-view-subtitle">Inbound &amp; outbound air travel for international guests of {{ event?.name ?? 'this event' }}.</p>
      </div>
      <div class="gms-view-actions">
        <button 
          class="gms-btn gms-btn-ghost gms-btn-sm" 
          @click="refreshFlights"
          :disabled="isRefreshing"
          title="Refresh flights"
          style="margin-right: 8px;"
        >
          <GmsIcon name="refresh-cw" :size="14" :class="{ 'gms-spin': isRefreshing }" />
        </button>
        <GmsBtn variant="ghost" icon="download" :iconSize="14">Export</GmsBtn>
        <GmsBtn variant="primary" icon="plus" :iconSize="14" @click="newModal = true">New flight request</GmsBtn>
      </div>
    </div>

    <!-- View toggle -->
    <div class="gms-tabs" style="margin-bottom: 20px;">
      <button class="gms-tab" :class="{ active: viewMode === 'queue' }" @click="viewMode = 'queue'">Request queue</button>
      <button class="gms-tab" :class="{ active: viewMode === 'inbox' }" @click="viewMode = 'inbox'">
        Guest requests
        <span v-if="pendingGuestRequests.length" class="gms-tab-badge">{{ pendingGuestRequests.length }}</span>
      </button>
      <button class="gms-tab" :class="{ active: viewMode === 'schedule' }" @click="viewMode = 'schedule'">Schedule</button>
    </div>

    <!-- Request queue view -->
    <template v-if="viewMode === 'queue'">

    <!-- Stats -->
    <div class="gms-stats" style="margin-bottom:24px;">
      <GmsMiniStat :value="countFor('new')"       label="New requests"    color="#a16207" />
      <GmsMiniStat :value="countFor('change')"    label="Change requests" color="#2563eb" />
      <GmsMiniStat :value="countFor('confirmed')" label="Confirmed"       color="#15803d" />
      <GmsMiniStat :value="countFor('cancelled')" label="Cancelled"       />
    </div>

    <!-- Toolbar -->
    <div class="gms-toolbar" style="margin-bottom:22px;">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search guest, PNR or flight…" />
      </div>
      <div class="gms-seg">
        <button v-for="t in tabs" :key="t.key" :class="{ on: statusFilter === t.key }" @click="statusFilter = t.key">
          {{ t.label }}<span v-if="t.key !== 'all'" class="gms-seg-count">{{ countFor(t.key) }}</span>
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
                    <div style="flex:1;">
                      <div style="display:flex;align-items:center;gap:6px;">
                        <div style="font-weight:600;font-size:13px;line-height:1.2;">{{ r.guestName }}</div>
                        <span v-if="r.source === 'portal'" class="portal-badge">
                          <GmsIcon name="globe" :size="10" />
                        </span>
                      </div>
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

    </template>

    <!-- ═══════════ GUEST REQUESTS INBOX ═══════════ -->
    <template v-if="viewMode === 'inbox'">
      <div class="gms-stats" style="margin-bottom:24px;">
        <GmsMiniStat :value="pendingGuestRequests.length" label="Pending requests" color="#a16207" />
        <GmsMiniStat :value="fulfilledGuestRequests.length" label="Booked" color="#15803d" />
      </div>

      <div v-if="fulfilledGuestRequests.length > 0" style="margin-bottom:16px;">
        <button class="gms-btn gms-btn-ghost gms-btn-sm" @click="showFulfilled = !showFulfilled">
          <GmsIcon :name="showFulfilled ? 'eye' : 'eye'" :size="13" />
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
                <span class="gr-pref-label">Route</span>
                <span class="gr-pref-value">
                  {{ r.legs?.find(l => l.dir === 'Outbound')?.fromCity || '—' }} → Doha
                </span>
              </div>
              <div class="gr-pref" v-if="r.legs?.find(l => l.dir === 'Inbound')?.date">
                <span class="gr-pref-label">Inbound</span>
                <span class="gr-pref-value">{{ fmtDateShort(r.legs.find(l => l.dir === 'Inbound')?.date) }}</span>
              </div>
              <div class="gr-pref" v-if="r.legs?.find(l => l.dir === 'Outbound')?.date">
                <span class="gr-pref-label">Return</span>
                <span class="gr-pref-value">{{ fmtDateShort(r.legs.find(l => l.dir === 'Outbound')?.date) }}</span>
              </div>
              <div class="gr-pref">
                <span class="gr-pref-label">Class</span>
                <span :class="classPillClass(r.class)">{{ r.class }}</span>
              </div>
              <div class="gr-pref">
                <span class="gr-pref-label">Pax</span>
                <span class="gr-pref-value">{{ r.pax }}</span>
              </div>
            </div>
            <div v-if="r.notes" class="gr-notes">"{{ r.notes }}"</div>
          </div>
          <div class="gr-actions">
            <GmsBtn variant="primary" icon="plane" :iconSize="13" @click="bookFromRequest(r)">Book flight</GmsBtn>
          </div>
        </div>

        <!-- Fulfilled requests (toggle) -->
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
                  <span class="gr-pref-label">Route</span>
                  <span class="gr-pref-value">
                    {{ r.legs?.find(l => l.dir === 'Outbound')?.fromCity || '—' }} → Doha
                  </span>
                </div>
                <div class="gr-pref">
                  <span class="gr-pref-label">Class</span>
                  <span :class="classPillClass(r.class)">{{ r.class }}</span>
                </div>
                <div class="gr-pref">
                  <span class="gr-pref-label">Pax</span>
                  <span class="gr-pref-value">{{ r.pax }}</span>
                </div>
              </div>
            </div>
            <div class="gr-actions">
              <span class="gr-fulfilled-tag"><GmsIcon name="check" :size="12" /> Booked</span>
            </div>
          </div>
        </template>

        <div v-if="!pendingGuestRequests.length && !showFulfilled" class="gms-empty" style="padding:40px 20px;">
          <GmsIcon name="plane" :size="32" style="opacity:.3;margin-bottom:12px;" />
          <div class="gms-empty-title">No pending guest requests</div>
          <div class="gms-empty-sub">Guest flight preferences submitted via the portal will appear here.</div>
        </div>
      </div>
    </template>

    <!-- Schedule view -->
    <template v-if="viewMode === 'schedule'">
      <div class="fl-sched">
        <div v-for="day in scheduleData" :key="day.date" class="fl-day">
          <div class="fl-day-h">
            <span class="fl-day-d">{{ day.date }}</span>
            <span class="gms-muted" style="font-size:12px;">
              {{ day.arrivals.length }} arrival{{ day.arrivals.length !== 1 ? 's' : '' }} · 
              {{ day.departures.length }} departure{{ day.departures.length !== 1 ? 's' : '' }}
            </span>
          </div>
          <div class="fl-day-cols">
            <!-- Arrivals column -->
            <div class="fl-col">
              <div class="fl-col-h arr">
                <span class="fl-pl">
                  <GmsIcon name="plane" :size="15" />
                </span>
                Arrivals
              </div>
              <div v-if="day.arrivals.length === 0" class="gms-muted" style="font-size:12px;padding:6px 2px;">None</div>
              <div
                v-for="(ev, i) in day.arrivals"
                :key="i"
                class="fl-ev"
                @click="openDrawer(ev.flight)"
              >
                <span class="fl-ev-t gms-mono">{{ ev.time.replace(/\s*\+1$/, '') }}</span>
                <GmsAvatar :name="ev.guest.name" size="sm" />
                <div style="flex:1;min-width:0;">
                  <div class="fl-ev-n">{{ ev.guest.name }}</div>
                  <div class="gms-muted" style="font-size:11px;font-family:var(--gms-font-mono);text-transform:uppercase;">
                    {{ ev.leg.fromCode }} → {{ ev.leg.toCode }} · {{ ev.leg.flightNo }}
                  </div>
                </div>
                <GmsPill
                  v-if="ev.flight.status !== 'confirmed'"
                  type="custom"
                  :value="displayStatus(ev.flight).label"
                  :bg="displayStatus(ev.flight).bg"
                  :fg="displayStatus(ev.flight).fg"
                />
              </div>

            <!-- Departures column -->
            </div>
            <div class="fl-col">
              <div class="fl-col-h dep">
                <span class="fl-pl">
                  <GmsIcon name="plane" :size="15" />
                </span>
                Departures
              </div>
              <div v-if="day.departures.length === 0" class="gms-muted" style="font-size:12px;padding:6px 2px;">None</div>
              <div
                v-for="(ev, i) in day.departures"
                :key="i"
                class="fl-ev"
                @click="openDrawer(ev.flight)"
              >
                <span class="fl-ev-t gms-mono">{{ ev.time.replace(/\s*\+1$/, '') }}</span>
                <GmsAvatar :name="ev.guest.name" size="sm" />
                <div style="flex:1;min-width:0;">
                  <div class="fl-ev-n">{{ ev.guest.name }}</div>
                  <div class="gms-muted" style="font-size:11px;font-family:var(--gms-font-mono);text-transform:uppercase;">
                    {{ ev.leg.fromCode }} → {{ ev.leg.toCode }} · {{ ev.leg.flightNo }}
                  </div>
                </div>
                <GmsPill
                  v-if="ev.flight.status !== 'confirmed'"
                  type="custom"
                  :value="displayStatus(ev.flight).label"
                  :bg="displayStatus(ev.flight).bg"
                  :fg="displayStatus(ev.flight).fg"
                />
              </div>
            </div>
          </div>
        </div>

        <div v-if="scheduleData.length === 0" class="gms-empty" style="margin-top:40px;">
          <div class="gms-empty-title">No flights scheduled</div>
          <div class="gms-empty-subtitle">Add flight requests to see the schedule</div>
        </div>
      </div>
    </template>

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
            <span class="flt-leg-dir flt-leg-dir-in">
              <GmsIcon name="plane" :size="15" />
              Inbound
            </span>
            <span class="flt-leg-code">{{ activeReq.inboundFlight }}</span>
          </div>
          <span class="flt-leg-date">{{ fmtDateShort(activeReq.date) }}</span>
        </div>
        <div class="flt-leg-route">
          <div class="flt-leg-end">
            <div class="flt-leg-iata">{{ activeReq.legs?.find(l => l.dir === 'Inbound')?.fromCode ?? activeReq.origin }}</div>
            <div class="flt-leg-city">{{ activeReq.legs?.find(l => l.dir === 'Inbound')?.fromCity ?? activeReq.originCity }}</div>
            <div class="flt-leg-time">{{ activeReq.time }}</div>
          </div>
          <div class="flt-leg-mid">
            <span class="flt-leg-dur">{{ activeReq.duration }}</span>
            <div class="flt-leg-line">
              <div class="flt-leg-dash" />
              <div class="flt-leg-plane-ico flt-leg-plane-in"><GmsIcon name="plane" :size="14" /></div>
              <div class="flt-leg-dash" />
            </div>
            <span class="flt-leg-carrier">{{ activeReq.airline }}</span>
          </div>
          <div class="flt-leg-end right">
            <div class="flt-leg-iata">{{ activeReq.legs?.find(l => l.dir === 'Inbound')?.toCode ?? activeReq.destination }}</div>
            <div class="flt-leg-city">{{ activeReq.legs?.find(l => l.dir === 'Inbound')?.toCity ?? activeReq.destCity }}</div>
            <div class="flt-leg-time">{{ activeReq.arrivalTime }}</div>
          </div>
        </div>
        <div class="flt-leg-foot">
          <div style="display:flex;align-items:center;gap:8px;">
            <span :class="classPillClass(activeReq.legs?.find(l => l.dir === 'Inbound')?.cls ?? activeReq.class)">{{ activeReq.legs?.find(l => l.dir === 'Inbound')?.cls ?? activeReq.class }}</span>
            <span class="flt-leg-terminal">{{ activeReq.inboundTerminal }}</span>
          </div>
          <button class="flt-edit-btn" @click="openEditLeg('inbound')"><GmsIcon name="edit" :size="12" /> Edit</button>
        </div>
      </div>

      <!-- Outbound leg -->
      <div class="flt-leg">
        <div class="flt-leg-header">
          <div class="flt-leg-tag">
            <span class="flt-leg-dir flt-leg-dir-out">
              <GmsIcon name="plane" :size="15" />
              Outbound
            </span>
            <span class="flt-leg-code">{{ activeReq.outboundFlight }}</span>
          </div>
          <span class="flt-leg-date">{{ fmtDateShort(activeReq.outboundDate) }}</span>
        </div>
        <div class="flt-leg-route">
          <div class="flt-leg-end">
            <div class="flt-leg-iata">{{ activeReq.legs?.find(l => l.dir === 'Outbound')?.fromCode ?? activeReq.destination }}</div>
            <div class="flt-leg-city">{{ activeReq.legs?.find(l => l.dir === 'Outbound')?.fromCity ?? activeReq.destCity }}</div>
            <div class="flt-leg-time">{{ activeReq.outboundTime }}</div>
          </div>
          <div class="flt-leg-mid">
            <span class="flt-leg-dur">{{ activeReq.duration }}</span>
            <div class="flt-leg-line">
              <div class="flt-leg-dash" />
              <div class="flt-leg-plane-ico flt-leg-plane-out"><GmsIcon name="plane" :size="14" /></div>
              <div class="flt-leg-dash" />
            </div>
            <span class="flt-leg-carrier">{{ activeReq.airline }}</span>
          </div>
          <div class="flt-leg-end right">
            <div class="flt-leg-iata">{{ activeReq.legs?.find(l => l.dir === 'Outbound')?.toCode ?? activeReq.origin }}</div>
            <div class="flt-leg-city">{{ activeReq.legs?.find(l => l.dir === 'Outbound')?.toCity ?? activeReq.originCity }}</div>
            <div class="flt-leg-time">{{ activeReq.outboundArrivalTime }}</div>
          </div>
        </div>
        <div class="flt-leg-foot">
          <div style="display:flex;align-items:center;gap:8px;">
            <span :class="classPillClass(activeReq.legs?.find(l => l.dir === 'Outbound')?.cls ?? activeReq.class)">{{ activeReq.legs?.find(l => l.dir === 'Outbound')?.cls ?? activeReq.class }}</span>
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
      <div style="margin-left:auto;display:flex;gap:8px;">
        <GmsBtn 
          variant="danger" 
          icon="trash-2" 
          :iconSize="13" 
          @click="deleteFlightRequest(activeReq)"
        >
          {{ confirmDelete ? 'Confirm delete?' : 'Delete' }}
        </GmsBtn>
        <GmsBtn v-if="canConfirm(activeReq)" variant="ghost" icon="x" :iconSize="13" @click="cancelBooking(activeReq)">Cancel</GmsBtn>
        <GmsBtn v-if="canConfirm(activeReq)" variant="primary" icon="check" :iconSize="13" @click="confirmRow(activeReq)">Confirm booking</GmsBtn>
        <GmsBtn v-if="!canConfirm(activeReq)" variant="primary" icon="refresh-cw" :iconSize="13" @click="reinstateBooking(activeReq)">Re-instate</GmsBtn>
      </div>
    </template>
  </GmsDrawer>

  <!-- ── New request modal ── -->
  <GmsModal :open="newModal" :title="bookingFromGuestRequest ? 'Book flight' : 'New Flight Request'" size="lg" @close="newModal = false; selectedGuestId = null; bookingFromGuestRequest = null">
    <div style="padding:0;">

      <!-- Booking-from-guest-request banner -->
      <div v-if="bookingFromGuestRequest" class="gr-booking-banner">
        <GmsIcon name="globe" :size="14" />
        <span>Booking from guest request {{ bookingFromGuestRequest.id }} — fill in flight details below</span>
      </div>

      <!-- Step 1: Guest selection -->
      <div class="nf-step">1 · Guest</div>
      <div style="margin:2px 0 10px;">
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
              Only guests who have confirmed their invitations can request flights. Confirm guest invitations first.
            </div>
          </div>
          <GmsGuestPicker
            v-else
            :guests="confirmedGuests"
            :selected-guest-id="selectedGuestId"
            :tiers="props.tiers"
            :show-existing-indicator="true"
            :existing-guest-ids="existingFlightGuestIds"
            existing-label="has flight"
            @select="pickGuest"
          />
        </template>
      </div>

      <!-- Step 2: Flight details (only shown when guest selected) -->
      <div :class="['nf-details', { 'off': !selectedGuest }]">
        <div class="nf-step" style="margin-top:20px;">2 · Flight details</div>
        <div v-if="!selectedGuest" class="gms-muted" style="font-size:12.5px;margin-top:6px;">
          Select a guest above to set their route.
        </div>
        <div v-else>
          <!-- Change request toggle -->
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input 
                type="checkbox" 
                v-model="form.isChange" 
                style="width:16px;height:16px;cursor:pointer;accent-color:var(--gms-maroon);"
              >
              <span style="font-size:13px;font-weight:500;">This is a change request</span>
            </label>
            <span 
              v-if="form.isChange" 
              class="gms-pill" 
              style="background:var(--warn);color:white;font-size:10.5px;font-weight:600;"
            >CHANGE</span>
          </div>

          <!-- Origin & Destination fields -->
          <div class="gms-form-grid" style="margin-bottom:14px;">
            <div class="gms-field">
              <label class="gms-label">Origin</label>
              <input 
                type="text" 
                v-model="form.destination" 
                class="gms-input" 
                placeholder="e.g. LHR, JFK, SYD" 
                style="text-transform:uppercase;font-family:var(--gms-font-mono);" 
                maxlength="3"
              >
              <div class="gms-hint" style="margin-top:4px;font-size:11px;">Guest's home airport (3-letter code)</div>
            </div>
            <div class="gms-field">
              <label class="gms-label">Destination</label>
              <input 
                type="text" 
                v-model="form.origin" 
                class="gms-input" 
                readonly 
                style="background:var(--gms-surface-2);cursor:not-allowed;font-family:var(--gms-font-mono);"
              >
              <div class="gms-hint" style="margin-top:4px;font-size:11px;">Doha (event venue)</div>
            </div>
          </div>

          <!-- Route display -->
          <div class="nf-od" style="margin-top:4px;">
            <span class="fl-od" style="font-size:15px;">
              <b>{{ form.destination || 'INT' }}</b>
              <span class="fl-swap">⇄</span>
              <b>{{ form.origin }}</b>
            </span>
            <span class="gms-muted" style="font-size:12.5px;">
              {{ selectedGuest.nationality || 'International' }} ↔ Doha · Qatar Airways
            </span>
            <span v-if="selectedGuest.guestType === 'local'" class="gms-pill" style="background:var(--warn-soft);color:var(--warn);margin-left:auto;font-size:10.5px;">local guest</span>
          </div>

          <!-- Form fields -->
          <div class="gms-form-grid" style="margin-top:14px;">
            <!-- Cabin class -->
            <div class="gms-field">
              <label class="gms-label">Cabin class</label>
              <div class="gms-seg">
                <button
                  v-for="cls in ['Economy', 'Business', 'First']"
                  :key="cls"
                  type="button"
                  :class="{ on: form.class === cls }"
                  @click="form.class = cls"
                >{{ cls }}</button>
              </div>
            </div>

            <!-- Passengers -->
            <div class="gms-field">
              <label class="gms-label">Passengers</label>
              <div class="nf-stepper">
                <button type="button" @click="decrementPax">−</button>
                <span class="tnum">{{ form.pax }}</span>
                <button type="button" @click="incrementPax">+</button>
                <span class="gms-muted" style="font-size:11.5px;margin-left:8px;">
                  {{ form.pax > 1 ? `guest + ${form.pax - 1}` : 'guest only' }}
                </span>
              </div>
            </div>

          </div>

          <!-- Inbound Flight Details -->
          <div class="gms-section-title" style="margin-top:20px;margin-bottom:10px;font-size:13px;">
            Inbound Flight (to Doha)
          </div>
          <div class="gms-form-grid">
            <div class="gms-field">
              <label class="gms-label">Flight Number</label>
              <input v-model="form.inboundFlightNo" class="gms-input" placeholder="QR 123" style="font-family:var(--gms-font-mono);" />
            </div>
            <div class="gms-field">
              <label class="gms-label">Date</label>
              <GmsDatePicker
                v-model="form.date"
                placeholder="Select date"
                date-format="d/m/Y"
              />
            </div>
            <div class="gms-field">
              <label class="gms-label">Departure Time</label>
              <GmsTimePicker
                v-model="form.inboundDepTime"
                placeholder="08:00"
              />
            </div>
            <div class="gms-field">
              <label class="gms-label">Arrival Time</label>
              <GmsTimePicker
                v-model="form.inboundArrTime"
                placeholder="12:00"
              />
            </div>
            <div class="gms-field">
              <label class="gms-label">Duration</label>
              <input v-model="form.inboundDuration" class="gms-input" placeholder="4h 00m" readonly style="font-family:var(--gms-font-mono);background:var(--gms-surface-2);cursor:not-allowed;" />
            </div>
          </div>

          <!-- Outbound Flight Details -->
          <div class="gms-section-title" style="margin-top:20px;margin-bottom:10px;font-size:13px;">
            Outbound Flight (from Doha)
          </div>
          <div class="gms-form-grid">
            <div class="gms-field">
              <label class="gms-label">Flight Number</label>
              <input v-model="form.outboundFlightNo" class="gms-input" placeholder="QR 456" style="font-family:var(--gms-font-mono);" />
            </div>
            <div class="gms-field">
              <label class="gms-label">Date</label>
              <GmsDatePicker
                v-model="form.outboundDate"
                placeholder="Select date"
                date-format="d/m/Y"
              />
            </div>
            <div class="gms-field">
              <label class="gms-label">Departure Time</label>
              <GmsTimePicker
                v-model="form.outboundDepTime"
                placeholder="14:00"
              />
            </div>
            <div class="gms-field">
              <label class="gms-label">Arrival Time</label>
              <GmsTimePicker
                v-model="form.outboundArrTime"
                placeholder="18:00"
              />
            </div>
            <div class="gms-field">
              <label class="gms-label">Duration</label>
              <input v-model="form.outboundDuration" class="gms-input" placeholder="4h 00m" readonly style="font-family:var(--gms-font-mono);background:var(--gms-surface-2);cursor:not-allowed;" />
            </div>
          </div>
        </div>
      </div>

    </div>
    <template #footer>
      <span class="gms-muted" style="font-size:12.5px;">
        {{ selectedGuest ? `${selectedGuest.name} · ${form.class} · ${form.pax} pax` : 'No guest selected' }}
      </span>
      <div style="margin-left:auto;display:flex;gap:10px;">
        <GmsBtn variant="ghost" @click="newModal = false; selectedGuestId = null">Cancel</GmsBtn>
        <GmsBtn variant="primary" :disabled="!selectedGuest || form.processing" @click="saveNew">
          <GmsIcon name="plus" :size="13" />
          Create request
        </GmsBtn>
      </div>
    </template>
  </GmsModal>

  <!-- ── Edit leg modal ── -->
  <GmsModal :open="legModal" :title="editingLeg === 'inbound' ? 'Edit inbound leg' : 'Edit outbound leg'" @close="legModal = false">
    <template v-if="activeReq">
      <!-- Origin & Destination -->
      <div class="gms-form-grid" style="margin-bottom:14px;">
        <div class="gms-field">
          <label class="gms-label">Origin code *</label>
          <input 
            v-model="legForm.fromCode" 
            class="gms-input" 
            placeholder="LHR" 
            maxlength="3"
            style="text-transform:uppercase;font-family:var(--gms-font-mono);"
          />
          <div class="gms-hint" style="margin-top:4px;font-size:11px;">3-letter airport code</div>
        </div>
        <div class="gms-field">
          <label class="gms-label">Origin city</label>
          <input 
            v-model="legForm.fromCity" 
            class="gms-input" 
            placeholder="London"
          />
        </div>
      </div>

      <div class="gms-form-grid" style="margin-bottom:14px;">
        <div class="gms-field">
          <label class="gms-label">Destination code *</label>
          <input 
            v-model="legForm.toCode" 
            class="gms-input" 
            placeholder="DOH" 
            maxlength="3"
            style="text-transform:uppercase;font-family:var(--gms-font-mono);"
          />
          <div class="gms-hint" style="margin-top:4px;font-size:11px;">3-letter airport code</div>
        </div>
        <div class="gms-field">
          <label class="gms-label">Destination city</label>
          <input 
            v-model="legForm.toCity" 
            class="gms-input" 
            placeholder="Doha"
          />
        </div>
      </div>

      <!-- Route preview -->
      <div style="padding:10px 12px;background:var(--gms-surface-2);border-radius:8px;margin-bottom:14px;">
        <div class="gms-muted" style="font-size:11px;margin-bottom:4px;">Route preview</div>
        <div style="font-weight:600;font-size:14px;font-family:var(--gms-font-mono);text-transform:uppercase;">
          {{ legForm.fromCode || '???' }} → {{ legForm.toCode || '???' }}
        </div>
        <div class="gms-muted" style="font-size:12px;margin-top:2px;">
          {{ legForm.fromCity || 'Origin' }} to {{ legForm.toCity || 'Destination' }}
        </div>
      </div>

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
          <GmsDatePicker v-model="legForm.date" placeholder="Select date" dateFormat="Y-m-d" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Duration</label>
          <input v-model="legForm.duration" class="gms-input" placeholder="Auto-calculated" readonly style="background: var(--gms-surface-2); cursor: not-allowed;" />
          <div class="gms-hint" style="margin-top:4px;font-size:11px;">Calculated from departure and arrival times</div>
        </div>
      </div>

      <!-- Departure + Arrival -->
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Departure *</label>
          <GmsTimePicker v-model="legForm.time" placeholder="Select time" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Arrival *</label>
          <GmsTimePicker v-model="legForm.arrivalTime" placeholder="Select time" />
        </div>
      </div>
      <p style="font-size:11.5px;color:var(--gms-text-3);margin-top:8px;">Use <code>+1</code> after a time to mark next-day arrival.</p>
    </template>

    <template #footer>
      <GmsBtn variant="ghost" @click="legModal = false" :disabled="isSavingLeg">Cancel</GmsBtn>
      <GmsBtn 
        variant="primary" 
        :disabled="!legForm.flightNo || !legForm.fromCode || !legForm.toCode || isSavingLeg" 
        @click="saveLeg"
      >
        <GmsIcon v-if="isSavingLeg" name="refresh-cw" :size="12" class="gms-spin" style="margin-right: 4px;" />
        {{ isSavingLeg ? 'Saving...' : 'Save leg' }}
      </GmsBtn>
    </template>
  </GmsModal>
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
