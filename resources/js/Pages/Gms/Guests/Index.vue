<script setup>
import { ref, computed, inject, onMounted, onUnmounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsPill from '@/Components/Gms/GmsPill.vue'
import GmsDrawer from '@/Components/Gms/GmsDrawer.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'
import GmsFilterDropdown from '@/Components/Gms/GmsFilterDropdown.vue'
import GmsDocUploader from '@/Components/Gms/GmsDocUploader.vue'
import GmsBtn from '@/Components/Gms/GmsBtn.vue'
import GmsMiniStat from '@/Components/Gms/GmsMiniStat.vue'
import GmsSearchableSelect from '@/Components/Gms/GmsSearchableSelect.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    guests:  { type: Array,  default: () => [] },
    tiers:   { type: Array,  default: () => [] },
    groups:  { type: Array,  default: () => [] },
    hosts:   { type: Array,  default: () => [] },
    hotels:  { type: Array,  default: () => [] },
    event:   { type: Object, default: () => ({}) },
    matches: { type: Array,  default: () => [] },
    venues:  { type: Array,  default: () => [] },
    emailTemplates: { type: Array, default: () => [] },
    nationalities: { type: Array, default: () => [] },
    errors:  { type: Object, default: () => ({}) },
    auth:    { type: Object, default: () => ({}) },
    flash:   { type: Object, default: () => ({}) },
    gmsEvents: { type: Array, default: () => [] },
})

const toast = inject('toast')

function parseJsonMaybe(value, fallback) {
  if (value == null) return fallback
  if (typeof value !== 'string') return value
  try { return JSON.parse(value) } catch { return fallback }
}

function normalizeFacilities(value) {
  const parsed = parseJsonMaybe(value, {})
  return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {}
}

function normalizeFacilityOverrides(value) {
  const parsed = parseJsonMaybe(value, { added: [], removed: [] })
  if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
    return { added: Array.isArray(parsed.added) ? parsed.added : [], removed: Array.isArray(parsed.removed) ? parsed.removed : [] }
  }
  return { added: [], removed: [] }
}

function normalizeGuest(g) {
  return {
    ...g,
    facilities: normalizeFacilities(g?.facilities),
    facilityOverrides: normalizeFacilityOverrides(g?.facilityOverrides),
    attendance: (g?.attendance && !Array.isArray(g.attendance)) ? g.attendance : {},
  }
}

// ── Local reactive guest list ─────────────────────────────────────
const localGuests = ref(props.guests.map(normalizeGuest))

// ── Filters ───────────────────────────────────────────────────────
const search            = ref('')
const scopeFilter       = ref('all') // 'all' | 'on' | 'off'
const tierFilter        = ref('all')
const nationalityFilter = ref('all')
const groupFilter       = ref('all')

// ── Selection ────────────────────────────────────────────────────
const selected = ref(new Set())

function toggleSelect(guestId) {
    if (selected.value.has(guestId)) selected.value.delete(guestId)
    else selected.value.add(guestId)
    selected.value = new Set(selected.value) // trigger reactivity
}

function toggleSelectAll() {
    if (allFilteredSelected.value) {
        selected.value = new Set()
    } else {
        selected.value = new Set(filtered.value.map(g => g.id))
    }
}

const allFilteredSelected = computed(() =>
    filtered.value.length > 0 && filtered.value.every(g => selected.value.has(g.id))
)

const selectedNotOnEvent = computed(() => {
    if (!props.event?.id) return 0
    return [...selected.value].filter(id => {
        const g = localGuests.value.find(x => x.id === id)
        return g && !g.attendance[String(props.event.id)]
    }).length
})


function clearSelection() { selected.value = new Set() }

const isAddingBulk = ref(false)

function addSelectedToEvent() {
    if (!props.event?.id || isAddingBulk.value) return
    const ids = [...selected.value].filter(id => {
        const g = localGuests.value.find(x => x.id === id)
        return g && !g.attendance[String(props.event.id)]
    })
    if (!ids.length) return

    isAddingBulk.value = true
    router.post(route('gms.guests.addToEvent'), { guest_ids: ids }, {
        preserveScroll: true,
        onSuccess: () => {
            ids.forEach(id => {
                const g = localGuests.value.find(x => x.id === id)
                if (g) g.attendance[String(props.event.id)] = { status: 'not_invited', added_at: new Date().toISOString() }
            })
            selected.value = new Set()
            isAddingBulk.value = false
            toast(`${ids.length} guest(s) added to ${props.event.name}`)
        },
        onError: () => { isAddingBulk.value = false; toast('Failed to add guests', 'error') },
    })
}

function addSingleToEvent(guestId) {
    if (!props.event?.id) return
    router.post(route('gms.guests.addToEvent'), { guest_ids: [guestId] }, {
        preserveScroll: true,
        onSuccess: () => {
            const g = localGuests.value.find(x => x.id === guestId)
            if (g) g.attendance[String(props.event.id)] = { status: 'not_invited', added_at: new Date().toISOString() }
            toast(`${g?.name ?? 'Guest'} added to ${props.event.name}`)
        },
        onError: () => toast('Failed to add guest', 'error'),
    })
}

function removeFromEvent(guestId, eventId) {
    router.post(route('gms.guests.removeFromEvent'), { guest_id: guestId }, {
        preserveScroll: true,
        onSuccess: () => {
            const g = localGuests.value.find(x => x.id === guestId)
            if (g) delete g.attendance[String(eventId)]
            toast(`${g?.name ?? 'Guest'} removed from ${eventName(eventId)}`)
        },
        onError: () => toast('Failed to remove guest', 'error'),
    })
}

// ── Helpers ──────────────────────────────────────────────────────
function isOnEvent(g) { return props.event?.id && !!g.attendance[String(props.event.id)] }

// ── Stats ───────────────────────────────────────────────────────
const onEventCount = computed(() =>
    localGuests.value.filter(g => isOnEvent(g)).length
)

const platinumCount = computed(() =>
    localGuests.value.filter(g => g.tier === 'T1' || g.tier === 'T2').length
)

const internationalCount = computed(() =>
    localGuests.value.filter(g => g.guestType === 'international').length
)

// ── Refresh ─────────────────────────────────────────────────────
const isRefreshing = ref(false)

function refreshGuests() {
    isRefreshing.value = true
    router.reload({
        only: ['guests'],
        onFinish: () => {
            setTimeout(() => {
                isRefreshing.value = false
                localGuests.value = props.guests.map(normalizeGuest)
                toast('Guests refreshed')
            }, 500)
        }
    })
}

const nationalities = computed(() => {
    const codes = [...new Set(localGuests.value.map(g => g.nationality).filter(Boolean))].sort()
    return codes.map(code => ({ value: code, label: countryName(code) }))
})

const filtered = computed(() => {
    let list = localGuests.value

    // Scope filter
    if (scopeFilter.value === 'on')  list = list.filter(g => isOnEvent(g))
    if (scopeFilter.value === 'off') list = list.filter(g => !isOnEvent(g))

    if (tierFilter.value !== 'all')        list = list.filter(g => g.tier === tierFilter.value)
    if (nationalityFilter.value !== 'all') list = list.filter(g => g.nationality === nationalityFilter.value)
    if (groupFilter.value !== 'all')       list = list.filter(g => g.group_id === groupFilter.value)
    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(g =>
            g.name.toLowerCase().includes(q) ||
            (g.email ?? '').toLowerCase().includes(q) ||
            (g.reference_number ?? '').toLowerCase().includes(q)
        )
    }
    return list
})

// ── Profile drawer ──────────────────────────────────────────────
const drawerOpen      = ref(false)
const activeGuest     = ref(null)
const actionsMenuOpen = ref(null)
const drawerTab       = ref('overview')

const guestVipLevel = computed(() => {
    if (!activeGuest.value) return null
    return ['T1', 'T2'].includes(activeGuest.value.tier) ? 'VVIP' : 'VIP'
})
const tierPickerOpen  = ref(false)
const companionsExpanded = ref(true)

// Get companions for the current event
const currentEventCompanions = computed(() => {
    if (!activeGuest.value || !props.event?.id) return []
    const attendance = activeGuest.value.attendance?.[String(props.event.id)]
    return attendance?.companions ?? []
})

// Get preference overrides for the current event and merge with global
const currentEventPreferences = computed(() => {
    if (!activeGuest.value || !props.event?.id) {
        // No event context, return global preferences
        return {
            flight: activeGuest.value?.flightPreferences ?? '',
            accommodation: activeGuest.value?.accommodationPreferences ?? '',
            transportation: activeGuest.value?.transportationPreferences ?? '',
            overrides: { flight: false, accommodation: false, transportation: false }
        }
    }
    const attendance = activeGuest.value.attendance?.[String(props.event.id)]
    const overrides = attendance?.preference_overrides ?? {}
    return {
        flight: overrides.flightPreferences ?? activeGuest.value?.flightPreferences ?? '',
        accommodation: overrides.accommodationPreferences ?? activeGuest.value?.accommodationPreferences ?? '',
        transportation: overrides.transportationPreferences ?? activeGuest.value?.transportationPreferences ?? '',
        overrides: {
            flight: overrides.flightPreferences != null,
            accommodation: overrides.accommodationPreferences != null,
            transportation: overrides.transportationPreferences != null
        }
    }
})

const preferencesExpanded = ref(true)
const invitationDetailModal = ref(false)
const activeInvitation = ref(null)

const newFacilityName = ref('')
const isSavingFacilities = ref(false)

function openDrawer(g) {
    activeGuest.value  = g
    drawerTab.value    = 'overview'
    tierPickerOpen.value = false
    companionsExpanded.value = true
    preferencesExpanded.value = true
    newFacilityName.value = ''
    invitationDetailModal.value = false
    activeInvitation.value = null
    drawerOpen.value   = true
}

function viewInvitationDetails(invitation) {
    activeInvitation.value = invitation
    invitationDetailModal.value = true
}

function getConfirmedMatches(guest) {
    if (!guest?.invitations) return []
    const confirmedMatches = []
    guest.invitations.forEach(invitation => {
        invitation.matches?.forEach(match => {
            if (match.response === 'yes') confirmedMatches.push({ invitation, match })
        })
    })
    return confirmedMatches
}

function getInvitationSummary(invitation) {
    if (!invitation.matches || invitation.matches.length === 0) return { text: 'No matches', color: 'var(--gms-text-3)' }
    const total = invitation.matches.length
    const responded = invitation.matches.filter(m => m.response).length
    const accepted = invitation.matches.filter(m => m.response === 'yes').length
    const declined = invitation.matches.filter(m => m.response === 'no').length
    if (responded === 0) return { text: 'Pending response', color: '#f59e0b', bg: '#fef3c7' }
    if (accepted === total) return { text: `All ${total} accepted`, color: '#10b981', bg: '#d1fae5' }
    if (declined === total) return { text: `All ${total} declined`, color: '#ef4444', bg: '#fee2e2' }
    if (responded < total) return { text: `${responded}/${total} responded`, color: '#f59e0b', bg: '#fef3c7' }
    return { text: `${accepted} accepted, ${declined} declined`, color: '#6366f1', bg: '#e0e7ff' }
}

function formatMatchDateTime(dateString) {
    if (!dateString) return 'TBD'
    const date = new Date(dateString)
    return date.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
}

function getGuestFacilities(guest) {
    if (!guest) return []
    const tier = tierFor(guest.tier)
    const tierFacilities = tier?.facilities ?? []
    const overrides = guest.facilityOverrides ?? { added: [], removed: [] }
    const baseFacilities = tierFacilities.filter(f => !(overrides.removed ?? []).includes(f))
    return [...baseFacilities, ...(overrides.added ?? [])]
}

function isTierFacility(guest, facility) {
    if (!guest) return false
    const tier = tierFor(guest.tier)
    return (tier?.facilities ?? []).includes(facility)
}

function isRemovedFacility(guest, facility) {
    if (!guest) return false
    const overrides = guest.facilityOverrides ?? { added: [], removed: [] }
    return (overrides.removed ?? []).includes(facility)
}

function toggleRemoveFacility(facility) {
    if (!activeGuest.value) return
    const overrides = activeGuest.value.facilityOverrides ?? { added: [], removed: [] }
    const removed = overrides.removed ?? []
    if (removed.includes(facility)) overrides.removed = removed.filter(f => f !== facility)
    else overrides.removed = [...removed, facility]
    activeGuest.value.facilityOverrides = overrides
    saveFacilityOverrides()
}

function addCustomFacility() {
    if (!activeGuest.value || !newFacilityName.value.trim()) return
    const overrides = activeGuest.value.facilityOverrides ?? { added: [], removed: [] }
    const added = overrides.added ?? []
    if (!added.includes(newFacilityName.value.trim())) {
        overrides.added = [...added, newFacilityName.value.trim()]
        activeGuest.value.facilityOverrides = overrides
        newFacilityName.value = ''
        saveFacilityOverrides()
    }
}

function removeCustomFacility(facility) {
    if (!activeGuest.value) return
    const overrides = activeGuest.value.facilityOverrides ?? { added: [], removed: [] }
    overrides.added = (overrides.added ?? []).filter(f => f !== facility)
    activeGuest.value.facilityOverrides = overrides
    saveFacilityOverrides()
}

function saveFacilityOverrides() {
    if (!activeGuest.value || isSavingFacilities.value) return
    isSavingFacilities.value = true
    const idx = localGuests.value.findIndex(g => g.id === activeGuest.value.id)
    if (idx !== -1) localGuests.value[idx].facilityOverrides = activeGuest.value.facilityOverrides

    router.put(route('gms.guests.update', activeGuest.value.id), {
        name: activeGuest.value.name, firstName: activeGuest.value.firstName, lastName: activeGuest.value.lastName,
        title: activeGuest.value.title, guestType: activeGuest.value.guestType, qid: activeGuest.value.qid,
        tier: activeGuest.value.tier, group_id: activeGuest.value.group_id, nationality: activeGuest.value.nationality,
        email: activeGuest.value.email, phone: activeGuest.value.phone,
        host: activeGuest.value.host, hotel: activeGuest.value.hotel, dietaryNotes: activeGuest.value.dietaryNotes,
        notes: activeGuest.value.notes, flightPreferences: activeGuest.value.flightPreferences,
        accommodationPreferences: activeGuest.value.accommodationPreferences,
        transportationPreferences: activeGuest.value.transportationPreferences,
        companions: props.event?.id && activeGuest.value.attendance?.[String(props.event.id)]?.companions || [],
        facilities: activeGuest.value.facilities, facilityOverrides: activeGuest.value.facilityOverrides,
    }, {
        onSuccess: () => { isSavingFacilities.value = false; toast('Facilities updated') },
        onError: (errors) => {
            isSavingFacilities.value = false
            toast(Object.values(errors)[0] || 'Failed to update facilities', 'error')
        },
        preserveScroll: true,
    })
}

function pickTier(tierId) {
    if (!activeGuest.value) return
    const idx = localGuests.value.findIndex(g => g.id === activeGuest.value.id)
    if (idx !== -1) { localGuests.value[idx].tier = tierId; activeGuest.value = { ...localGuests.value[idx] } }
    tierPickerOpen.value = false
    toast(`Service level updated to ${tierFor(tierId)?.name}.`)
}

const serviceModules = [
    { id: 'flights',   label: 'Flights',             icon: 'plane'    },
    { id: 'accomm',    label: 'Accommodation',        icon: 'building' },
    { id: 'seating',   label: 'Seating',              icon: 'ticket'   },
    { id: 'transport', label: 'Transport',            icon: 'car'      },
    { id: 'arrival',   label: 'Arrival & Departure',  icon: 'arrivals', full: true },
]

const tierServices = {
    T1: ['flights', 'accomm', 'seating', 'transport', 'arrival'],
    T2: ['flights', 'accomm', 'seating', 'transport', 'arrival'],
    T3: ['accomm', 'seating', 'transport', 'arrival'],
    T4: ['seating', 'transport'],
    T5: ['seating'],
}

function guestServices(g) {
    const included = tierServices[g.tier] ?? []
    const rawMap = {
        flights: g.serviceStatuses?.flights,
        accomm: g.serviceStatuses?.accommodation,
        seating: g.serviceStatuses?.seating,
        transport: g.serviceStatuses?.transport,
        arrival: g.serviceStatuses?.arrival,
    }
    return serviceModules.filter(m => included.includes(m.id)).map(m => {
        const raw = rawMap[m.id]
        const isObj = raw && typeof raw === 'object'
        const status = isObj ? raw.status : (raw || 'Not requested')
        const source = isObj ? raw.source : null
        const fulfilled = isObj ? raw.fulfilled : null
        const isGuestRequest = source === 'portal' && fulfilled !== true
        const service = { ...m, status, source, isGuestRequest }
        if (m.id === 'seating') service.seatInfo = getGuestSeats(g)
        if (m.id === 'flights') service.flightInfo = g.flightInfo || []
        return service
    })
}

function getGuestSeats(g) {
    if (!g?.seatAssignments || g.seatAssignments.length === 0) return []
    // Get confirmed matches from invitations
    const confirmedMatches = getConfirmedMatches(g)
    const confirmedMatchIds = new Set(confirmedMatches.map(cm => cm.match.id))
    
    // Filter seat assignments to only confirmed matches
    return g.seatAssignments.filter(seat => confirmedMatchIds.has(seat.match_id))
}

function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }
function tierFor(id)  { return props.tiers.find(t => t.id === id) }
function groupFor(id) { return props.groups.find(g => g.id === id) }
function hostFor(id)  { return props.hosts.find(h => h.id === id) }

// ── Event pill helpers ──────────────────────────────────────────
function eventName(eventId) {
    const ev = props.gmsEvents?.find(e => String(e.id) === String(eventId))
    return ev?.name ?? `Event ${eventId}`
}

function isCurrentEvent(eventId) {
    return props.event?.id && String(eventId) === String(props.event.id)
}

// ── Create / Edit modal ─────────────────────────────────────────
const guestModal   = ref(false)
const editingGuest = ref(null)
const companions = ref([])
const GUEST_RELATIONS = ['Spouse', 'Family', 'Aide', 'Security', 'Delegate', 'Interpreter', 'Companion']

// Guest documents (per event)
const guestDocs = ref({ passport_no: '', personal_photo: '', passport_front: '' })

// Preference override state
const prefOverrideActive = ref({ flight: false, accommodation: false, transportation: false })
const prefOverrideValues = ref({ flight: '', accommodation: '', transportation: '' })

const form = useForm({
    name: '', firstName: '', lastName: '', title: '', guestType: 'local', qid: '',
    tier: props.tiers[0]?.id ?? '', group_id: '', host: '', hotel: '', nationality: '',
    email: '', phone: '', dietaryNotes: '', notes: '',
    flightPreferences: '', accommodationPreferences: '', transportationPreferences: '',
})

function openNew() {
    editingGuest.value = null; form.reset()
    form.tier = props.tiers[0]?.id ?? ''
    companions.value = []
    guestDocs.value = { passport_no: '', personal_photo: '', passport_front: '' }
    prefOverrideActive.value = { flight: false, accommodation: false, transportation: false }
    prefOverrideValues.value = { flight: '', accommodation: '', transportation: '' }
    guestModal.value = true
}

function openEdit(g, fromDrawer = false) {
    const guest = normalizeGuest(g); editingGuest.value = guest
    form.name = guest.name; form.firstName = guest.firstName ?? ''; form.lastName = guest.lastName ?? ''
    form.title = guest.title ?? ''; form.guestType = guest.guestType ?? 'local'; form.qid = guest.qid ?? ''
    form.tier = guest.tier; form.group_id = guest.group_id ?? ''; form.host = guest.host ?? ''
    form.hotel = guest.hotel ?? ''; form.nationality = guest.nationality ?? ''
    form.email = guest.email ?? ''; form.phone = guest.phone ?? ''
    form.dietaryNotes = guest.dietaryNotes ?? ''; form.notes = guest.notes ?? ''
    form.flightPreferences = guest.flightPreferences ?? ''
    form.accommodationPreferences = guest.accommodationPreferences ?? ''
    form.transportationPreferences = guest.transportationPreferences ?? ''
    // Load companions from current event's pivot data
    const eventCompanions = props.event?.id && guest.attendance?.[String(props.event.id)]?.companions || []
    companions.value = eventCompanions.map(c => ({ name: c?.name ?? '', relation: c?.relation ?? 'Companion', passport_no: c?.passport_no ?? '', personal_photo: c?.personal_photo ?? '', passport_front: c?.passport_front ?? '' }))
    // Load guest documents from pivot
    const att = props.event?.id && guest.attendance?.[String(props.event.id)] || {}
    guestDocs.value = { passport_no: att.passport_no ?? '', personal_photo: att.personal_photo ?? '', passport_front: att.passport_front ?? '' }
    // Load preference overrides from current event's pivot data
    const eventOverrides = props.event?.id && guest.attendance?.[String(props.event.id)]?.preference_overrides || {}
    prefOverrideActive.value = {
        flight: eventOverrides.flightPreferences != null,
        accommodation: eventOverrides.accommodationPreferences != null,
        transportation: eventOverrides.transportationPreferences != null
    }
    prefOverrideValues.value = {
        flight: eventOverrides.flightPreferences ?? '',
        accommodation: eventOverrides.accommodationPreferences ?? '',
        transportation: eventOverrides.transportationPreferences ?? ''
    }
    if (fromDrawer) drawerOpen.value = false
    guestModal.value = true
}

function saveGuest() {
    if (form.firstName || form.lastName) form.name = (form.firstName + ' ' + form.lastName).trim()
    const payload = { ...form.data() }
    const companionList = companions.value.filter(c => c.name.trim()).map(c => ({
        name: c.name.trim(), relation: c.relation || 'Companion',
        passport_no: c.passport_no || '', personal_photo: c.personal_photo || '', passport_front: c.passport_front || '',
    }))
    payload.companions = companionList
    payload.event_id = props.event?.id
    payload.passport_no = guestDocs.value.passport_no
    payload.personal_photo = guestDocs.value.personal_photo
    payload.passport_front = guestDocs.value.passport_front

    // Build preference_overrides object (only send if on an event and guest exists)
    if (props.event?.id && editingGuest.value) {
        const preferenceOverrides = {}
        if (prefOverrideActive.value.flight) preferenceOverrides.flightPreferences = prefOverrideValues.value.flight
        if (prefOverrideActive.value.accommodation) preferenceOverrides.accommodationPreferences = prefOverrideValues.value.accommodation
        if (prefOverrideActive.value.transportation) preferenceOverrides.transportationPreferences = prefOverrideValues.value.transportation
        payload.preference_overrides = Object.keys(preferenceOverrides).length > 0 ? preferenceOverrides : null
    }

    if (editingGuest.value) {
        const idx = localGuests.value.findIndex(g => g.id === editingGuest.value.id)
        if (idx !== -1) {
            localGuests.value[idx] = { ...localGuests.value[idx], ...payload }
            // Update attendance companions and preference_overrides for current event
            if (props.event?.id && localGuests.value[idx].attendance?.[String(props.event.id)]) {
                localGuests.value[idx].attendance[String(props.event.id)].companions = companionList
                localGuests.value[idx].attendance[String(props.event.id)].preference_overrides = payload.preference_overrides
            }
            if (activeGuest.value?.id === editingGuest.value.id) activeGuest.value = localGuests.value[idx]
        }
        form.transform(() => payload).put(route('gms.guests.update', editingGuest.value.id), {
            onSuccess: () => { guestModal.value = false; toast('Guest updated.'); form.transform(data => data) },
            onError: (errors) => { toast(Object.values(errors)[0] || 'Failed to save.', 'error'); form.transform(data => data) },
            preserveScroll: true,
        })
    } else {
        const newGuest = { 
            id: 'G' + Date.now(), 
            ...payload, 
            attendance: props.event?.id ? {
                [String(props.event.id)]: {
                    status: 'Not Invited',
                    added_at: new Date().toISOString(),
                    companions: companionList
                }
            } : {}
        }
        localGuests.value.unshift(newGuest)
        form.transform(() => payload).post(route('gms.guests.store'), {
            onSuccess: () => { guestModal.value = false; toast('Guest created.'); form.transform(data => data) },
            onError: (errors) => { toast(Object.values(errors)[0] || 'Failed to save.', 'error'); form.transform(data => data) },
            preserveScroll: true,
        })
    }
}

// ── Delete ──────────────────────────────────────────────────────
const deleteModal = ref(false)
const deletingId  = ref(null)
function openDelete(id) { deletingId.value = id; deleteModal.value = true }
function confirmDelete() {
    localGuests.value = localGuests.value.filter(g => g.id !== deletingId.value)
    if (activeGuest.value?.id === deletingId.value) drawerOpen.value = false
    router.delete(route('gms.guests.destroy', deletingId.value), {
        onSuccess: () => { deleteModal.value = false; toast('Guest deleted.') },
        onError: () => toast('Failed to delete.', 'error'),
        preserveScroll: true,
    })
}

// ── Import ──────────────────────────────────────────────────────
const importModal = ref(false), importFile = ref(null), importFileInput = ref(null), isImporting = ref(false)
function openImport() { importModal.value = true; importFile.value = null }
function handleFileChange(event) {
    const file = event.target.files[0]
    if (file) {
        const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv']
        if (!validTypes.includes(file.type)) { toast('Please select a valid Excel or CSV file', 'error'); return }
        if (file.size > 10 * 1024 * 1024) { toast('File size must be less than 10MB', 'error'); return }
        importFile.value = file
    }
}
function confirmImport() {
    if (!importFile.value) { toast('Please select a file to import', 'error'); return }
    isImporting.value = true
    const formData = new FormData(); formData.append('file', importFile.value)
    router.post(route('gms.guests.import'), formData, {
        onSuccess: () => { importModal.value = false; importFile.value = null; isImporting.value = false; refreshGuests() },
        onError: (errors) => { isImporting.value = false; toast(Object.values(errors)[0] || 'Import failed', 'error') },
        preserveScroll: true,
    })
}

// ── Helpers ─────────────────────────────────────────────────────
const menuPosition = ref({ top: 0, left: 0 })
function toggleActionsMenu(guestId, event) {
    if (actionsMenuOpen.value === guestId) {
        actionsMenuOpen.value = null
        return
    }
    actionsMenuOpen.value = guestId
    // Calculate position for teleported menu (fixed positioning)
    const rect = event.currentTarget.getBoundingClientRect()
    const menuWidth = 180 // Approximate menu width
    const viewportWidth = window.innerWidth
    const viewportHeight = window.innerHeight
    
    // Calculate top position
    let top = rect.bottom + 4
    
    // Calculate left position - align to right edge of button, but keep on screen
    let left = rect.right - menuWidth
    
    // If menu would go off left edge, align to left edge of button
    if (left < 10) {
        left = rect.left
    }
    
    // If menu would go off right edge, align to right edge
    if (left + menuWidth > viewportWidth - 10) {
        left = viewportWidth - menuWidth - 10
    }
    
    // If menu would go off bottom, open upward
    const menuHeight = 120 // Approximate menu height
    if (top + menuHeight > viewportHeight - 10) {
        top = rect.top - menuHeight - 4
    }
    
    menuPosition.value = { top, left }
}

const flagEmoji = (cc) => {
    if (!cc) return ''
    return cc.toUpperCase().replace(/./g, c => String.fromCodePoint(0x1F1E6 - 65 + c.charCodeAt(0)))
}
const countryName = (cc) => {
    if (!cc) return ''
    const country = props.nationalities.find(c => c.value === cc.toUpperCase())
    return country ? country.label : cc
}

function handleClickOutside(e) {
    // Handle teleported actions menu
    if (actionsMenuOpen.value) {
        const menu = document.querySelector('.gms-menu-pop')
        const button = e.target.closest('.gms-btn-icon[title="More actions"]')
        if (!button && (!menu || !menu.contains(e.target))) {
            actionsMenuOpen.value = null
        }
    }
    if (tierPickerOpen.value && !e.target.closest('.tier-pick-pop') && !e.target.closest('.gd-change-btn')) tierPickerOpen.value = false
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <div class="gms-view">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Master Guest List</h1>
        <p class="gms-view-subtitle">Central directory of every VIP &amp; VVIP — reused across all events. Add people to {{ event?.name ?? 'the event' }} to invite them.</p>
      </div>
      <div class="gms-view-actions">
        <button class="gms-btn gms-btn-ghost" @click="refreshGuests" :disabled="isRefreshing" title="Refresh">
          <GmsIcon name="refresh-cw" :size="14" :class="{ 'gms-spin': isRefreshing }" />
        </button>
        <GmsBtn icon="upload" @click="openImport">Bulk upload</GmsBtn>
        <GmsBtn icon="download">Export</GmsBtn>
        <GmsBtn variant="primary" icon="plus" :icon-size="14" @click="openNew">New guest</GmsBtn>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="gms-stats">
      <GmsMiniStat label="In directory" :value="localGuests.length" color="#8a1f3d" />
      <GmsMiniStat :label="'On ' + (event?.name ?? 'event')" :value="onEventCount" color="#3a6a8a" />
      <GmsMiniStat label="Platinum (VVIP)" :value="platinumCount" color="#5b4a8a" />
      <GmsMiniStat label="International" :value="internationalCount" color="#9a7430" />
    </div>

    <!-- Toolbar -->
    <div class="gms-toolbar">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search name, email or GMS-ID…" />
      </div>

      <!-- Scope filter -->
      <div class="gms-seg">
        <button :class="{ on: scopeFilter === 'all' }" @click="scopeFilter = 'all'">All</button>
        <button :class="{ on: scopeFilter === 'on' }" @click="scopeFilter = 'on'">On this event</button>
        <button :class="{ on: scopeFilter === 'off' }" @click="scopeFilter = 'off'">Not on event</button>
      </div>

      <GmsFilterDropdown v-model="nationalityFilter" label="Nationality" all-label="All nationalities" :options="nationalities" value-key="value" label-key="label">
        <template #item="{ option }">{{ flagEmoji(option.value) }} {{ option.label }}</template>
      </GmsFilterDropdown>

      <GmsFilterDropdown v-model="groupFilter" label="Group" all-label="All groups" :options="groups" />

      <span class="mxt-count" style="margin-left: auto;">{{ filtered.length }} of {{ localGuests.length }}</span>
    </div>

    <!-- Guest table -->
    <div class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-table-wrap">
          <table class="gms-table">
            <thead>
              <tr>
                <th style="width:36px;padding-right:0;">
                  <label class="dir-check-wrap" @click.stop>
                    <input type="checkbox" :checked="allFilteredSelected" @change="toggleSelectAll" class="dir-check" />
                  </label>
                </th>
                <th>Guest</th>
                <th>GMS-ID</th>
                <th>Type</th>
                <th>Group</th>
                <th>Tier</th>
                <th>Events</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="g in filtered" :key="g.id" :class="{ 'dir-row-sel': selected.has(g.id) }" @click="openDrawer(g)">
                <td style="width:36px;padding-right:0;" @click.stop>
                  <label class="dir-check-wrap">
                    <input type="checkbox" :checked="selected.has(g.id)" @change="toggleSelect(g.id)" class="dir-check" />
                  </label>
                </td>
                <td>
                  <div style="display:flex;align-items:center;gap:9px;">
                    <GmsAvatar :name="g.name" />
                    <div>
                      <div style="font-weight:600;font-size:13.5px;">{{ g.name }}</div>
                      <div style="font-size:11px;color:var(--gms-text-3);">{{ g.email || '—' }}</div>
                    </div>
                  </div>
                </td>
                <td><span class="gms-muted gms-small" style="font-family:var(--gms-font-mono);">{{ g.reference_number || '—' }}</span></td>
                <td>
                  <span class="gms-muted gms-small">
                    <span style="font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">{{ g.nationality }}</span>
                    {{ capitalize(g.guestType || 'local').replace('local','Local').replace('international',"Int'l") }}
                  </span>
                </td>
                <td><span class="gms-muted gms-small">{{ groupFor(g.group_id)?.label ?? '—' }}</span></td>
                <td>
                  <span class="gms-pill" :style="{ background: tierFor(g.tier)?.bg ?? '#f3f4f6', color: tierFor(g.tier)?.color ?? '#374151', fontSize: '10.5px' }">{{ tierFor(g.tier)?.name ?? g.tier }}</span>
                </td>
                <td @click.stop>
                  <div class="dir-ev-chips">
                    <span
                      v-for="(att, evId) in g.attendance" :key="evId"
                      class="dir-ev-chip"
                      :class="{ current: isCurrentEvent(evId) }"
                    >
                      <span class="dir-ev-dot" :style="{ background: isCurrentEvent(evId) ? 'var(--maroon)' : 'var(--good)' }"></span>
                      {{ eventName(evId) }}
                      <button class="dir-ev-x" @click="removeFromEvent(g.id, evId)" title="Remove from event">
                        <GmsIcon name="x" :size="10" />
                      </button>
                    </span>
                    <button
                      v-if="!isOnEvent(g)"
                      class="gms-btn gms-btn-ghost gms-btn-sm dir-add-btn"
                      @click="addSingleToEvent(g.id)"
                    >
                      <GmsIcon name="plus" :size="12" />
                      Add
                    </button>
                  </div>
                </td>
                <td @click.stop>
                  <div class="gms-act-row">
                    <button v-if="isOnEvent(g)" class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" title="Send invitation" @click="router.visit(route('gms.invitations.index'))">
                      <GmsIcon name="mail" :size="14" />
                    </button>
                    <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" @click="toggleActionsMenu(g.id, $event)" title="More actions">
                      <GmsIcon name="more-vertical" :size="14" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!filtered.length">
                <td colspan="8">
                  <div class="gms-empty">
                    <div class="gms-empty-title">No guests match this filter</div>
                    <div class="gms-empty-sub">Try adjusting your search or filters.</div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Selection action bar -->
    <Transition name="dir-bar">
      <div v-if="selected.size > 0" class="dir-action-bar">
        <div class="dir-bar-info">
          <strong>{{ selected.size }} selected</strong>
          <span v-if="selectedNotOnEvent > 0" class="dir-bar-sub">{{ selectedNotOnEvent }} not yet on {{ event?.name }}</span>
        </div>
        <div class="dir-bar-actions">
          <GmsBtn @click="clearSelection">Clear</GmsBtn>
          <GmsBtn
            v-if="selectedNotOnEvent > 0"
            variant="primary"
            icon="plus"
            :disabled="isAddingBulk"
            :processing="isAddingBulk"
            @click="addSelectedToEvent"
          >
            Add {{ selectedNotOnEvent }} to {{ event?.name }}
          </GmsBtn>
        </div>
      </div>
    </Transition>
  </div>

  <!-- ── Profile Drawer ──────────────────────────────────────────── -->
  <GmsDrawer :open="drawerOpen" @close="drawerOpen = false">
    <template v-if="activeGuest" #header>
      <GmsAvatar :name="activeGuest.name" size="xl" />
      <div class="gd-hinfo">
        <div class="gd-name-row">
          <span class="gd-name">{{ activeGuest.name }}</span>
          <span class="gms-pill" :style="{ background: guestVipLevel === 'VVIP' ? '#8a1f3d' : '#5b4a8a', color: '#ffffff', fontSize: '11px', fontWeight: '700' }">{{ guestVipLevel }}</span>
          <span class="gms-pill" :style="{ background: tierFor(activeGuest.tier)?.bg, color: tierFor(activeGuest.tier)?.color, fontSize: '11px' }">{{ tierFor(activeGuest.tier)?.name ?? activeGuest.tier }}</span>
        </div>
        <div class="gd-meta">
          <span style="font-family:var(--gms-font-mono);font-size:11px;">{{ activeGuest.reference_number || activeGuest.id }}</span>
          <span class="gd-meta-dot">·</span>
          <span>{{ flagEmoji(activeGuest.nationality) }} {{ countryName(activeGuest.nationality) }}</span>
          <span class="gd-meta-dot">·</span>
          <span>{{ activeGuest.guestType === 'international' ? 'International' : 'Local' }}</span>
        </div>
        <!-- Event attendance status for current event -->
        <div v-if="event?.id && activeGuest.attendance?.[String(event.id)]" style="display:flex;align-items:center;gap:6px;margin-top:8px;">
          <span style="font-size:11px;color:var(--gms-text-3);">Status:</span>
          <span class="gms-pill" style="background:var(--gms-maroon-tint);color:var(--gms-maroon);font-size:11px;font-weight:600;">
            {{ activeGuest.attendance[String(event.id)].status }}
          </span>
        </div>
      </div>
    </template>

    <template v-if="activeGuest">
      <div class="gms-seg gd-tabs">
        <button :class="{ on: drawerTab === 'overview' }" @click="drawerTab = 'overview'">Overview</button>
        <button :class="{ on: drawerTab === 'preferences' }" @click="drawerTab = 'preferences'">Preferences</button>
        <button :class="{ on: drawerTab === 'facilities' }" @click="drawerTab = 'facilities'">Facilities</button>
        <button :class="{ on: drawerTab === 'invitations' }" @click="drawerTab = 'invitations'">Invitations</button>
      </div>

      <!-- ── Overview tab ── -->
      <template v-if="drawerTab === 'overview'">
        <div class="gd-section-head">Contact</div>
        <div class="gms-detail-row"><span class="gms-detail-label">Email</span><span class="gms-detail-value" style="font-family:var(--gms-font-mono);font-size:12px;">{{ activeGuest.email || '—' }}</span></div>
        <div class="gms-detail-row"><span class="gms-detail-label">Group</span><span class="gms-detail-value">{{ groupFor(activeGuest.group_id)?.label ?? '—' }}</span></div>
        <div class="gms-detail-row"><span class="gms-detail-label">Hosted by</span><span class="gms-detail-value">{{ hostFor(activeGuest.host)?.name ?? '—' }}</span></div>
        <div class="gms-detail-row"><span class="gms-detail-label">Title</span><span class="gms-detail-value">{{ activeGuest.title || '—' }}</span></div>

        <div v-if="currentEventCompanions.length > 0" style="margin-top:20px;">
          <div class="gd-section-head gd-collapsible" @click="companionsExpanded = !companionsExpanded">
            <div style="display:flex;align-items:center;gap:8px;"><span>Companions</span><span class="gms-pill" style="font-size:11px;">{{ currentEventCompanions.length }}</span></div>
            <GmsIcon :name="companionsExpanded ? 'chevron-up' : 'chevron-down'" :size="16" style="color:var(--gms-text-3);" />
          </div>
          <div v-show="companionsExpanded" style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
            <div v-for="(comp, idx) in currentEventCompanions" :key="idx" class="gd-comp-card">
              <div class="gd-comp-row">
                <span class="gd-comp-name">{{ comp.name }}</span>
                <span class="gd-comp-rel" style="color: white; background: black;">{{ comp.relation }}</span>
              </div>
              <div v-if="comp.passport_no || comp.personal_photo || comp.passport_front" class="gd-comp-docs">
                <span v-if="comp.passport_no" class="gd-doc-chip"><GmsIcon name="eye" :size="10" /> {{ comp.passport_no }}</span>
                <a v-if="comp.personal_photo" :href="route('gms.api.document', { path: comp.personal_photo })" target="_blank" class="gd-doc-chip gd-doc-link"><GmsIcon name="user" :size="10" /> Photo</a>
                <a v-if="comp.passport_front" :href="route('gms.api.document', { path: comp.passport_front })" target="_blank" class="gd-doc-chip gd-doc-link"><GmsIcon name="eye" :size="10" /> Passport</a>
              </div>
            </div>
          </div>
        </div>

        <!-- Guest Documents -->
        <div v-if="activeGuest.attendance?.[String(event?.id)]?.passport_no || activeGuest.attendance?.[String(event?.id)]?.personal_photo || activeGuest.attendance?.[String(event?.id)]?.passport_front" style="margin-top:20px;">
          <div class="gd-section-head">Documents</div>
          <div class="gd-docs-grid">
            <div v-if="activeGuest.attendance[String(event.id)].passport_no" class="gd-doc-item">
              <div class="gd-doc-label">Passport No.</div>
              <div class="gd-doc-value" style="font-family:var(--gms-font-mono);">{{ activeGuest.attendance[String(event.id)].passport_no }}</div>
            </div>
            <a v-if="activeGuest.attendance[String(event.id)].personal_photo" :href="route('gms.api.document', { path: activeGuest.attendance[String(event.id)].personal_photo })" target="_blank" class="gd-doc-item gd-doc-clickable">
              <img :src="route('gms.api.document', { path: activeGuest.attendance[String(event.id)].personal_photo })" class="gd-doc-thumb" />
              <div>
                <div class="gd-doc-label">Personal Photo</div>
                <div class="gd-doc-view">View <GmsIcon name="chevron-right" :size="10" /></div>
              </div>
            </a>
            <a v-if="activeGuest.attendance[String(event.id)].passport_front" :href="route('gms.api.document', { path: activeGuest.attendance[String(event.id)].passport_front })" target="_blank" class="gd-doc-item gd-doc-clickable">
              <img :src="route('gms.api.document', { path: activeGuest.attendance[String(event.id)].passport_front })" class="gd-doc-thumb" />
              <div>
                <div class="gd-doc-label">Passport Front</div>
                <div class="gd-doc-view">View <GmsIcon name="chevron-right" :size="10" /></div>
              </div>
            </a>
          </div>
        </div>

        <div class="gd-sl-row" style="margin-top:20px;">
          <span class="gd-section-head" style="margin-bottom:0;">Service Level</span>
          <span class="gms-pill" :style="{ background: tierFor(activeGuest.tier)?.bg, color: tierFor(activeGuest.tier)?.color, fontSize: '11px' }">{{ tierFor(activeGuest.tier)?.name ?? activeGuest.tier }}</span>
          <div class="gd-change-wrap">
            <GmsBtn icon="edit" class="gd-change-btn" @click="tierPickerOpen = !tierPickerOpen">Change</GmsBtn>
            <div v-if="tierPickerOpen" class="tier-pick-pop">
              <button v-for="t in tiers" :key="t.id" class="tier-pick-row" :class="{ on: activeGuest.tier === t.id }" @click="pickTier(t.id)">
                <span class="tier-pick-name" :style="{ color: t.color }">{{ t.name }}</span>
                <span class="tier-pick-icons"><GmsIcon v-for="svc in serviceModules.filter(m => (tierServices[t.id] ?? []).includes(m.id))" :key="svc.id" :name="svc.icon" :size="13" /></span>
                <GmsIcon v-if="activeGuest.tier === t.id" name="check" :size="13" class="tier-pick-check" />
              </button>
            </div>
          </div>
        </div>

        <div class="gd-service-grid">
          <div v-for="svc in guestServices(activeGuest)" :key="svc.id" class="gd-service-card" :class="{ 'gd-full': svc.full, 'gd-guest-req': svc.isGuestRequest }">
            <div class="gd-svc-top">
              <div class="gd-svc-icon"><GmsIcon :name="svc.icon" :size="16" /></div>
              <div class="gd-svc-name">{{ svc.label }}</div>
              <span v-if="svc.isGuestRequest" class="gd-svc-src"><GmsIcon name="globe" :size="10" /> Guest request</span>
            </div>
            <div class="gd-svc-bot">
              <div style="display:flex;flex-direction:column;align-items:flex-start;gap:6px;width:100%;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <GmsPill :value="svc.status" />
                  <span v-if="svc.isGuestRequest" class="gd-svc-awaiting">Awaiting booking</span>
                </div>
                <div v-if="svc.id === 'seating' && svc.seatInfo && svc.seatInfo.length > 0" style="display:flex;flex-wrap:wrap;gap:4px;">
                  <span v-for="seat in svc.seatInfo" :key="seat.id" class="gms-pill" style="background:#dbeafe;color:#1e40af;font-size:10px;font-weight:600;font-family:var(--gms-font-mono);">
                    <span v-if="seat.block" style="opacity:0.7;">{{ seat.block }}</span><span v-if="seat.block && seat.block_label" style="opacity:0.5;margin:0 2px;">·</span><span v-if="seat.block_label" style="opacity:0.7;">{{ seat.block_label }}</span><span v-if="seat.block || seat.block_label" style="margin:0 4px;">•</span>{{ seat.seat_code }}
                  </span>
                </div>
                <div v-if="svc.id === 'flights' && svc.flightInfo && svc.flightInfo.length > 0" style="display:flex;flex-direction:column;gap:4px;width:100%;">
                  <div v-for="flight in svc.flightInfo" :key="flight.id" style="display:flex;align-items:center;gap:6px;font-size:11px;">
                    <span class="gms-pill" style="background:#fef3c7;color:#92400e;font-size:10px;font-weight:600;font-family:var(--gms-font-mono);">{{ flight.code }}</span>
                    <span style="color:var(--gms-text-2);">{{ flight.route }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ── Preferences tab ── -->
      <template v-else-if="drawerTab === 'preferences'">
        <div v-if="!currentEventPreferences.flight && !currentEventPreferences.accommodation && !currentEventPreferences.transportation" class="gms-empty" style="padding:40px 0;">
          <div class="gms-empty-title">No preferences set</div>
          <div class="gms-empty-sub">Edit this guest to add travel, accommodation, or transportation preferences.</div>
        </div>
        <div v-else style="display:flex;flex-direction:column;gap:16px;">
          <div v-if="currentEventPreferences.flight" class="gd-pref-item">
            <div class="gd-pref-head">
              <GmsIcon name="plane" :size="16" />
              <span class="gd-pref-label">Flight Preferences</span>
              <span v-if="currentEventPreferences.overrides.flight" class="gms-pill" style="background:#fef3c7;color:#92400e;font-size:10px;font-weight:600;margin-left:auto;">Event override</span>
            </div>
            <div class="gd-pref-details"><p style="font-size:13px;line-height:1.5;color:var(--gms-text);margin:0;">{{ currentEventPreferences.flight }}</p></div>
          </div>
          <div v-if="currentEventPreferences.accommodation" class="gd-pref-item">
            <div class="gd-pref-head">
              <GmsIcon name="building" :size="16" />
              <span class="gd-pref-label">Accommodation Preferences</span>
              <span v-if="currentEventPreferences.overrides.accommodation" class="gms-pill" style="background:#fef3c7;color:#92400e;font-size:10px;font-weight:600;margin-left:auto;">Event override</span>
            </div>
            <div class="gd-pref-details"><p style="font-size:13px;line-height:1.5;color:var(--gms-text);margin:0;">{{ currentEventPreferences.accommodation }}</p></div>
          </div>
          <div v-if="currentEventPreferences.transportation" class="gd-pref-item">
            <div class="gd-pref-head">
              <GmsIcon name="car" :size="16" />
              <span class="gd-pref-label">Transportation Preferences</span>
              <span v-if="currentEventPreferences.overrides.transportation" class="gms-pill" style="background:#fef3c7;color:#92400e;font-size:10px;font-weight:600;margin-left:auto;">Event override</span>
            </div>
            <div class="gd-pref-details"><p style="font-size:13px;line-height:1.5;color:var(--gms-text);margin:0;">{{ currentEventPreferences.transportation }}</p></div>
          </div>
        </div>
      </template>

      <!-- ── Facilities tab ── -->
      <template v-else-if="drawerTab === 'facilities'">
        <div class="gd-section-head">Service Level Facilities</div>
        <p style="font-size:12px;color:var(--gms-text-3);margin-bottom:12px;">Based on tier: <strong>{{ tierFor(activeGuest.tier)?.name ?? activeGuest.tier }}</strong></p>
        <div v-if="(tierFor(activeGuest.tier)?.facilities ?? []).length > 0" style="margin-bottom:24px;">
          <div style="display:flex;flex-wrap:wrap;gap:6px;">
            <span v-for="f in tierFor(activeGuest.tier)?.facilities ?? []" :key="f" class="gms-pill" :style="{ background: isRemovedFacility(activeGuest, f) ? '#f3f4f6' : tierFor(activeGuest.tier)?.bg, color: isRemovedFacility(activeGuest, f) ? '#9ca3af' : tierFor(activeGuest.tier)?.color, textDecoration: isRemovedFacility(activeGuest, f) ? 'line-through' : 'none', paddingRight: '6px', display: 'flex', alignItems: 'center', gap: '6px', cursor: 'pointer', opacity: isRemovedFacility(activeGuest, f) ? 0.6 : 1 }" @click="toggleRemoveFacility(f)" :title="isRemovedFacility(activeGuest, f) ? 'Click to re-enable' : 'Click to exclude for this guest'">
              {{ f }} <GmsIcon :name="isRemovedFacility(activeGuest, f) ? 'plus' : 'x'" :size="12" />
            </span>
          </div>
        </div>
        <div v-else style="margin-bottom:24px;"><p style="font-size:13px;color:var(--gms-text-3);">No default facilities for this tier.</p></div>

        <div v-if="(activeGuest.facilityOverrides?.added ?? []).length > 0" style="margin-bottom:24px;">
          <div class="gd-section-head">Custom Facilities</div>
          <div style="display:flex;flex-wrap:wrap;gap:6px;">
            <span v-for="f in activeGuest.facilityOverrides.added" :key="f" class="gms-pill" :style="{ background: '#dbeafe', color: '#1e40af', paddingRight: '6px', display: 'flex', alignItems: 'center', gap: '6px' }">
              {{ f }}
              <button type="button" @click="removeCustomFacility(f)" style="background:none;border:none;padding:2px;cursor:pointer;display:flex;align-items:center;color:#1e40af;opacity:0.7;"><GmsIcon name="x" :size="12" /></button>
            </span>
          </div>
        </div>

        <div style="margin-bottom:24px;">
          <div class="gd-section-head">Add Custom Facility</div>
          <div style="display:flex;gap:8px;">
            <input v-model="newFacilityName" class="gms-input" placeholder="e.g., Private Jet Access" @keyup.enter="addCustomFacility" style="flex:1;" />
            <button class="gms-btn gms-btn-primary gms-btn-sm" @click="addCustomFacility" :disabled="!newFacilityName.trim() || isSavingFacilities"><GmsIcon name="plus" :size="14" /> Add</button>
          </div>
        </div>

        <div style="height:1px;background:var(--gms-border);margin:24px 0;"></div>
        <div v-if="activeGuest.dietaryNotes"><div class="gd-section-head">Dietary Requirements</div><p style="font-size:13px;color:var(--gms-text-2);line-height:1.6;">{{ activeGuest.dietaryNotes }}</p></div>
        <div v-if="activeGuest.notes" style="margin-top:14px;"><div class="gd-section-head">Special Notes</div><p style="font-size:13px;color:var(--gms-text-2);line-height:1.6;">{{ activeGuest.notes }}</p></div>
      </template>

      <!-- ── Invitations tab ── -->
      <template v-else-if="drawerTab === 'invitations'">
        <div v-if="!activeGuest.invitations || activeGuest.invitations.length === 0" class="gms-empty" style="padding:40px 0;">
          <div class="gms-empty-title">No invitations sent</div>
          <div class="gms-empty-sub">Send an invitation to this guest to get started.</div>
        </div>
        <div v-else style="display:flex;flex-direction:column;gap:12px;">
          <div v-for="inv in activeGuest.invitations" :key="inv.id" class="gms-card" style="padding:14px;cursor:pointer;transition:all 0.15s;border:1px solid var(--gms-border);" @click="viewInvitationDetails(inv)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
              <GmsPill :value="inv.status" />
              <div style="font-size:11px;color:var(--gms-text-3);">Sent {{ inv.sent_at ? new Date(inv.sent_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A' }}</div>
            </div>
            <div style="font-weight:600;font-size:14px;margin-bottom:8px;color:var(--gms-text);">{{ inv.subject || 'No subject' }}</div>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:12px;color:var(--gms-text-2);"><GmsIcon name="ticket" :size="12" style="opacity:0.7;margin-right:4px;" />{{ inv.matches?.length || 0 }} {{ inv.matches?.length === 1 ? 'match' : 'matches' }}</div>
              <span v-if="inv.matches && inv.matches.length > 0" class="gms-pill" :style="{ background: getInvitationSummary(inv).bg, color: getInvitationSummary(inv).color, fontSize: '11px', fontWeight: '600' }">{{ getInvitationSummary(inv).text }}</span>
            </div>
          </div>
        </div>
      </template>
    </template>

    <template #footer>
      <GmsBtn icon="trash" icon-only title="Delete" style="color: #dc2626;" @click="openDelete(activeGuest.id); drawerOpen = false" />
      <GmsBtn icon="edit" style="background: var(--gms-surface); border-color: var(--gms-border);" @click="openEdit(activeGuest, true)">Edit</GmsBtn>
      <GmsBtn v-if="!isOnEvent(activeGuest)" variant="primary" icon="plus" style="margin-left:auto" @click="addSingleToEvent(activeGuest.id); drawerOpen = false">Add to {{ event?.name }}</GmsBtn>
      <GmsBtn v-else variant="primary" icon="mail" style="margin-left:auto" @click="router.visit(route('gms.invitations.index'))">Manage invitation</GmsBtn>
    </template>
  </GmsDrawer>

  <!-- ── Create / Edit Modal ──────────────────────────────────────── -->
  <GmsModal :open="guestModal" :title="editingGuest ? 'Edit guest' : 'New guest'" size="lg" @close="guestModal = false">
    <div class="gf-body">
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Guest type <span class="gf-req">*</span></label>
          <div class="gms-seg" style="width:fit-content;">
            <button :class="{ on: form.guestType === 'local' }" @click="form.guestType = 'local'">Local</button>
            <button :class="{ on: form.guestType === 'international' }" @click="form.guestType = 'international'">International</button>
          </div>
        </div>
        <div class="gms-field"><label class="gms-label">Title</label><input v-model="form.title" class="gms-input" placeholder="Mr / Mrs / H.E. ..." /></div>
        <div class="gms-field"><label class="gms-label">First name <span class="gf-req">*</span></label><input v-model="form.firstName" class="gms-input" placeholder="First name" /></div>
        <div class="gms-field"><label class="gms-label">Last name <span class="gf-req">*</span></label><input v-model="form.lastName" class="gms-input" placeholder="Last name" /></div>
        <div class="gms-field"><label class="gms-label">Mobile</label><input v-model="form.phone" class="gms-input" placeholder="+974 ..." /></div>
        <div class="gms-field"><label class="gms-label">Email <span class="gf-req">*</span></label><input v-model="form.email" type="email" class="gms-input" placeholder="name@email.com" /></div>
        <div class="gms-field"><label class="gms-label">{{ form.guestType === 'local' ? 'QID' : 'Passport No.' }}</label><input v-model="form.qid" class="gms-input" :placeholder="form.guestType === 'local' ? 'Qatari ID' : 'Passport number'" /></div>
        <div class="gms-field"><label class="gms-label">Nationality</label><GmsSearchableSelect v-model="form.nationality" :options="nationalities" placeholder="Select country..." value-key="value" label-key="label" /></div>
        <div class="gms-field"><label class="gms-label">Group <span class="gf-req">*</span></label><select v-model="form.group_id" class="gms-input gms-select"><option value="">Select group</option><option v-for="g in groups" :key="g.id" :value="g.id">{{ g.label }}</option></select></div>
        <div class="gms-field"><label class="gms-label">Hosted by <span class="gf-req">*</span></label><select v-model="form.host" class="gms-input gms-select"><option value="">Select host</option><option v-for="h in hosts" :key="h.id" :value="h.id">{{ h.name }}</option></select></div>
      </div>

      <div class="gms-field" style="margin-top:6px;">
        <label class="gms-label">Service level <span class="gf-req">*</span></label>
        <div class="gf-tier-row">
          <button v-for="t in tiers" :key="t.id" type="button" class="gf-tier-card" :class="{ on: form.tier === t.id }" @click="form.tier = t.id">
            <span class="gf-tier-name" :style="{ color: t.color }">{{ t.name }}</span>
            <span class="gf-tier-count">{{ t.facilities?.length ?? 0 }} facilities</span>
          </button>
        </div>
      </div>

      <hr class="gms-divider" style="margin:18px 0 14px;" />

      <div class="ng-sec-h">
        <span class="ng-sec-ic"><GmsIcon name="users" :size="16" /></span>
        <div style="flex:1;"><div class="ng-sec-t">Companions</div><div class="ng-sec-s">Spouse, aides or delegation travelling under this guest.</div></div>
        <span v-if="companions.length > 0" class="gms-pill">{{ companions.length }}</span>
      </div>
      <div v-if="companions.length > 0" class="comp-list">
        <div v-for="(c, i) in companions" :key="i" class="comp-card">
          <div class="comp-row">
            <div style="flex:1;"><input v-model="c.name" class="gms-input" placeholder="Companion full name" /></div>
            <div style="width:160px;flex:none;"><select v-model="c.relation" class="gms-input gms-select"><option value="">Relation</option><option v-for="rel in GUEST_RELATIONS" :key="rel" :value="rel">{{ rel }}</option></select></div>
            <button class="gms-btn gms-btn-ghost gms-btn-icon gms-btn-sm" title="Remove" @click="companions.splice(i, 1)"><GmsIcon name="x" :size="14" /></button>
          </div>
          <div class="comp-docs">
            <div class="gms-field">
              <label class="gms-label">Passport No.</label>
              <input v-model="c.passport_no" class="gms-input" placeholder="e.g. AB1234567" style="font-family:var(--gms-font-mono);" />
            </div>
            <div class="gms-field">
              <label class="gms-label">Personal Photo</label>
              <GmsDocUploader v-model="c.personal_photo" label="Upload photo" :folder="'guests/companions'" />
            </div>
            <div class="gms-field">
              <label class="gms-label">Passport Front</label>
              <GmsDocUploader v-model="c.passport_front" label="Upload passport" :folder="'guests/companions'" />
            </div>
          </div>
        </div>
      </div>
      <button class="gms-btn gms-btn-sm" :style="{ marginTop: companions.length ? '10px' : '2px' }" @click="companions.push({ name: '', relation: 'Companion', passport_no: '', personal_photo: '', passport_front: '' })"><GmsIcon name="plus" :size="14" /> Add companion</button>

      <hr class="gms-divider" style="margin:18px 0 14px;" />

      <!-- Guest Documents -->
      <div class="ng-sec-h">
        <span class="ng-sec-ic"><GmsIcon name="eye" :size="16" /></span>
        <div style="flex:1;"><div class="ng-sec-t">Documents</div><div class="ng-sec-s">Personal photo, passport copy and number for this event.</div></div>
      </div>
      <div class="gms-field" style="margin-bottom:12px;">
        <label class="gms-label">Passport No.</label>
        <input v-model="guestDocs.passport_no" class="gms-input" placeholder="e.g. AB1234567" style="font-family:var(--gms-font-mono);max-width:300px;" />
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Personal Photo</label>
          <GmsDocUploader v-model="guestDocs.personal_photo" label="Upload photo" :folder="'guests/photos'" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Passport Front Copy</label>
          <GmsDocUploader v-model="guestDocs.passport_front" label="Upload passport" :folder="'guests/passports'" />
        </div>
      </div>

      <hr class="gms-divider" style="margin:18px 0 14px;" />

      <div class="ng-sec-h">
        <span class="ng-sec-ic"><GmsIcon name="star" :size="16" /></span>
        <div style="flex:1;"><div class="ng-sec-t">Preferences</div><div class="ng-sec-s">Optional notes about travel, accommodation, and transportation preferences.</div></div>
      </div>
      
      <!-- Flight Preferences -->
      <div class="gms-field">
        <label class="gms-label">
          Flight Preferences <span style="color:var(--gms-text-3);font-weight:400;">(Global)</span>
        </label>
        <textarea v-model="form.flightPreferences" class="gms-input" rows="2" placeholder="e.g., Business class preferred, vegetarian meals, window seat" />
        <div v-if="editingGuest && props.event?.id" style="margin-top:8px;">
          <button 
            type="button" 
            class="gms-btn gms-btn-ghost gms-btn-sm" 
            :style="{ background: prefOverrideActive.flight ? '#fef3c7' : 'transparent', color: prefOverrideActive.flight ? '#92400e' : 'var(--gms-text-3)' }"
            @click="prefOverrideActive.flight = !prefOverrideActive.flight"
          >
            <GmsIcon :name="prefOverrideActive.flight ? 'check-circle' : 'circle'" :size="14" />
            Override for {{ event?.name ?? 'this event' }}
          </button>
          <textarea 
            v-if="prefOverrideActive.flight" 
            v-model="prefOverrideValues.flight" 
            class="gms-input" 
            rows="2" 
            placeholder="Event-specific flight preferences..." 
            style="margin-top:6px;border-color:#f59e0b;" 
          />
        </div>
      </div>

      <!-- Accommodation Preferences -->
      <div class="gms-field">
        <label class="gms-label">
          Accommodation Preferences <span style="color:var(--gms-text-3);font-weight:400;">(Global)</span>
        </label>
        <textarea v-model="form.accommodationPreferences" class="gms-input" rows="2" placeholder="e.g., High floor, quiet room, accessible room" />
        <div v-if="editingGuest && props.event?.id" style="margin-top:8px;">
          <button 
            type="button" 
            class="gms-btn gms-btn-ghost gms-btn-sm" 
            :style="{ background: prefOverrideActive.accommodation ? '#fef3c7' : 'transparent', color: prefOverrideActive.accommodation ? '#92400e' : 'var(--gms-text-3)' }"
            @click="prefOverrideActive.accommodation = !prefOverrideActive.accommodation"
          >
            <GmsIcon :name="prefOverrideActive.accommodation ? 'check-circle' : 'circle'" :size="14" />
            Override for {{ event?.name ?? 'this event' }}
          </button>
          <textarea 
            v-if="prefOverrideActive.accommodation" 
            v-model="prefOverrideValues.accommodation" 
            class="gms-input" 
            rows="2" 
            placeholder="Event-specific accommodation preferences..." 
            style="margin-top:6px;border-color:#f59e0b;" 
          />
        </div>
      </div>

      <!-- Transportation Preferences -->
      <div class="gms-field">
        <label class="gms-label">
          Transportation Preferences <span style="color:var(--gms-text-3);font-weight:400;">(Global)</span>
        </label>
        <textarea v-model="form.transportationPreferences" class="gms-input" rows="2" placeholder="e.g., Mercedes preferred, child seat needed" />
        <div v-if="editingGuest && props.event?.id" style="margin-top:8px;">
          <button 
            type="button" 
            class="gms-btn gms-btn-ghost gms-btn-sm" 
            :style="{ background: prefOverrideActive.transportation ? '#fef3c7' : 'transparent', color: prefOverrideActive.transportation ? '#92400e' : 'var(--gms-text-3)' }"
            @click="prefOverrideActive.transportation = !prefOverrideActive.transportation"
          >
            <GmsIcon :name="prefOverrideActive.transportation ? 'check-circle' : 'circle'" :size="14" />
            Override for {{ event?.name ?? 'this event' }}
          </button>
          <textarea 
            v-if="prefOverrideActive.transportation" 
            v-model="prefOverrideValues.transportation" 
            class="gms-input" 
            rows="2" 
            placeholder="Event-specific transportation preferences..." 
            style="margin-top:6px;border-color:#f59e0b;" 
          />
        </div>
      </div>
    </div>

    <template #footer>
      <GmsBtn @click="guestModal = false">Cancel</GmsBtn>
      <GmsBtn variant="primary" :disabled="form.processing" @click="saveGuest">{{ editingGuest ? 'Save changes' : 'Create guest' }}</GmsBtn>
    </template>
  </GmsModal>

  <!-- ── Delete Confirm ──────────────────────────────────────────── -->
  <GmsModal :open="deleteModal" title="Delete Guest" size="sm" @close="deleteModal = false">
    <p style="font-size:13.5px;color:var(--gms-text-2);">This guest will be permanently removed along with any seat assignments.</p>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="deleteModal = false">Cancel</button>
      <button class="gms-btn gms-btn-danger" @click="confirmDelete">Delete</button>
    </template>
  </GmsModal>

  <!-- ── Import Modal ─────────────────────────────────────────────── -->
  <GmsModal :open="importModal" title="Import Guests" size="sm" @close="importModal = false">
    <div style="display: flex; flex-direction: column; gap: 20px;">
      <div>
        <p style="font-size: 13.5px; color: var(--gms-text-2); margin-bottom: 12px;">Upload an Excel (.xlsx, .xls) or CSV file to import guests.</p>
      </div>
      <div class="gms-field">
        <label class="gms-label">Select File</label>
        <input ref="importFileInput" type="file" accept=".xlsx,.xls,.csv" @change="handleFileChange" class="gms-input" style="padding: 8px;" />
        <div v-if="importFile" style="margin-top: 8px; font-size: 12px; color: var(--gms-text-3);">Selected: {{ importFile.name }} ({{ (importFile.size / 1024).toFixed(1) }} KB)</div>
      </div>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="importModal = false" :disabled="isImporting">Cancel</button>
      <button class="gms-btn gms-btn-primary" @click="confirmImport" :disabled="!importFile || isImporting">
        <GmsIcon v-if="isImporting" name="refresh-cw" :size="14" class="gms-spin" />
        {{ isImporting ? 'Importing...' : 'Import' }}
      </button>
    </template>
  </GmsModal>

  <!-- Invitation Detail Modal -->
  <GmsModal :open="invitationDetailModal" title="Invitation Details" size="lg" @close="invitationDetailModal = false">
    <div v-if="activeInvitation" style="display:flex;flex-direction:column;gap:20px;">
      <div style="display:flex;gap:16px;padding:12px;background:var(--gms-surface);border:1px solid var(--gms-border);border-radius:8px;">
        <div style="flex:1;"><div style="font-size:11px;color:var(--gms-text-3);margin-bottom:4px;">Status</div><GmsPill :value="activeInvitation.status" /></div>
        <div style="flex:1;"><div style="font-size:11px;color:var(--gms-text-3);margin-bottom:4px;">Sent</div><div style="font-size:13px;font-weight:600;">{{ activeInvitation.sent_at ? new Date(activeInvitation.sent_at).toLocaleDateString() : '—' }}</div></div>
        <div style="flex:1;"><div style="font-size:11px;color:var(--gms-text-3);margin-bottom:4px;">Responded</div><div style="font-size:13px;font-weight:600;">{{ activeInvitation.responded_at ? new Date(activeInvitation.responded_at).toLocaleDateString() : 'Pending' }}</div></div>
      </div>
      <div><div class="gms-section-title" style="margin-bottom:8px;">Subject</div><div style="padding:10px 12px;background:var(--gms-surface);border:1px solid var(--gms-border);border-radius:8px;font-size:13px;">{{ activeInvitation.subject }}</div></div>
      <div><div class="gms-section-title" style="margin-bottom:8px;">Matches ({{ activeInvitation.matches?.length ?? 0 }})</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <div v-for="match in activeInvitation.matches" :key="match.id" style="padding:10px 12px;background:var(--gms-surface);border:1px solid var(--gms-border);border-radius:8px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
              <span class="gms-pill" style="background:var(--gms-maroon-tint);color:var(--gms-maroon);font-size:10px;font-weight:700;text-transform:uppercase;">{{ match.stage }}</span>
              <span v-if="match.response" class="gms-pill" :style="{ background: match.response === 'yes' ? 'var(--good-soft)' : 'var(--bad-soft)', color: match.response === 'yes' ? 'var(--good)' : 'var(--bad)', fontSize:'10px' }">{{ match.response === 'yes' ? '✓ Attending' : '✕ Declined' }}</span>
            </div>
            <div style="font-weight:600;font-size:13px;margin-bottom:2px;">{{ match.homeTeam }} vs {{ match.awayTeam }}</div>
            <div style="font-size:11.5px;color:var(--gms-text-3);">{{ match.venue }} • {{ formatMatchDateTime(match.date) }}</div>
          </div>
        </div>
      </div>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="invitationDetailModal = false">Close</button>
    </template>
  </GmsModal>

  <!-- Teleported Actions Menu -->
  <Teleport to="body">
    <div
      v-if="actionsMenuOpen"
      class="gms-menu-pop"
      :style="{ position: 'fixed', top: menuPosition.top + 'px', left: menuPosition.left + 'px', zIndex: 9999 }"
    >
      <button class="gms-menu-item" @click="openDrawer(filtered.find(g => g.id === actionsMenuOpen)); actionsMenuOpen = null"><GmsIcon name="eye" :size="16" /> View profile</button>
      <button class="gms-menu-item" @click="openEdit(filtered.find(g => g.id === actionsMenuOpen)); actionsMenuOpen = null"><GmsIcon name="edit" :size="16" /> Edit</button>
      <div class="gms-menu-sep"></div>
      <button class="gms-menu-item danger" @click="openDelete(actionsMenuOpen); actionsMenuOpen = null"><GmsIcon name="trash" :size="16" /> Delete</button>
    </div>
  </Teleport>
</template>
