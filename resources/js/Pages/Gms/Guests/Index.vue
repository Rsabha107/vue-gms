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

function normalizeGuest(g) {
  return {
    ...g,
    companionList: normalizeCompanionList(g?.companionList),
    facilities: normalizeFacilities(g?.facilities),
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
const travelExpanded     = ref(true)

function openDrawer(g) {
    activeGuest.value  = g
    drawerTab.value    = 'overview'
    tierPickerOpen.value = false
    companionsExpanded.value = true
    travelExpanded.value = true
    drawerOpen.value   = true
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

// Travel preferences
const FLIGHT_CLASSES = ['First', 'Business', 'Economy', 'Private']
const ROOM_TYPES = ['Suite', 'Deluxe', 'Executive', 'Standard']
const VEHICLES = ['Mercedes S-Class', 'BMW 7 Series', 'Range Rover', 'Mercedes V-Class']

const travelPrefs = ref({
    flight: { on: false, cls: 'Business', from: '', date: '10 Aug' },
    hotel: { on: false, hotel: props.hotels[0]?.name ?? '', room: 'Suite', in: '10 Aug', out: '20 Aug' },
    transport: { on: false, car: 'Mercedes S-Class' }
})

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
})

function openNew() {
    editingGuest.value = null
    form.reset()
    form.tier      = props.tiers[0]?.id ?? ''
    form.status_id = 'invited'
    companions.value = []
    travelPrefs.value = {
        flight: { on: false, cls: 'Business', from: '', date: '10 Aug' },
        hotel: { on: false, hotel: props.hotels[0]?.name ?? '', room: 'Suite', in: '10 Aug', out: '20 Aug' },
        transport: { on: false, car: 'Mercedes S-Class' }
    }
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
    
    // Hydrate companions and travel prefs from facilities
    companions.value = guest.companionList.map(c => ({ name: c?.name ?? '', relation: c?.relation ?? 'Companion' }))
    const fac = guest.facilities
    travelPrefs.value = {
        flight: fac.flight ? { on: true, cls: fac.flight.cls || 'Business', from: fac.flight.inb || '', date: fac.flight.date || '10 Aug' } : { on: false, cls: 'Business', from: '', date: '10 Aug' },
        hotel: fac.hotel ? { on: true, hotel: fac.hotel.hotel || props.hotels[0]?.name || '', room: fac.hotel.room || 'Suite', in: fac.hotel.in || '10 Aug', out: fac.hotel.out || '20 Aug' } : { on: false, hotel: props.hotels[0]?.name ?? '', room: 'Suite', in: '10 Aug', out: '20 Aug' },
        transport: fac.transport ? { on: true, car: fac.transport.car || 'Mercedes S-Class' } : { on: false, car: 'Mercedes S-Class' }
    }
    
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

    // Build facilities from travel prefs
    const facilities = {}
    if (travelPrefs.value.flight.on) {
        facilities.flight = {
            status: 'new',
            cls: travelPrefs.value.flight.cls,
            inb: travelPrefs.value.flight.from.trim() || 'INT',
            date: travelPrefs.value.flight.date
        }
    }
    if (travelPrefs.value.hotel.on) {
        facilities.hotel = {
            status: 'draft',
            hotel: travelPrefs.value.hotel.hotel,
            room: travelPrefs.value.hotel.room,
            in: travelPrefs.value.hotel.in,
            out: travelPrefs.value.hotel.out
        }
    }
    if (travelPrefs.value.transport.on) {
        facilities.transport = {
            status: 'new',
            car: travelPrefs.value.transport.car
        }
    }
    payload.facilities = facilities

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
const emailSubject    = ref('Your invitation to Doha Cup 2026')
const emailBody       = ref(`Dear {{guest_name}},

You are cordially invited to attend the prestigious Doha Cup 2026 at Lusail Stadium.

We are delighted to offer you exclusive access to the following matches:

{{match_list}}

Your service level: {{tier_name}}

Please confirm your attendance at your earliest convenience.

Best regards,
The Doha Cup Committee`)

function openInvite(guest) {
    inviteRecipient.value = guest
    inviteStep.value = 1
    selectedMatches.value = []
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

        <!-- Travel & Ground Services Section -->
        <div v-if="activeGuest.facilities && (activeGuest.facilities.flight || activeGuest.facilities.hotel || activeGuest.facilities.transport)" style="margin-top:20px;">
          <div class="gd-section-head gd-collapsible" @click="travelExpanded = !travelExpanded">
            <span>Travel & Ground Services</span>
            <GmsIcon :name="travelExpanded ? 'chevron-up' : 'chevron-down'" :size="16" style="color:var(--gms-text-3);" />
          </div>
          <div v-show="travelExpanded" style="display:flex;flex-direction:column;gap:10px;margin-top:10px;">
            <!-- Flight -->
            <div v-if="activeGuest.facilities.flight" class="gd-pref-item">
              <div class="gd-pref-head">
                <GmsIcon name="plane" :size="16" />
                <span class="gd-pref-label">Flight</span>
              </div>
              <div class="gd-pref-details">
                <div class="gms-detail-row"><span class="gms-detail-label">Class</span><span class="gms-detail-value">{{ activeGuest.facilities.flight.cls }}</span></div>
                <div class="gms-detail-row"><span class="gms-detail-label">From</span><span class="gms-detail-value">{{ activeGuest.facilities.flight.inb || '—' }}</span></div>
                <div class="gms-detail-row"><span class="gms-detail-label">Date</span><span class="gms-detail-value">{{ activeGuest.facilities.flight.date }}</span></div>
              </div>
            </div>
            <!-- Hotel -->
            <div v-if="activeGuest.facilities.hotel" class="gd-pref-item">
              <div class="gd-pref-head">
                <GmsIcon name="building" :size="16" />
                <span class="gd-pref-label">Accommodation</span>
              </div>
              <div class="gd-pref-details">
                <div class="gms-detail-row"><span class="gms-detail-label">Hotel</span><span class="gms-detail-value">{{ activeGuest.facilities.hotel.hotel }}</span></div>
                <div class="gms-detail-row"><span class="gms-detail-label">Room</span><span class="gms-detail-value">{{ activeGuest.facilities.hotel.room }}</span></div>
                <div class="gms-detail-row"><span class="gms-detail-label">Check-in</span><span class="gms-detail-value">{{ activeGuest.facilities.hotel.in }}</span></div>
                <div class="gms-detail-row"><span class="gms-detail-label">Check-out</span><span class="gms-detail-value">{{ activeGuest.facilities.hotel.out }}</span></div>
              </div>
            </div>
            <!-- Transport -->
            <div v-if="activeGuest.facilities.transport" class="gd-pref-item">
              <div class="gd-pref-head">
                <GmsIcon name="car" :size="16" />
                <span class="gd-pref-label">Transport</span>
              </div>
              <div class="gd-pref-details">
                <div class="gms-detail-row"><span class="gms-detail-label">Vehicle</span><span class="gms-detail-value">{{ activeGuest.facilities.transport.car }}</span></div>
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
        <div class="gd-section-head">Tier Facilities</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:18px;">
          <span
            v-for="f in tierFor(activeGuest.tier)?.facilities ?? []" :key="f"
            class="gms-pill"
            :style="{ background: tierFor(activeGuest.tier)?.bg, color: tierFor(activeGuest.tier)?.color }"
          >{{ f }}</span>
        </div>
        <div v-if="activeGuest.dietaryNotes">
          <div class="gd-section-head">Dietary</div>
          <p style="font-size:13px;color:var(--gms-text-2);line-height:1.6;">{{ activeGuest.dietaryNotes }}</p>
        </div>
        <div v-if="activeGuest.notes" style="margin-top:14px;">
          <div class="gd-section-head">Notes</div>
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

      <!-- Travel & ground services section -->
      <div class="ng-sec-h">
        <span class="ng-sec-ic">
          <GmsIcon name="plane" :size="16" />
        </span>
        <div style="flex:1;">
          <div class="ng-sec-t">Travel & ground services</div>
          <div class="ng-sec-s">Flight, accommodation or transport — layered on top of the {{ form.tier }} package.</div>
        </div>
      </div>

      <div class="pref-stack">
        <!-- Flight preference -->
        <div class="pref-card" :class="{ on: travelPrefs.flight.on }">
          <div class="pref-head" @click="travelPrefs.flight.on = !travelPrefs.flight.on">
            <span class="pref-ic">
              <GmsIcon name="plane" :size="16" />
            </span>
            <div style="flex:1;min-width:0;">
              <div class="pref-t">Flight</div>
              <div class="pref-s">{{ travelPrefs.flight.on ? `${travelPrefs.flight.cls} class` : 'Not requested' }}</div>
            </div>
            <span class="sw" :class="{ on: travelPrefs.flight.on }">
              <span class="kn"></span>
            </span>
          </div>
          <div v-if="travelPrefs.flight.on" class="pref-body">
            <div class="gms-form-grid">
              <div class="gms-field">
                <label class="gms-label">Class</label>
                <select v-model="travelPrefs.flight.cls" class="gms-input gms-select">
                  <option v-for="cls in FLIGHT_CLASSES" :key="cls" :value="cls">{{ cls }}</option>
                </select>
              </div>
              <div class="gms-field">
                <label class="gms-label">Departure airport</label>
                <input v-model="travelPrefs.flight.from" class="gms-input" placeholder="e.g. LHR" />
              </div>
              <div class="gms-field">
                <label class="gms-label">Inbound date</label>
                <input v-model="travelPrefs.flight.date" class="gms-input" placeholder="10 Aug" />
              </div>
            </div>
          </div>
        </div>

        <!-- Accommodation preference -->
        <div class="pref-card" :class="{ on: travelPrefs.hotel.on }">
          <div class="pref-head" @click="travelPrefs.hotel.on = !travelPrefs.hotel.on">
            <span class="pref-ic">
              <GmsIcon name="building" :size="16" />
            </span>
            <div style="flex:1;min-width:0;">
              <div class="pref-t">Accommodation</div>
              <div class="pref-s">{{ travelPrefs.hotel.on ? travelPrefs.hotel.hotel : 'Not requested' }}</div>
            </div>
            <span class="sw" :class="{ on: travelPrefs.hotel.on }">
              <span class="kn"></span>
            </span>
          </div>
          <div v-if="travelPrefs.hotel.on" class="pref-body">
            <div class="gms-form-grid">
              <div class="gms-field">
                <label class="gms-label">Hotel</label>
                <select v-model="travelPrefs.hotel.hotel" class="gms-input gms-select">
                  <option v-for="h in hotels" :key="h.id" :value="h.name">{{ h.name }}</option>
                </select>
              </div>
              <div class="gms-field">
                <label class="gms-label">Room type</label>
                <select v-model="travelPrefs.hotel.room" class="gms-input gms-select">
                  <option v-for="room in ROOM_TYPES" :key="room" :value="room">{{ room }}</option>
                </select>
              </div>
              <div class="gms-field">
                <label class="gms-label">Check-in</label>
                <input v-model="travelPrefs.hotel.in" class="gms-input" placeholder="10 Aug" />
              </div>
              <div class="gms-field">
                <label class="gms-label">Check-out</label>
                <input v-model="travelPrefs.hotel.out" class="gms-input" placeholder="20 Aug" />
              </div>
            </div>
          </div>
        </div>

        <!-- Transport preference -->
        <div class="pref-card" :class="{ on: travelPrefs.transport.on }">
          <div class="pref-head" @click="travelPrefs.transport.on = !travelPrefs.transport.on">
            <span class="pref-ic">
              <GmsIcon name="car" :size="16" />
            </span>
            <div style="flex:1;min-width:0;">
              <div class="pref-t">Transport</div>
              <div class="pref-s">{{ travelPrefs.transport.on ? travelPrefs.transport.car : 'Not requested' }}</div>
            </div>
            <span class="sw" :class="{ on: travelPrefs.transport.on }">
              <span class="kn"></span>
            </span>
          </div>
          <div v-if="travelPrefs.transport.on" class="pref-body">
            <div class="gms-form-grid">
              <div class="gms-field" style="grid-column:1/-1;">
                <label class="gms-label">Vehicle</label>
                <select v-model="travelPrefs.transport.car" class="gms-input gms-select">
                  <option v-for="car in VEHICLES" :key="car" :value="car">{{ car }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="gf-note" style="margin-top:14px;">
        <span class="gf-note-badge">FR 4.2.11.1</span>
        Services here are added on top of the {{ form.tier }} level and routed to the right module as drafts.
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

  <!-- ── Invite Wizard Modal ───────────────────────────────────────── -->
  <GmsModal :open="inviteModal" :title="`Invite to ${event?.name ?? 'Doha Cup \'26'}`" size="lg" @close="inviteModal = false">
    <!-- Stepper -->
    <div class="gms-wizard-steps">
      <div class="gms-wizard-step" :class="{ active: inviteStep === 1, done: inviteStep > 1 }">
        <span class="gms-wizard-num">1</span>
        <span class="gms-wizard-label">Matches</span>
      </div>
      <div class="gms-wizard-line" :class="{ done: inviteStep > 1 }"></div>
      <div class="gms-wizard-step" :class="{ active: inviteStep === 2, done: inviteStep > 2 }">
        <span class="gms-wizard-num">2</span>
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
            <label class="gms-label">Subject</label>
            <input v-model="emailSubject" class="gms-input" />
          </div>
          <div class="gms-field">
            <label class="gms-label">Message Body</label>
            <textarea v-model="emailBody" class="email-body" rows="10"></textarea>
          </div>
          <div class="tags-row">
            <button class="tag-chip" @click="insertTag('guest_name')">{&#8203;{guest_name}}</button>
            <button class="tag-chip" @click="insertTag('tier_name')">{&#8203;{tier_name}}</button>
            <button class="tag-chip" @click="insertTag('match_list')">{&#8203;{match_list}}</button>
          </div>
        </div>
        <div>
          <div class="preview-label"><GmsIcon name="eye" :size="13" /> Preview</div>
          <div style="padding:16px;background:var(--gms-surface-2);border:1px solid var(--gms-border);border-radius:10px;">
            <div style="font-weight:700;font-size:13px;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--gms-border);">{{ emailSubject }}</div>
            <div style="font-size:12.5px;line-height:1.65;color:var(--gms-text);white-space:pre-wrap;">{{ previewEmail }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Step 3: Review ── -->
    <div v-if="inviteStep === 3" style="margin-top:16px;">
      <div class="gms-section-title">Selected matches</div>
      <div style="display:flex;flex-direction:column;gap:6px;margin-top:10px;">
        <div v-for="mid in selectedMatches" :key="mid" class="inv-review-match">
          <span class="mc-stage" :class="stageClass(matches.find(m => m.id === mid)?.stageCode)">
            {{ matches.find(m => m.id === mid)?.stageCode }}
          </span>
          <span style="font-size:13px;font-weight:600;">{{ matches.find(m => m.id === mid)?.stageLabel }}</span>
          <span style="font-size:12px;color:var(--gms-text-3);margin-left:auto;">{{ matches.find(m => m.id === mid)?.date }}</span>
        </div>
      </div>
      <div class="gms-section-title" style="margin-top:20px;">Email preview</div>
      <div style="margin-top:10px;padding:16px;background:var(--gms-surface-2);border:1px solid var(--gms-border);border-radius:10px;">
        <div style="font-weight:700;font-size:13px;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--gms-border);">{{ emailSubject }}</div>
        <div style="font-size:12.5px;line-height:1.65;color:var(--gms-text);white-space:pre-wrap;max-height:180px;overflow-y:auto;">{{ previewEmail }}</div>
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
