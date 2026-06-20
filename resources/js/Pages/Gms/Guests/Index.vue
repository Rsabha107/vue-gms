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
import GmsBtn from '@/Components/Gms/GmsBtn.vue'
import GmsMiniStat from '@/Components/Gms/GmsMiniStat.vue'

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
    errors:  { type: Object, default: () => ({}) },
    auth:    { type: Object, default: () => ({}) },
    flash:   { type: Object, default: () => ({}) },
    gmsEvents: { type: Array, default: () => [] },
})

const toast = inject('toast')

function parseJsonMaybe(value, fallback) {
  if (value == null) return fallback
  if (typeof value !== 'string') return value
  try {
    return JSON.parse(value)
  } catch {
    return fallback
  }
}

function normalizeCompanionList(value) {
  const parsed = parseJsonMaybe(value, [])
  if (Array.isArray(parsed)) return parsed
  if (parsed && typeof parsed === 'object') return [parsed]
  return []
}

function normalizeFacilities(value) {
  const parsed = parseJsonMaybe(value, {})
  return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {}
}

function normalizeFacilityOverrides(value) {
  const parsed = parseJsonMaybe(value, { added: [], removed: [] })
  if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
    return {
      added: Array.isArray(parsed.added) ? parsed.added : [],
      removed: Array.isArray(parsed.removed) ? parsed.removed : []
    }
  }
  return { added: [], removed: [] }
}

function normalizeGuest(g) {
  return {
    ...g,
    companionList: normalizeCompanionList(g?.companionList),
    facilities: normalizeFacilities(g?.facilities),
    facilityOverrides: normalizeFacilityOverrides(g?.facilityOverrides),
  }
}

// ── Local reactive guest list ─────────────────────────────────────
const localGuests = ref(props.guests.map(normalizeGuest))

// ── Filters ───────────────────────────────────────────────────────
const search            = ref('')
const tierFilter        = ref('all')
const nationalityFilter = ref('all')
const groupFilter       = ref('all')

// ── Column visibility ─────────────────────────────────────────────
const columnVisibility = ref({
    gmsId: true,
    title: true,
    type: true,
    group: true,
    tier: true,
    nationality: false,
    status: false,
    host: true,
})
const columnMenuOpen = ref(false)

// ── Refresh ───────────────────────────────────────────────────────
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

const columns = [
    { key: 'gmsId', label: 'GMS-ID' },
    { key: 'title', label: 'Title / Role' },
    { key: 'type', label: 'Type' },
    { key: 'group', label: 'Group' },
    { key: 'tier', label: 'Tier' },
    { key: 'nationality', label: 'Nationality' },
    { key: 'status', label: 'Status' },
    { key: 'host', label: 'Host' },
]

function toggleColumn(key) {
    columnVisibility.value[key] = !columnVisibility.value[key]
}

const visibleColumnsCount = computed(() => 
    2 + Object.values(columnVisibility.value).filter(Boolean).length
)

const nationalities = computed(() =>
    [...new Set(localGuests.value.map(g => g.nationality).filter(Boolean))].sort()
)

// Stats helpers
const platinumCount = computed(() => 
    localGuests.value.filter(g => g.tier === 'T1' || g.tier === 'T2').length
)

const internationalCount = computed(() => 
    localGuests.value.filter(g => g.guestType === 'international').length
)

const localCount = computed(() => 
    localGuests.value.filter(g => g.guestType === 'local').length
)

const filtered = computed(() => {
    let list = localGuests.value
    if (tierFilter.value !== 'all')        list = list.filter(g => g.tier === tierFilter.value)
    if (nationalityFilter.value !== 'all') list = list.filter(g => g.nationality === nationalityFilter.value)
    if (groupFilter.value !== 'all')       list = list.filter(g => g.group_id === groupFilter.value)
    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(g =>
            g.name.toLowerCase().includes(q) ||
            (g.title ?? '').toLowerCase().includes(q) ||
            (g.nationality ?? '').toLowerCase().includes(q)
        )
    }
    return list
})

// ── Profile drawer ────────────────────────────────────────────────
const drawerOpen      = ref(false)
const activeGuest     = ref(null)
const actionsMenuOpen = ref(null)
const drawerTab       = ref('overview')
const tierPickerOpen  = ref(false)
const companionsExpanded = ref(true)
const preferencesExpanded = ref(true)

// Facility overrides
const newFacilityName = ref('')
const isSavingFacilities = ref(false)

function openDrawer(g) {
    activeGuest.value  = g
    drawerTab.value    = 'overview'
    tierPickerOpen.value = false
    companionsExpanded.value = true
    preferencesExpanded.value = true
    newFacilityName.value = ''
    drawerOpen.value   = true
}

// Get merged facilities: tier baseline + guest overrides
function getGuestFacilities(guest) {
    if (!guest) return []
    const tier = tierFor(guest.tier)
    const tierFacilities = tier?.facilities ?? []
    const overrides = guest.facilityOverrides ?? { added: [], removed: [] }
    
    // Filter out removed facilities and add custom ones
    const baseFacilities = tierFacilities.filter(f => !(overrides.removed ?? []).includes(f))
    return [...baseFacilities, ...(overrides.added ?? [])]
}

// Check if a facility is from tier (baseline) or custom (added)
function isTierFacility(guest, facility) {
    if (!guest) return false
    const tier = tierFor(guest.tier)
    return (tier?.facilities ?? []).includes(facility)
}

// Check if a facility is removed from tier
function isRemovedFacility(guest, facility) {
    if (!guest) return false
    const overrides = guest.facilityOverrides ?? { added: [], removed: [] }
    return (overrides.removed ?? []).includes(facility)
}

// Toggle facility removal from tier
function toggleRemoveFacility(facility) {
    if (!activeGuest.value) return
    const overrides = activeGuest.value.facilityOverrides ?? { added: [], removed: [] }
    const removed = overrides.removed ?? []
    
    if (removed.includes(facility)) {
        // Re-enable it
        overrides.removed = removed.filter(f => f !== facility)
    } else {
        // Remove it
        overrides.removed = [...removed, facility]
    }
    
    activeGuest.value.facilityOverrides = overrides
    saveFacilityOverrides()
}

// Add custom facility
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

// Remove custom facility
function removeCustomFacility(facility) {
    if (!activeGuest.value) return
    const overrides = activeGuest.value.facilityOverrides ?? { added: [], removed: [] }
    overrides.added = (overrides.added ?? []).filter(f => f !== facility)
    activeGuest.value.facilityOverrides = overrides
    saveFacilityOverrides()
}

// Save facility overrides to backend
function saveFacilityOverrides() {
    if (!activeGuest.value || isSavingFacilities.value) return
    
    isSavingFacilities.value = true
    const idx = localGuests.value.findIndex(g => g.id === activeGuest.value.id)
    if (idx !== -1) {
        localGuests.value[idx].facilityOverrides = activeGuest.value.facilityOverrides
    }
    
    // Send full guest data to satisfy validation
    router.put(route('gms.guests.update', activeGuest.value.id), {
        name: activeGuest.value.name,
        firstName: activeGuest.value.firstName,
        lastName: activeGuest.value.lastName,
        title: activeGuest.value.title,
        guestType: activeGuest.value.guestType,
        qid: activeGuest.value.qid,
        tier: activeGuest.value.tier,
        group_id: activeGuest.value.group_id,
        nationality: activeGuest.value.nationality,
        status_id: activeGuest.value.status_id,
        email: activeGuest.value.email,
        phone: activeGuest.value.phone,
        host: activeGuest.value.host,
        hotel: activeGuest.value.hotel,
        dietaryNotes: activeGuest.value.dietaryNotes,
        notes: activeGuest.value.notes,
        flightPreferences: activeGuest.value.flightPreferences,
        accommodationPreferences: activeGuest.value.accommodationPreferences,
        transportationPreferences: activeGuest.value.transportationPreferences,
        companionList: activeGuest.value.companionList,
        companions: activeGuest.value.companions,
        facilities: activeGuest.value.facilities,
        facilityOverrides: activeGuest.value.facilityOverrides,
    }, {
        onSuccess: () => {
            isSavingFacilities.value = false
            toast('Facilities updated')
        },
        onError: (errors) => {
            isSavingFacilities.value = false
            console.error('Save error:', errors)
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Failed to update facilities', 'error')
        },
        preserveScroll: true,
    })
}

function pickTier(tierId) {
    if (!activeGuest.value) return
    const idx = localGuests.value.findIndex(g => g.id === activeGuest.value.id)
    if (idx !== -1) {
        localGuests.value[idx].tier = tierId
        activeGuest.value = { ...localGuests.value[idx] }
    }
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
    return serviceModules
        .filter(m => included.includes(m.id))
        .map(m => ({ ...m, status: g.status_id }))
}

function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }

function tierFor(id)  { return props.tiers.find(t => t.id === id) }
function groupFor(id) { return props.groups.find(g => g.id === id) }
function hostFor(id)  { return props.hosts.find(h => h.id === id) }

// ── Create / Edit modal ──────────────────────────────────────────
const guestModal   = ref(false)
const editingGuest = ref(null)

// Companions
const companions = ref([])
const GUEST_RELATIONS = ['Spouse', 'Family', 'Aide', 'Security', 'Delegate', 'Interpreter', 'Companion']

const form = useForm({
    name:         '',
    firstName:    '',
    lastName:     '',
    title:        '',
    guestType:    'local',
    qid:          '',
    tier:         props.tiers[0]?.id ?? '',
    group_id:     '',
    host:         '',
    hotel:        '',
    nationality:  '',
    status_id:    'invited',
    email:        '',
    phone:        '',
    dietaryNotes: '',
    notes:        '',
    flightPreferences: '',
    accommodationPreferences: '',
    transportationPreferences: '',
})

function openNew() {
    editingGuest.value = null
    form.reset()
    form.tier      = props.tiers[0]?.id ?? ''
    form.status_id = 'invited'
    companions.value = []
    guestModal.value = true
}

function openEdit(g, fromDrawer = false) {
  const guest = normalizeGuest(g)
  editingGuest.value = guest
  form.name         = guest.name
  form.firstName    = guest.firstName   ?? ''
  form.lastName     = guest.lastName    ?? ''
  form.title        = guest.title       ?? ''
  form.guestType    = guest.guestType   ?? (guest.nationality === 'QA' ? 'local' : 'international')
  form.qid          = guest.qid         ?? ''
  form.tier         = guest.tier
  form.group_id     = guest.group_id    ?? ''
  form.host         = guest.host        ?? ''
  form.hotel        = guest.hotel       ?? ''
  form.nationality  = guest.nationality ?? ''
  form.status_id    = guest.status_id
  form.email        = guest.email       ?? ''
  form.phone        = guest.phone       ?? ''
  form.dietaryNotes = guest.dietaryNotes ?? ''
  form.notes        = guest.notes       ?? ''
  form.flightPreferences = guest.flightPreferences ?? ''
  form.accommodationPreferences = guest.accommodationPreferences ?? ''
  form.transportationPreferences = guest.transportationPreferences ?? ''
    
    // Hydrate companions
    companions.value = guest.companionList.map(c => ({ name: c?.name ?? '', relation: c?.relation ?? 'Companion' }))
    
    if (fromDrawer) drawerOpen.value = false
    guestModal.value = true
}

function saveGuest() {
    // Ensure name is always set from firstName + lastName
    if (form.firstName || form.lastName) {
        form.name = (form.firstName + ' ' + form.lastName).trim()
    }
    
    const payload = { ...form.data() }

    // Add companions data
    const companionList = companions.value.filter(c => c.name.trim()).map(c => ({
        name: c.name.trim(),
        relation: c.relation || 'Companion'
    }))
    payload.companionList = companionList
    payload.companions = companionList.length

    if (editingGuest.value) {
        const idx = localGuests.value.findIndex(g => g.id === editingGuest.value.id)
        if (idx !== -1) localGuests.value[idx] = { ...localGuests.value[idx], ...payload }

        form.transform(() => payload).put(route('gms.guests.update', editingGuest.value.id), {
            onSuccess: () => { 
                guestModal.value = false
                toast('Guest updated.')
            form.transform(data => data)
            },
            onError: (errors) => {
                console.error('Validation errors:', errors)
                const firstError = Object.values(errors)[0]
                toast(firstError || 'Failed to save.', 'error')
            form.transform(data => data)
            },
            preserveScroll: true,
        })
    } else {
        const newGuest = { id: 'G' + Date.now(), ...payload }
        localGuests.value.unshift(newGuest)
        form.transform(() => payload).post(route('gms.guests.store'), {
            onSuccess: () => { 
                guestModal.value = false
                toast('Guest created.')
            form.transform(data => data)
            },
            onError: (errors) => {
                console.error('Validation errors:', errors)
                const firstError = Object.values(errors)[0]
                toast(firstError || 'Failed to save.', 'error')
            form.transform(data => data)
            },
            preserveScroll: true,
        })
    }
}

// ── Delete ────────────────────────────────────────────────────────
const deleteModal = ref(false)
const deletingId  = ref(null)

function openDelete(id) {
    deletingId.value  = id
    deleteModal.value = true
}

function confirmDelete() {
    localGuests.value = localGuests.value.filter(g => g.id !== deletingId.value)
    if (activeGuest.value?.id === deletingId.value) drawerOpen.value = false
    router.delete(route('gms.guests.destroy', deletingId.value), {
        onSuccess: () => { deleteModal.value = false; toast('Guest deleted.') },
        onError: () => toast('Failed to delete.', 'error'),
        preserveScroll: true,
    })
}

// ── Import ────────────────────────────────────────────────────────
const importModal     = ref(false)
const importFile      = ref(null)
const importFileInput = ref(null)
const isImporting     = ref(false)

function openImport() {
    importModal.value = true
    importFile.value  = null
}

function handleFileChange(event) {
    const file = event.target.files[0]
    if (file) {
        // Validate file type
        const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv']
        if (!validTypes.includes(file.type)) {
            toast('Please select a valid Excel (.xlsx, .xls) or CSV file', 'error')
            return
        }
        // Validate file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            toast('File size must be less than 10MB', 'error')
            return
        }
        importFile.value = file
    }
}

function confirmImport() {
    if (!importFile.value) {
        toast('Please select a file to import', 'error')
        return
    }

    isImporting.value = true
    const formData = new FormData()
    formData.append('file', importFile.value)

    router.post(route('gms.guests.import'), formData, {
        onSuccess: () => {
            importModal.value = false
            importFile.value = null
            isImporting.value = false
            // Refresh the guests list
            refreshGuests()
        },
        onError: (errors) => {
            isImporting.value = false
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Import failed', 'error')
        },
        preserveScroll: true,
    })
}

function downloadTemplate() {
    // Create CSV template with proper escaping
    const headers = [
        'name', 'first_name', 'last_name', 'title', 'guest_type', 'qid', 'tier',
        'group_id', 'nationality', 'email', 'phone', 'host', 'hotel',
        'dietary_notes', 'notes', 'status_id', 'companions', 'companion_list', 'facilities'
    ]
    
    // Helper function to escape CSV values
    const escapeCSV = (value) => {
        if (value == null || value === '') return ''
        const stringValue = String(value)
        // If value contains comma, quote, or newline, wrap in quotes and escape quotes
        if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n')) {
            return `"${stringValue.replace(/"/g, '""')}"`
        }
        return stringValue
    }
    
    // Example row with proper values aligned to headers
    const example = [
        'Sheikh Ahmed Al-Thani',              // name
        'Ahmed',                               // first_name
        'Al-Thani',                           // last_name
        'Minister of Sports',                 // title
        'local',                              // guest_type
        'QID123456',                          // qid
        'T1',                                 // tier
        '',                                   // group_id
        'QA',                                 // nationality
        'ahmed@example.qa',                   // email
        '+974 12345678',                      // phone
        '',                                   // host
        '',                                   // hotel
        'No shellfish',                       // dietary_notes
        'VIP protocol required',              // notes
        'invited',                            // status_id
        '2',                                  // companions
        'Fatima Al-Thani (Spouse), Ali Al-Thani (Aide)',  // companion_list (will be quoted)
        'Lounge access, Executive seating'    // facilities (will be quoted)
    ]
    
    const csvRows = [
        headers.map(escapeCSV).join(','),
        example.map(escapeCSV).join(','),
        // Add empty row for user to fill
        headers.map(() => '').join(',')
    ]
    
    const csvContent = csvRows.join('\n')
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    link.setAttribute('href', url)
    link.setAttribute('download', 'guests_import_template.csv')
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    toast('Template downloaded')
}

// ── Invite Wizard ─────────────────────────────────────────────────
const inviteModal       = ref(false)
const inviteStep        = ref(1)
const inviteRecipient   = ref(null)
const recipientPickerOpen  = ref(false)
const recipientSearch      = ref('')
const selectedMatches   = ref([])

const filteredRecipients = computed(() => {
    const q = recipientSearch.value.toLowerCase()
    if (!q) return localGuests.value.slice(0, 20)
    return localGuests.value.filter(g =>
        g.name.toLowerCase().includes(q) ||
        (groupFor(g.group_id)?.label ?? '').toLowerCase().includes(q)
    ).slice(0, 20)
})

function pickRecipient(guest) {
    inviteRecipient.value  = guest
    recipientPickerOpen.value = false
    recipientSearch.value  = ''
}
const selectedTemplate = ref('VIP Invitation')
const emailSubject    = ref('Your invitation to Doha Cup 2026')
const emailBody       = ref(`Dear {{guest_name}},

You are cordially invited to attend the prestigious Doha Cup 2026 at Lusail Stadium.

We are delighted to offer you exclusive access to the following matches:

{{match_list}}

Your service level: {{tier_name}}

Please confirm your attendance at your earliest convenience.

Best regards,
The Doha Cup Committee`)
const rsvpDeadline = ref('5 Aug 2026')

function openInvite(guest) {
    inviteRecipient.value = guest
    inviteStep.value = 1
    selectedMatches.value = props.matches.filter(m => m.featured).map(m => m.id)
    selectedTemplate.value = 'VIP Invitation'
    rsvpDeadline.value = '5 Aug 2026'
    inviteModal.value = true
    actionsMenuOpen.value = null
}

function nextInviteStep() {
    if (inviteStep.value < 3) inviteStep.value++
}

function prevInviteStep() {
    if (inviteStep.value > 1) inviteStep.value--
}

function toggleMatch(matchId) {
    const idx = selectedMatches.value.indexOf(matchId)
    if (idx > -1) selectedMatches.value.splice(idx, 1)
    else selectedMatches.value.push(matchId)
}

function selectAllMatches() {
    selectedMatches.value = props.matches.map(m => m.id)
}

function selectKnockouts() {
    selectedMatches.value = props.matches
        .filter(m => ['QF', 'SF', 'Final'].includes(m.stage))
        .map(m => m.id)
}

function stageClass(code) {
    if (!code) return ''
    const c = (code + '').toUpperCase()
    if (c.includes('OPENING') || c.includes('CEREMONY')) return 'mc-stage-opening'
    if (c.includes('GROUP')) return 'mc-stage-group'
    if (c.includes('QUARTER')) return 'mc-stage-qf'
    if (c.includes('SEMI')) return 'mc-stage-sf'
    return 'mc-stage-final'
}

const previewEmailText = computed(() => {
    if (!inviteRecipient.value) return ''
    
    let body = emailBody.value
    body = body.replace(/{{guest_name}}/g, inviteRecipient.value.name)
    body = body.replace(/{{tier_name}}/g, tierFor(inviteRecipient.value.tier)?.name ?? '')
    body = body.replace(/{{event_name}}/g, props.event?.name ?? 'Doha Cup \'26')
    body = body.replace(/{{venue}}/g, props.event?.venue ?? 'Lusail Stadium')
    
    // Remove match_list tag - matches will be rendered separately
    body = body.replace(/{{match_list}}/g, '')
    
    return body
})

const previewEmail = computed(() => {
    if (!inviteRecipient.value) return ''
    
    let body = emailBody.value
    body = body.replace(/{{guest_name}}/g, inviteRecipient.value.name)
    body = body.replace(/{{tier_name}}/g, tierFor(inviteRecipient.value.tier)?.name ?? '')
    
    // Build match list
    const matchList = selectedMatches.value
        .map(mid => props.matches.find(m => m.id === mid))
        .filter(Boolean)
        .map(m => {
            if (m.awayTeam) {
                return `• ${m.homeTeam} vs ${m.awayTeam} — ${m.date} at ${m.kickoff}`
            } else {
                return `• ${m.name} — ${m.date} at ${m.kickoff}`
            }
        })
        .join('\n')
    
    body = body.replace(/{{match_list}}/g, matchList || '(No matches selected)')
    
    return body
})

function sendInvitation() {
    if (!inviteRecipient.value || !selectedMatches.value.length) return
    
    // TODO: Implement actual invitation sending via backend
    toast(`Invitation sent to ${inviteRecipient.value.name}`)
    inviteModal.value = false
}

function insertTag(tag) {
    emailBody.value += `{{${tag}}}`
}

// ── Helpers ───────────────────────────────────────────────────────
function toggleActionsMenu(guestId) {
    actionsMenuOpen.value = actionsMenuOpen.value === guestId ? null : guestId
}

const flagEmoji = (cc) => {
    if (!cc) return ''
    return cc.toUpperCase().replace(/./g, c =>
        String.fromCodePoint(0x1F1E6 - 65 + c.charCodeAt(0))
    )
}

// Close actions menu when clicking outside
function handleClickOutside(e) {
    if (actionsMenuOpen.value && !e.target.closest('.gms-menu-pop') && !e.target.closest('.gms-btn-icon')) {
        actionsMenuOpen.value = null
    }
    if (tierPickerOpen.value && !e.target.closest('.tier-pick-pop') && !e.target.closest('.gd-change-btn')) {
        tierPickerOpen.value = false
    }
    if (recipientPickerOpen.value && !e.target.closest('.inv-change-wrap')) {
        recipientPickerOpen.value = false
    }
    if (columnMenuOpen.value && !e.target.closest('.col-menu-wrap')) {
        columnMenuOpen.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="gms-view">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Guest Registry</h1>
        <p class="gms-view-subtitle">{{ localGuests.length }} registered guests</p>
      </div>
      <div class="gms-view-actions">
        <GmsBtn icon="upload" @click="openImport">Import</GmsBtn>
        <GmsBtn icon="download">Export</GmsBtn>
        <GmsBtn variant="primary" icon="plus" :icon-size="14" @click="openNew">Add Guest</GmsBtn>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="gms-stats">
      <GmsMiniStat label="Total Guests" :value="localGuests.length" color="#8a1f3d" />
      <GmsMiniStat label="Platinum (VVIP)" :value="platinumCount" color="#5b4a8a" />
      <GmsMiniStat label="International" :value="internationalCount" color="#3a6a8a" />
      <GmsMiniStat label="Local" :value="localCount" color="#9a7430" />
    </div>

    <!-- Toolbar -->
    <div class="gms-toolbar">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search guests…" />
      </div>

      <!-- Tier filter -->
      <GmsFilterDropdown
        v-model="tierFilter"
        label="Tier"
        all-label="All tiers"
        :options="tiers"
      />

      <!-- Nationality filter -->
      <GmsFilterDropdown
        v-model="nationalityFilter"
        label="Nationality"
        all-label="All nationalities"
        :options="nationalities"
      >
        <template #item="{ option }">
          {{ flagEmoji(option) }} {{ option }}
        </template>
      </GmsFilterDropdown>

      <!-- Group filter -->
      <GmsFilterDropdown
        v-model="groupFilter"
        label="Group"
        all-label="All groups"
        :options="groups"
      />
      
      <!-- Refresh button -->
      <button 
        class="gms-btn gms-btn-ghost gms-btn-sm" 
        @click="refreshGuests"
        :disabled="isRefreshing"
        title="Refresh guests"
        style="margin-left: 4px;"
      >
        <GmsIcon name="refresh-cw" :size="14" :class="{ 'gms-spin': isRefreshing }" />
      </button>

      <!-- Column visibility toggle -->
      <div class="col-menu-wrap" style="position: relative; margin-left: 4px;">
        <button 
          class="gms-btn gms-btn-ghost gms-btn-sm" 
          @click="columnMenuOpen = !columnMenuOpen"
          title="Show/hide columns"
        >
          <GmsIcon name="list" :size="14" />
          Columns
        </button>
        
        <div v-if="columnMenuOpen" class="gms-menu-pop" style="right: 0; left: auto; min-width: 180px;">
          <div style="padding: 8px 12px; font-size: 11px; font-weight: 600; color: var(--gms-text-3); text-transform: uppercase; letter-spacing: 0.05em;">
            Toggle Columns
          </div>
          <div class="gms-menu-sep"></div>
          <button
            v-for="col in columns"
            :key="col.key"
            class="gms-menu-item"
            style="justify-content: space-between;"
            @click="toggleColumn(col.key)"
          >
            <span>{{ col.label }}</span>
            <GmsIcon 
              v-if="columnVisibility[col.key]" 
              name="check" 
              :size="14" 
              style="color: var(--gms-maroon);"
            />
          </button>
        </div>
      </div>
      
      <span class="mxt-count" style="margin-left: auto;">{{ filtered.length }} of {{ localGuests.length }}</span>
    </div>

    <!-- Guest table -->
    <div class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-table-wrap">
          <table class="gms-table">
            <thead>
              <tr>
                <th>Guest</th>
                <th v-if="columnVisibility.gmsId">GMS-ID</th>
                <th v-if="columnVisibility.title">Title / Role</th>
                <th v-if="columnVisibility.type">Type</th>
                <th v-if="columnVisibility.group">Group</th>
                <th v-if="columnVisibility.tier">Tier</th>
                <th v-if="columnVisibility.nationality">Nationality</th>
                <th v-if="columnVisibility.status">Status</th>
                <th v-if="columnVisibility.host">Host</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="g in filtered" :key="g.id" @click="openDrawer(g)">
                <td>
                  <div style="display:flex;align-items:center;gap:9px;">
                    <GmsAvatar :name="g.name" />
                    <div>
                      <div style="font-weight:600;font-size:13.5px;">{{ g.name }}</div>
                      <div style="font-size:11px;color:var(--gms-text-3);">{{ g.email || '—' }}</div>
                    </div>
                  </div>
                </td>
                <td v-if="columnVisibility.gmsId"><span class="gms-muted gms-small" style="font-family:var(--gms-font-mono);">{{ g.reference_number || '—' }}</span></td>
                <td v-if="columnVisibility.title"><span class="gms-muted gms-small">{{ g.title }}</span></td>
                <td v-if="columnVisibility.type"><span class="gms-muted gms-small">{{ capitalize(g.guestType || 'local') }}</span></td>
                <td v-if="columnVisibility.group"><span class="gms-muted gms-small">{{ groupFor(g.group_id)?.label ?? '—' }}</span></td>
                <td v-if="columnVisibility.tier">
                  <span
                    class="gms-pill"
                    :style="{
                      background: tierFor(g.tier)?.bg  ?? '#f3f4f6',
                      color:      tierFor(g.tier)?.color ?? '#374151',
                      fontSize: '10.5px'
                    }"
                  >{{ tierFor(g.tier)?.name ?? g.tier }}</span>
                </td>
                <td v-if="columnVisibility.nationality">
                  <span style="font-size:13px;">{{ flagEmoji(g.nationality) }}</span>
                  <span class="gms-muted gms-small" style="margin-left:4px;">{{ g.nationality }}</span>
                </td>
                <td v-if="columnVisibility.status"><GmsPill :value="g.status_id" /></td>
                <td v-if="columnVisibility.host"><span class="gms-muted gms-small">{{ hostFor(g.host)?.name ?? '—' }}</span></td>
                <td @click.stop>
                  <div class="gms-act-row">
                    <!-- Send Invitation button -->
                    <button
                      class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon"
                      @click="openInvite(g)"
                      title="Send invitation"
                    >
                      <GmsIcon name="mail" :size="14" />
                    </button>

                    <!-- More actions menu -->
                    <div style="position: relative;">
                      <button
                        class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon"
                        @click="toggleActionsMenu(g.id)"
                        title="More actions"
                      >
                        <GmsIcon name="more-vertical" :size="14" />
                      </button>

                      <!-- Actions dropdown menu -->
                      <div v-if="actionsMenuOpen === g.id" class="gms-menu-pop">
                        <button class="gms-menu-item" @click="openDrawer(g); actionsMenuOpen = null">
                          <GmsIcon name="eye" :size="16" />
                          View profile
                        </button>
                        <button class="gms-menu-item" @click="openEdit(g); actionsMenuOpen = null">
                          <GmsIcon name="edit" :size="16" />
                          Edit
                        </button>
                        <div class="gms-menu-sep"></div>
                        <button class="gms-menu-item danger" @click="openDelete(g.id)">
                          <GmsIcon name="trash" :size="16" />
                          Delete
                        </button>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              <tr v-if="!filtered.length">
                <td :colspan="visibleColumnsCount">
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
  </div>

  <!-- ── Profile Drawer ──────────────────────────────────────────── -->
  <GmsDrawer :open="drawerOpen" @close="drawerOpen = false">
    <template v-if="activeGuest" #header>
      <GmsAvatar :name="activeGuest.name" size="xl" />
      <div class="gd-hinfo">
        <div class="gd-name-row">
          <span class="gd-name">{{ activeGuest.name }}</span>
          <span
            class="gms-pill"
            :style="{ background: tierFor(activeGuest.tier)?.bg, color: tierFor(activeGuest.tier)?.color, fontSize: '11px' }"
          >{{ tierFor(activeGuest.tier)?.name ?? activeGuest.tier }}</span>
        </div>
        <div class="gd-meta">
          <span style="font-family:var(--gms-font-mono);font-size:11px;">{{ activeGuest.id }}</span>
          <span class="gd-meta-dot">·</span>
          <span>{{ flagEmoji(activeGuest.nationality) }} {{ activeGuest.nationality }}</span>
          <span class="gd-meta-dot">·</span>
          <span>{{ activeGuest.nationality === 'QA' ? 'Local' : 'International' }}</span>
        </div>
        <div class="gd-status-row">
          <span class="gd-status-pill" :class="activeGuest.status_id">● {{ capitalize(activeGuest.status_id) }}</span>
          <span v-if="activeGuest.status_id === 'confirmed'" class="gd-info-pill">Passport on file</span>
        </div>
      </div>
    </template>

    <template v-if="activeGuest">
      <!-- Tabs -->
      <div class="gms-seg gd-tabs">
        <button :class="{ on: drawerTab === 'overview' }"     @click="drawerTab = 'overview'">Overview</button>
        <button :class="{ on: drawerTab === 'facilities' }"   @click="drawerTab = 'facilities'">Facilities</button>
        <button :class="{ on: drawerTab === 'invitations' }"  @click="drawerTab = 'invitations'">Invitations</button>
      </div>

      <!-- ── Overview tab ── -->
      <template v-if="drawerTab === 'overview'">

        <div class="gd-section-head">Contact</div>
        <div class="gms-detail-row"><span class="gms-detail-label">Email</span><span class="gms-detail-value" style="font-family:var(--gms-font-mono);font-size:12px;">{{ activeGuest.email || '—' }}</span></div>
        <div class="gms-detail-row"><span class="gms-detail-label">Group</span><span class="gms-detail-value">{{ groupFor(activeGuest.group_id)?.label ?? '—' }}</span></div>
        <div class="gms-detail-row"><span class="gms-detail-label">Hosted by</span><span class="gms-detail-value">{{ hostFor(activeGuest.host)?.name ?? '—' }}</span></div>
        <div class="gms-detail-row"><span class="gms-detail-label">Title</span><span class="gms-detail-value">{{ activeGuest.title || '—' }}</span></div>

        <!-- Companions Section -->
        <div v-if="activeGuest.companionList && activeGuest.companionList.length > 0" style="margin-top:20px;">
          <div class="gd-section-head gd-collapsible" @click="companionsExpanded = !companionsExpanded">
            <div style="display:flex;align-items:center;gap:8px;">
              <span>Companions</span>
              <span class="gms-pill" style="font-size:11px;">{{ activeGuest.companionList.length }}</span>
            </div>
            <GmsIcon :name="companionsExpanded ? 'chevron-up' : 'chevron-down'" :size="16" style="color:var(--gms-text-3);" />
          </div>
          <div v-show="companionsExpanded" style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
            <div v-for="(comp, idx) in activeGuest.companionList" :key="idx" class="gd-comp-row">
              <span class="gd-comp-name">{{ comp.name }}</span>
              <span class="gd-comp-rel" style="color: white; background: black;">{{ comp.relation }}</span>
            </div>
          </div>
        </div>

        <!-- Preferences Section -->
        <div v-if="activeGuest.flightPreferences || activeGuest.accommodationPreferences || activeGuest.transportationPreferences" style="margin-top:20px;">
          <div class="gd-section-head gd-collapsible" @click="preferencesExpanded = !preferencesExpanded">
            <span>Preferences</span>
            <GmsIcon :name="preferencesExpanded ? 'chevron-up' : 'chevron-down'" :size="16" style="color:var(--gms-text-3);" />
          </div>
          <div v-show="preferencesExpanded" style="display:flex;flex-direction:column;gap:12px;margin-top:10px;">
            <!-- Flight Preferences -->
            <div v-if="activeGuest.flightPreferences" class="gd-pref-item">
              <div class="gd-pref-head">
                <GmsIcon name="plane" :size="16" />
                <span class="gd-pref-label">Flight Preferences</span>
              </div>
              <div class="gd-pref-details">
                <p style="font-size:13px;line-height:1.5;color:var(--gms-text);margin:0;">{{ activeGuest.flightPreferences }}</p>
              </div>
            </div>
            <!-- Accommodation Preferences -->
            <div v-if="activeGuest.accommodationPreferences" class="gd-pref-item">
              <div class="gd-pref-head">
                <GmsIcon name="building" :size="16" />
                <span class="gd-pref-label">Accommodation Preferences</span>
              </div>
              <div class="gd-pref-details">
                <p style="font-size:13px;line-height:1.5;color:var(--gms-text);margin:0;">{{ activeGuest.accommodationPreferences }}</p>
              </div>
            </div>
            <!-- Transportation Preferences -->
            <div v-if="activeGuest.transportationPreferences" class="gd-pref-item">
              <div class="gd-pref-head">
                <GmsIcon name="car" :size="16" />
                <span class="gd-pref-label">Transportation Preferences</span>
              </div>
              <div class="gd-pref-details">
                <p style="font-size:13px;line-height:1.5;color:var(--gms-text);margin:0;">{{ activeGuest.transportationPreferences }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="gd-sl-row" style="margin-top:20px;">
          <span class="gd-section-head" style="margin-bottom:0;">Service Level</span>
          <span
            class="gms-pill"
            :style="{ background: tierFor(activeGuest.tier)?.bg, color: tierFor(activeGuest.tier)?.color, fontSize: '11px' }"
          >{{ tierFor(activeGuest.tier)?.name ?? activeGuest.tier }}</span>
          <div class="gd-change-wrap">
            <GmsBtn icon="edit" class="gd-change-btn" @click="tierPickerOpen = !tierPickerOpen">Change</GmsBtn>
            <div v-if="tierPickerOpen" class="tier-pick-pop">
              <button
                v-for="t in tiers" :key="t.id"
                class="tier-pick-row"
                :class="{ on: activeGuest.tier === t.id }"
                @click="pickTier(t.id)"
              >
                <span class="tier-pick-name" :style="{ color: t.color }">{{ t.name }}</span>
                <span class="tier-pick-icons">
                  <GmsIcon
                    v-for="svc in serviceModules.filter(m => (tierServices[t.id] ?? []).includes(m.id))"
                    :key="svc.id"
                    :name="svc.icon"
                    :size="13"
                  />
                </span>
                <GmsIcon v-if="activeGuest.tier === t.id" name="check" :size="13" class="tier-pick-check" />
              </button>
            </div>
          </div>
        </div>

        <div class="gd-service-grid">
          <div
            v-for="svc in guestServices(activeGuest)" :key="svc.id"
            class="gd-service-card"
            :class="{ 'gd-full': svc.full }"
          >
            <div class="gd-svc-top">
              <div class="gd-svc-icon"><GmsIcon :name="svc.icon" :size="16" /></div>
              <div class="gd-svc-name">{{ svc.label }}</div>
            </div>
            <div class="gd-svc-bot">
              <span class="gd-svc-sub">Included</span>
              <GmsPill :value="svc.status" />
            </div>
          </div>
        </div>

        <div class="gd-service-note">
          <span class="gd-note-code">FR 4.2.11.1</span>
          Facilities can be added beyond the level per guest.
        </div>

      </template>

      <!-- ── Facilities tab ── -->
      <template v-else-if="drawerTab === 'facilities'">
        
        <!-- Tier Facilities Section -->
        <div class="gd-section-head">Service Level Facilities</div>
        <p style="font-size:12px;color:var(--gms-text-3);margin-bottom:12px;">
          Based on tier: <strong>{{ tierFor(activeGuest.tier)?.name ?? activeGuest.tier }}</strong>
        </p>
        
        <div v-if="(tierFor(activeGuest.tier)?.facilities ?? []).length > 0" style="margin-bottom:24px;">
          <div style="display:flex;flex-wrap:wrap;gap:6px;">
            <span
              v-for="f in tierFor(activeGuest.tier)?.facilities ?? []" :key="f"
              class="gms-pill"
              :style="{ 
                background: isRemovedFacility(activeGuest, f) ? '#f3f4f6' : tierFor(activeGuest.tier)?.bg, 
                color: isRemovedFacility(activeGuest, f) ? '#9ca3af' : tierFor(activeGuest.tier)?.color,
                textDecoration: isRemovedFacility(activeGuest, f) ? 'line-through' : 'none',
                paddingRight: '6px',
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                cursor: 'pointer',
                opacity: isRemovedFacility(activeGuest, f) ? 0.6 : 1
              }"
              @click="toggleRemoveFacility(f)"
              :title="isRemovedFacility(activeGuest, f) ? 'Click to re-enable' : 'Click to exclude for this guest'"
            >
              {{ f }}
              <GmsIcon :name="isRemovedFacility(activeGuest, f) ? 'plus' : 'x'" :size="12" />
            </span>
          </div>
          <p style="font-size:11px;color:var(--gms-text-3);margin-top:8px;">
            💡 Click any facility to exclude it for this guest
          </p>
        </div>
        <div v-else style="margin-bottom:24px;">
          <p style="font-size:13px;color:var(--gms-text-3);">No default facilities for this tier.</p>
        </div>

        <!-- Custom Added Facilities -->
        <div v-if="(activeGuest.facilityOverrides?.added ?? []).length > 0" style="margin-bottom:24px;">
          <div class="gd-section-head">Custom Facilities</div>
          <div style="display:flex;flex-wrap:wrap;gap:6px;">
            <span
              v-for="f in activeGuest.facilityOverrides.added" :key="f"
              class="gms-pill"
              :style="{ 
                background: '#dbeafe',
                color: '#1e40af',
                paddingRight: '6px',
                display: 'flex',
                alignItems: 'center',
                gap: '6px'
              }"
            >
              {{ f }}
              <button
                type="button"
                @click="removeCustomFacility(f)"
                style="background:none;border:none;padding:2px;cursor:pointer;display:flex;align-items:center;color:#1e40af;opacity:0.7;"
              >
                <GmsIcon name="x" :size="12" />
              </button>
            </span>
          </div>
        </div>

        <!-- Add Custom Facility -->
        <div style="margin-bottom:24px;">
          <div class="gd-section-head">Add Custom Facility</div>
          <p style="font-size:12px;color:var(--gms-text-3);margin-bottom:10px;">
            Add extra facilities not included in their service level
          </p>
          <div style="display:flex;gap:8px;">
            <input
              v-model="newFacilityName"
              class="gms-input"
              placeholder="e.g., Private Jet Access"
              @keyup.enter="addCustomFacility"
              style="flex:1;"
            />
            <button
              class="gms-btn gms-btn-primary gms-btn-sm"
              @click="addCustomFacility"
              :disabled="!newFacilityName.trim() || isSavingFacilities"
            >
              <GmsIcon name="plus" :size="14" />
              Add
            </button>
          </div>
        </div>

        <!-- Divider -->
        <div style="height:1px;background:var(--gms-border);margin:24px 0;"></div>

        <!-- Dietary Notes -->
        <div v-if="activeGuest.dietaryNotes">
          <div class="gd-section-head">Dietary Requirements</div>
          <p style="font-size:13px;color:var(--gms-text-2);line-height:1.6;">{{ activeGuest.dietaryNotes }}</p>
        </div>
        
        <!-- General Notes -->
        <div v-if="activeGuest.notes" style="margin-top:14px;">
          <div class="gd-section-head">Special Notes</div>
          <p style="font-size:13px;color:var(--gms-text-2);line-height:1.6;">{{ activeGuest.notes }}</p>
        </div>
      </template>

      <!-- ── Invitations tab ── -->
      <template v-else-if="drawerTab === 'invitations'">
        <div class="gms-empty" style="padding:40px 0;">
          <div class="gms-empty-title">No invitations yet</div>
          <div class="gms-empty-sub">Send an invite to get started.</div>
        </div>
      </template>
    </template>

    <template #footer>
      <GmsBtn
        icon="trash"
        icon-only
        title="Delete"
        style="color: #dc2626;"
        @click="openDelete(activeGuest.id); drawerOpen = false"
      />
      <GmsBtn
        icon="edit"
        style="background: var(--gms-surface); border-color: var(--gms-border);"
        @click="openEdit(activeGuest, true)"
      >Edit</GmsBtn>
      <GmsBtn
        icon="ticket"
        style="background: var(--gms-surface); border-color: var(--gms-border);"
        @click="toast('Seating management coming soon.')"
      >Manage seat</GmsBtn>
      <GmsBtn variant="primary" icon="mail" style="margin-left:auto" @click="openInvite(activeGuest); drawerOpen = false">Send / update invite</GmsBtn>
    </template>
  </GmsDrawer>

  <!-- ── Create / Edit Modal ──────────────────────────────────────── -->
  <GmsModal
    :open="guestModal"
    :title="editingGuest ? 'Edit guest' : 'New guest'"
    size="lg"
    @close="guestModal = false"
  >
    <div class="gf-body">

      <div class="gms-form-grid">

        <!-- Guest type -->
        <div class="gms-field">
          <label class="gms-label">Guest type <span class="gf-req">*</span></label>
          <div class="gms-seg" style="width:fit-content;">
            <button :class="{ on: form.guestType === 'local' }"         @click="form.guestType = 'local'">Local</button>
            <button :class="{ on: form.guestType === 'international' }" @click="form.guestType = 'international'">International</button>
          </div>
        </div>

        <!-- Title -->
        <div class="gms-field">
          <label class="gms-label">Title</label>
          <input v-model="form.title" class="gms-input" placeholder="Mr / Mrs / H.E. ..." />
        </div>

        <!-- First name -->
        <div class="gms-field">
          <label class="gms-label">First name <span class="gf-req">*</span></label>
          <input v-model="form.firstName" class="gms-input" placeholder="First name" />
        </div>

        <!-- Last name -->
        <div class="gms-field">
          <label class="gms-label">Last name <span class="gf-req">*</span></label>
          <input v-model="form.lastName" class="gms-input" placeholder="Last name" />
        </div>

        <!-- Mobile -->
        <div class="gms-field">
          <label class="gms-label">Mobile</label>
          <input v-model="form.phone" class="gms-input" placeholder="+974 ..." />
        </div>

        <!-- Email -->
        <div class="gms-field">
          <label class="gms-label">Email <span class="gf-req">*</span></label>
          <input v-model="form.email" type="email" class="gms-input" placeholder="name@email.com" />
        </div>

        <!-- QID / Passport -->
        <div class="gms-field">
          <label class="gms-label">{{ form.guestType === 'local' ? 'QID' : 'Passport No.' }}</label>
          <input v-model="form.qid" class="gms-input" :placeholder="form.guestType === 'local' ? 'Qatari ID' : 'Passport number'" />
        </div>

        <!-- Nationality -->
        <div class="gms-field">
          <label class="gms-label">Nationality</label>
          <input v-model="form.nationality" class="gms-input" placeholder="Nationality" maxlength="2" style="text-transform:uppercase;" />
        </div>

        <!-- Group -->
        <div class="gms-field">
          <label class="gms-label">Group <span class="gf-req">*</span></label>
          <select v-model="form.group_id" class="gms-input gms-select">
            <option value="">Select group</option>
            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.label }}</option>
          </select>
        </div>

        <!-- Hosted by -->
        <div class="gms-field">
          <label class="gms-label">Hosted by <span class="gf-req">*</span></label>
          <select v-model="form.host" class="gms-input gms-select">
            <option value="">Select host</option>
            <option v-for="h in hosts" :key="h.id" :value="h.id">{{ h.name }}</option>
          </select>
        </div>

      </div>

      <!-- Service level -->
      <div class="gms-field" style="margin-top:6px;">
        <label class="gms-label">Service level <span class="gf-req">*</span></label>
        <div class="gf-tier-row">
          <button
            v-for="t in tiers" :key="t.id"
            type="button"
            class="gf-tier-card"
            :class="{ on: form.tier === t.id }"
            @click="form.tier = t.id"
          >
            <span class="gf-tier-name" :style="{ color: t.color }">{{ t.name }}</span>
            <span class="gf-tier-count">{{ t.facilities?.length ?? 0 }} facilities</span>
          </button>
        </div>
      </div>

      <hr class="gms-divider" style="margin:18px 0 14px;" />

      <!-- Companions section -->
      <div class="ng-sec-h">
        <span class="ng-sec-ic">
          <GmsIcon name="users" :size="16" />
        </span>
        <div style="flex:1;">
          <div class="ng-sec-t">Companions</div>
          <div class="ng-sec-s">Spouse, aides or delegation travelling under this guest.</div>
        </div>
        <span v-if="companions.length > 0" class="gms-pill">{{ companions.length }}</span>
      </div>

      <div v-if="companions.length > 0" class="comp-list">
        <div v-for="(c, i) in companions" :key="i" class="comp-row">
          <div style="flex:1;">
            <input v-model="c.name" class="gms-input" placeholder="Companion full name" />
          </div>
          <div style="width:160px;flex:none;">
            <select v-model="c.relation" class="gms-input gms-select">
              <option value="">Relation</option>
              <option v-for="rel in GUEST_RELATIONS" :key="rel" :value="rel">{{ rel }}</option>
            </select>
          </div>
          <button class="gms-btn gms-btn-ghost gms-btn-icon gms-btn-sm" title="Remove" @click="companions.splice(i, 1)">
            <GmsIcon name="x" :size="14" />
          </button>
        </div>
      </div>

      <button class="gms-btn gms-btn-sm" :style="{ marginTop: companions.length ? '10px' : '2px' }" @click="companions.push({ name: '', relation: 'Companion' })">
        <GmsIcon name="plus" :size="14" />
        Add companion
      </button>

      <hr class="gms-divider" style="margin:18px 0 14px;" />

      <!-- Preferences Section -->
      <div class="ng-sec-h">
        <span class="ng-sec-ic">
          <GmsIcon name="star" :size="16" />
        </span>
        <div style="flex:1;">
          <div class="ng-sec-t">Preferences</div>
          <div class="ng-sec-s">Optional notes about travel, accommodation, and transportation preferences.</div>
        </div>
      </div>

      <div class="gms-field">
        <label class="gms-label">Flight Preferences <span style="color:var(--gms-text-3);font-weight:400;">(Optional)</span></label>
        <textarea 
          v-model="form.flightPreferences" 
          class="gms-input" 
          rows="2"
          placeholder="e.g., Business class preferred, vegetarian meals, window seat"
        />
      </div>

      <div class="gms-field">
        <label class="gms-label">Accommodation Preferences <span style="color:var(--gms-text-3);font-weight:400;">(Optional)</span></label>
        <textarea 
          v-model="form.accommodationPreferences" 
          class="gms-input" 
          rows="2"
          placeholder="e.g., High floor, quiet room, accessible room"
        />
      </div>

      <div class="gms-field">
        <label class="gms-label">Transportation Preferences <span style="color:var(--gms-text-3);font-weight:400;">(Optional)</span></label>
        <textarea 
          v-model="form.transportationPreferences" 
          class="gms-input" 
          rows="2"
          placeholder="e.g., Mercedes preferred, child seat needed"
        />
      </div>

    </div>

    <template #footer>
      <GmsBtn @click="guestModal = false">Cancel</GmsBtn>
      <GmsBtn variant="primary" :disabled="form.processing" @click="saveGuest">{{ editingGuest ? 'Save changes' : 'Create guest' }}</GmsBtn>
    </template>
  </GmsModal>

  <!-- ── Delete Confirm ──────────────────────────────────────────── -->
  <GmsModal :open="deleteModal" title="Delete Guest" size="sm" @close="deleteModal = false">
    <p style="font-size:13.5px;color:var(--gms-text-2);">
      This guest will be permanently removed along with any seat assignments.
    </p>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="deleteModal = false">Cancel</button>
      <button class="gms-btn gms-btn-danger" @click="confirmDelete">Delete</button>
    </template>
  </GmsModal>

  <!-- ── Import Modal ─────────────────────────────────────────────── -->
  <GmsModal :open="importModal" title="Import Guests" size="sm" @close="importModal = false">
    <div style="display: flex; flex-direction: column; gap: 20px;">
      <div>
        <p style="font-size: 13.5px; color: var(--gms-text-2); margin-bottom: 12px;">
          Upload an Excel (.xlsx, .xls) or CSV file to import guests. Make sure your file includes the required columns.
        </p>
        <button class="gms-btn gms-btn-ghost gms-btn-sm" @click="downloadTemplate" style="margin-bottom: 16px;">
          <GmsIcon name="download" :size="14" />
          Download Template
        </button>
      </div>

      <div class="gms-field">
        <label class="gms-label">Select File</label>
        <input 
          ref="importFileInput"
          type="file" 
          accept=".xlsx,.xls,.csv"
          @change="handleFileChange"
          class="gms-input"
          style="padding: 8px;"
        />
        <div v-if="importFile" style="margin-top: 8px; font-size: 12px; color: var(--gms-text-3);">
          Selected: {{ importFile.name }} ({{ (importFile.size / 1024).toFixed(1) }} KB)
        </div>
      </div>

      <div style="background: var(--gms-bg); padding: 12px; border-radius: 6px; font-size: 12px; color: var(--gms-text-2);">
        <div style="font-weight: 600; margin-bottom: 8px;">Required columns:</div>
        <ul style="margin: 0; padding-left: 20px; line-height: 1.6;">
          <li><code>first_name</code>, <code>last_name</code> (or <code>name</code>)</li>
          <li><code>email</code>, <code>nationality</code></li>
          <li><code>tier</code> (T1, T2, T3, T4, T5)</li>
        </ul>
        <div style="font-weight: 600; margin-top: 12px; margin-bottom: 8px;">Optional columns:</div>
        <ul style="margin: 0; padding-left: 20px; line-height: 1.6;">
          <li><code>title</code>, <code>guest_type</code> (local/international)</li>
          <li><code>qid</code>, <code>phone</code>, <code>group_id</code></li>
          <li><code>host</code>, <code>hotel</code>, <code>dietary_notes</code></li>
          <li><code>notes</code>, <code>status_id</code> (invited/confirmed/pending/declined)</li>
        </ul>
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

  <!-- ── Invite Wizard Modal ───────────────────────────────────────── -->
  <GmsModal :open="inviteModal" :title="`Invite to ${event?.name ?? 'Doha Cup \'26'}`" size="xl" @close="inviteModal = false">
    <!-- Stepper -->
    <div class="gms-wizard-steps">
      <div class="gms-wizard-step" :class="{ active: inviteStep === 1, done: inviteStep > 1 }">
        <span class="gms-wizard-num">
          <GmsIcon v-if="inviteStep > 1" name="check" :size="11" />
          <span v-else>1</span>
        </span>
        <span class="gms-wizard-label">Matches</span>
      </div>
      <div class="gms-wizard-line" :class="{ done: inviteStep > 1 }"></div>
      <div class="gms-wizard-step" :class="{ active: inviteStep === 2, done: inviteStep > 2 }">
        <span class="gms-wizard-num">
          <GmsIcon v-if="inviteStep > 2" name="check" :size="11" />
          <span v-else>2</span>
        </span>
        <span class="gms-wizard-label">Email</span>
      </div>
      <div class="gms-wizard-line" :class="{ done: inviteStep > 2 }"></div>
      <div class="gms-wizard-step" :class="{ active: inviteStep === 3 }">
        <span class="gms-wizard-num">3</span>
        <span class="gms-wizard-label">Review</span>
      </div>
    </div>

    <!-- Recipient bar (all steps) -->
    <div v-if="inviteRecipient" class="inv-recipient">
      <span class="inv-rec-label">RECIPIENT</span>
      <GmsAvatar :name="inviteRecipient.name" size="sm" />
      <div class="inv-rec-info">
        <div class="inv-rec-name">{{ inviteRecipient.name }}</div>
        <div class="inv-rec-meta">{{ inviteRecipient.email ?? '—' }} · {{ groupFor(inviteRecipient.group_id)?.label ?? inviteRecipient.title ?? 'Guest' }}</div>
      </div>
      <span class="gms-pill inv-tier-pill" :style="{ background: tierFor(inviteRecipient.tier)?.bg, color: tierFor(inviteRecipient.tier)?.color }">
        {{ tierFor(inviteRecipient.tier)?.name ?? '—' }}
      </span>
      <div class="inv-change-wrap">
        <GmsBtn @click="recipientPickerOpen = !recipientPickerOpen">Change</GmsBtn>
        <div v-if="recipientPickerOpen" class="inv-rec-pop">
          <div class="inv-rec-search">
            <GmsIcon name="search" :size="14" class="inv-rec-search-icon" />
            <input
              v-model="recipientSearch"
              class="inv-rec-search-input"
              placeholder="Search guests..."
              autofocus
            />
          </div>
          <div class="inv-rec-list">
            <button
              v-for="g in filteredRecipients" :key="g.id"
              class="inv-rec-row"
              :class="{ on: inviteRecipient?.id === g.id }"
              @click="pickRecipient(g)"
            >
              <GmsAvatar :name="g.name" size="sm" />
              <div class="inv-rec-row-info">
                <div class="inv-rec-row-name">{{ g.name }}</div>
                <div class="inv-rec-row-sub">{{ groupFor(g.group_id)?.label ?? g.title ?? '—' }}</div>
              </div>
              <span class="gms-pill" :style="{ background: tierFor(g.tier)?.bg, color: tierFor(g.tier)?.color, fontSize: '10.5px' }">
                {{ tierFor(g.tier)?.name }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Step 1: Matches ── -->
    <div v-if="inviteStep === 1">
      <div class="inv-match-header">
        <p class="inv-match-desc">Choose which matches to offer — the guest picks from these.</p>
        <div class="inv-match-btns">
          <button class="gms-btn gms-btn-ghost gms-btn-sm" @click="selectAllMatches">Select all</button>
          <button class="gms-btn gms-btn-ghost gms-btn-sm" @click="selectKnockouts">Knockouts</button>
          <button class="gms-btn gms-btn-ghost gms-btn-sm" @click="selectedMatches = []">Clear</button>
        </div>
      </div>
      <div class="match-list">
        <div
          v-for="match in matches" :key="match.id"
          class="match-card" :class="{ sel: selectedMatches.includes(match.id) }"
          @click="toggleMatch(match.id)"
        >
          <div class="mc-top">
            <span class="mc-stage" :class="stageClass(match.stageCode)">{{ match.stageCode }}</span>
            <span class="mc-stlabel">{{ match.stageLabel }}</span>
            <div class="mc-check">
              <GmsIcon v-if="selectedMatches.includes(match.id)" name="check" :size="11" />
            </div>
          </div>
          <div class="mc-teams">
            <div class="team">
              <span v-if="match.homeCode" class="mc-flag">{{ flagEmoji(match.homeCode) }}</span>
              <span v-else class="mc-tbd">🏆</span>
              <span class="mc-tname">{{ match.homeTeam }}</span>
              <span v-if="match.homeCode" class="mc-code">{{ match.homeCode }}</span>
            </div>
            <span class="vs">v</span>
            <div class="team r">
              <span v-if="match.awayCode" class="mc-code">{{ match.awayCode }}</span>
              <span class="mc-tname">{{ match.awayTeam }}</span>
              <span v-if="match.awayCode" class="mc-flag">{{ flagEmoji(match.awayCode) }}</span>
              <span v-else class="mc-tbd">🏆</span>
            </div>
          </div>
          <div class="mc-meta">
            <span>{{ match.date }}</span>
            <span class="mc-dot">·</span>
            <span>{{ match.kickoff }}</span>
            <div v-if="match.seatsLeft != null" class="mc-seats-wrap">
              <div class="mc-seats-bar">
                <div class="mc-seats-fill" :style="{ width: Math.min(100, (match.seatsLeft / match.seatsTotal) * 100) + '%' }"></div>
              </div>
              <span class="mc-seats-left">{{ match.seatsLeft }} left</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Step 2: Email ── -->
    <div v-if="inviteStep === 2">
      <div class="email-grid" style="margin-top:16px;">
        <div>
          <div class="gms-field">
            <label class="gms-label">Template</label>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <button
                v-for="tpl in emailTemplates.slice(0, 5)"
                :key="tpl.id"
                class="gms-chip"
                :class="{ on: selectedTemplate === tpl.name }"
                @click="selectedTemplate = tpl.name; emailSubject = tpl.subject; emailBody = tpl.body"
              >
                {{ tpl.name }}
              </button>
            </div>
          </div>
          <div class="gms-field">
            <label class="gms-label">Subject <span style="color:var(--gms-maroon);">*</span></label>
            <input v-model="emailSubject" class="gms-input" placeholder="Email subject" />
          </div>
          <div class="gms-field">
            <label class="gms-label">Message Body <span style="color:var(--gms-maroon);">*</span></label>
            <textarea v-model="emailBody" class="email-body" rows="10" placeholder="Email body"></textarea>
          </div>
          <div>
            <div class="gms-label" style="margin-bottom:8px;">Insert a merge tag:</div>
            <div class="tags-row">
              <button class="tag-chip" @click="insertTag('guest_name')">{&#8203;{guest_name}}</button>
              <button class="tag-chip" @click="insertTag('tier_name')">{&#8203;{tier_name}}</button>
              <button class="tag-chip" @click="insertTag('match_list')">{&#8203;{match_list}}</button>
              <button class="tag-chip" @click="insertTag('event_name')">{&#8203;{event_name}}</button>
              <button class="tag-chip" @click="insertTag('venue')">{&#8203;{venue}}</button>
            </div>
          </div>
          <div class="gms-field">
            <label class="gms-label">RSVP Deadline <span style="color:var(--gms-maroon);">*</span></label>
            <input v-model="rsvpDeadline" class="gms-input" placeholder="e.g., 5 Aug 2026" />
          </div>
        </div>
        <div>
          <div class="preview-label"><GmsIcon name="eye" :size="13" /> Live preview</div>
          <div class="mail">
            <div class="mail-head">
              <div class="crest">🏆</div>
              <h3>{{ emailSubject || 'Invitation to ' + (event?.name ?? 'Doha Cup \'26') }}</h3>
            </div>
            <div class="mail-body">
              <div class="mail-text">{{ previewEmailText || 'Start typing to see your message preview...' }}</div>
              <div v-if="selectedMatches.length > 0" class="mail-matches">
                <div v-for="mid in selectedMatches" :key="mid" class="mini-match">
                  <div class="mm-stage">{{ matches.find(m => m.id === mid)?.stageCode }}</div>
                  <div class="mm-content">
                    <div class="mm-teams">
                      {{ matches.find(m => m.id === mid)?.homeTeam || 'TBD' }} vs {{ matches.find(m => m.id === mid)?.awayTeam || 'TBD' }}
                    </div>
                    <div class="mm-venue">🏟️ {{ matches.find(m => m.id === mid)?.venueName }}</div>
                  </div>
                  <div class="mm-when">
                    {{ matches.find(m => m.id === mid)?.date }}<br>{{ matches.find(m => m.id === mid)?.kickoff }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Step 3: Review ── -->
    <div v-if="inviteStep === 3" style="margin-top:16px;">
      <div class="inv-review-sec">
        <div class="gms-section-title">Summary</div>
        <div class="inv-kv-list">
          <div class="inv-kv"><span class="inv-k">Recipient</span><span class="inv-v">{{ inviteRecipient?.name }}</span></div>
          <div class="inv-kv"><span class="inv-k">Email</span><span class="inv-v">{{ inviteRecipient?.email ?? '—' }}</span></div>
          <div class="inv-kv"><span class="inv-k">Template</span><span class="inv-v">{{ selectedTemplate }}</span></div>
          <div class="inv-kv"><span class="inv-k">Subject</span><span class="inv-v">{{ emailSubject }}</span></div>
          <div class="inv-kv"><span class="inv-k">Matches offered</span><span class="inv-v">{{ selectedMatches.length }}</span></div>
          <div class="inv-kv"><span class="inv-k">RSVP by</span><span class="inv-v">{{ rsvpDeadline }}</span></div>
        </div>
      </div>

      <div class="inv-review-sec">
        <div class="gms-section-title">Matches the guest can choose from</div>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:10px;">
          <div v-for="mid in selectedMatches" :key="mid" class="inv-review-match">
            <span class="mc-stage" :class="stageClass(matches.find(m => m.id === mid)?.stageCode)">
              {{ matches.find(m => m.id === mid)?.stageCode }}
            </span>
            <span style="font-size:13px;font-weight:600;">{{ matches.find(m => m.id === mid)?.stageLabel }}</span>
            <span style="font-size:12px;color:var(--gms-text-3);margin-left:auto;">{{ matches.find(m => m.id === mid)?.date }}</span>
          </div>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--gms-maroon-tint);border-radius:10px;margin-top:16px;">
        <GmsIcon name="mail" :size="20" style="color:var(--gms-maroon);flex-shrink:0;" />
        <div style="flex:1;font-size:13px;color:var(--gms-text);">See exactly what the guest receives and how they choose matches.</div>
        <button class="gms-btn gms-btn-ghost" style="flex-shrink:0;" @click="toast('Guest preview coming soon')">
          Preview as guest →
        </button>
      </div>
    </div>

    <template #footer>
      <div style="display:flex;align-items:center;justify-content:space-between;width:100%;">
        <span class="inv-footer-info">
          {{ inviteRecipient?.name.split(' ').pop() }} · {{ selectedMatches.length }} {{ selectedMatches.length === 1 ? 'match' : 'matches' }}
        </span>
        <div style="display:flex;gap:8px;">
          <button v-if="inviteStep > 1" class="gms-btn gms-btn-ghost" @click="prevInviteStep">
            <GmsIcon name="chevron-left" :size="13" /> Back
          </button>
          <button v-if="inviteStep < 3" class="gms-btn gms-btn-primary" @click="nextInviteStep">Continue</button>
          <button v-if="inviteStep === 3" class="gms-btn gms-btn-primary" :disabled="!selectedMatches.length" @click="sendInvitation">
            <GmsIcon name="mail" :size="13" /> Send Invitation
          </button>
        </div>
      </div>
    </template>
  </GmsModal>
</template>
