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
})

const toast = inject('toast')

// ── Local reactive guest list ─────────────────────────────────────
const localGuests = ref(props.guests.map(g => ({ ...g })))

// ── Filters ───────────────────────────────────────────────────────
const search            = ref('')
const statusFilter      = ref('all')
const tierFilter        = ref('all')
const nationalityFilter = ref('all')
const groupFilter       = ref('all')

const statusOptions = ['confirmed', 'invited', 'pending', 'declined']

const nationalities = computed(() =>
    [...new Set(localGuests.value.map(g => g.nationality).filter(Boolean))].sort()
)

function countForStatus(s) {
    if (s === 'all') return localGuests.value.length
    return localGuests.value.filter(g => g.status === s).length
}

const filtered = computed(() => {
    let list = localGuests.value
    if (statusFilter.value !== 'all')     list = list.filter(g => g.status === statusFilter.value)
    if (tierFilter.value !== 'all')        list = list.filter(g => g.tier === tierFilter.value)
    if (nationalityFilter.value !== 'all') list = list.filter(g => g.nationality === nationalityFilter.value)
    if (groupFilter.value !== 'all')       list = list.filter(g => g.group === groupFilter.value)
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

function openDrawer(g) {
    activeGuest.value  = g
    drawerTab.value    = 'overview'
    tierPickerOpen.value = false
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
        .map(m => ({ ...m, status: g.status }))
}

function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }

function tierFor(id)  { return props.tiers.find(t => t.id === id) }
function groupFor(id) { return props.groups.find(g => g.id === id) }
function hostFor(id)  { return props.hosts.find(h => h.id === id) }

// ── Create / Edit modal ──────────────────────────────────────────
const guestModal   = ref(false)
const editingGuest = ref(null)

const form = useForm({
    name:         '',
    firstName:    '',
    lastName:     '',
    title:        '',
    guestType:    'local',
    qid:          '',
    tier:         props.tiers[0]?.id ?? '',
    group:        '',
    host:         '',
    hotel:        '',
    nationality:  '',
    status:       'invited',
    email:        '',
    phone:        '',
    dietaryNotes: '',
    notes:        '',
})

function openNew() {
    editingGuest.value = null
    form.reset()
    form.tier   = props.tiers[0]?.id ?? ''
    form.status = 'invited'
    guestModal.value = true
}

function openEdit(g, fromDrawer = false) {
    editingGuest.value = g
    form.name         = g.name
    form.firstName    = g.firstName   ?? ''
    form.lastName     = g.lastName    ?? ''
    form.title        = g.title       ?? ''
    form.guestType    = g.guestType   ?? (g.nationality === 'QA' ? 'local' : 'international')
    form.qid          = g.qid         ?? ''
    form.tier         = g.tier
    form.group        = g.group       ?? ''
    form.host         = g.host        ?? ''
    form.hotel        = g.hotel       ?? ''
    form.nationality  = g.nationality ?? ''
    form.status       = g.status
    form.email        = g.email       ?? ''
    form.phone        = g.phone       ?? ''
    form.dietaryNotes = g.dietaryNotes ?? ''
    form.notes        = g.notes       ?? ''
    if (fromDrawer) drawerOpen.value = false
    guestModal.value = true
}

function saveGuest() {
    const payload = { ...form }
    if (!payload.name && payload.firstName) {
        payload.name = (payload.firstName + ' ' + payload.lastName).trim()
    }

    if (editingGuest.value) {
        const idx = localGuests.value.findIndex(g => g.id === editingGuest.value.id)
        if (idx !== -1) localGuests.value[idx] = { ...localGuests.value[idx], ...payload }

        form.put(route('gms.guests.update', editingGuest.value.id), {
            onSuccess: () => { guestModal.value = false; toast('Guest updated.') },
            onError: () => toast('Failed to save.', 'error'),
            preserveScroll: true,
        })
    } else {
        const newGuest = { id: 'G' + Date.now(), ...payload }
        localGuests.value.unshift(newGuest)
        form.post(route('gms.guests.store'), {
            onSuccess: () => { guestModal.value = false; toast('Guest created.') },
            onError: () => toast('Failed to save.', 'error'),
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
        (groupFor(g.group)?.label ?? '').toLowerCase().includes(q)
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
      <GmsMiniStat label="Total Guests" :value="localGuests.length" />
      <GmsMiniStat label="Confirmed" :value="countForStatus('confirmed')" color="#15803d" clickable @click="statusFilter = 'confirmed'" />
      <GmsMiniStat label="Pending"   :value="countForStatus('pending')"   color="#a16207" clickable @click="statusFilter = 'pending'" />
      <GmsMiniStat label="Invited"   :value="countForStatus('invited')"   color="#1d4ed8" clickable @click="statusFilter = 'invited'" />
      <GmsMiniStat label="Declined"  :value="countForStatus('declined')"  color="#dc2626" clickable @click="statusFilter = 'declined'" />
    </div>

    <!-- Toolbar -->
    <div class="gms-toolbar">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search guests…" />
      </div>

      <!-- Status filter -->
      <GmsFilterDropdown
        v-model="statusFilter"
        label="Status"
        all-label="All statuses"
        :options="statusOptions"
      >
        <template #item="{ option }">
          {{ option.charAt(0).toUpperCase() + option.slice(1) }}
        </template>
      </GmsFilterDropdown>

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
                <th>Title / Role</th>
                <th>Tier</th>
                <th>Nationality</th>
                <th>Status</th>
                <th>Host</th>
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
                      <div style="font-size:11px;color:var(--gms-text-3);font-family:var(--gms-font-mono);">{{ g.id }}</div>
                    </div>
                  </div>
                </td>
                <td><span class="gms-muted gms-small">{{ g.title }}</span></td>
                <td>
                  <span
                    class="gms-pill"
                    :style="{
                      background: tierFor(g.tier)?.bg  ?? '#f3f4f6',
                      color:      tierFor(g.tier)?.color ?? '#374151',
                      fontSize: '10.5px'
                    }"
                  >{{ tierFor(g.tier)?.name ?? g.tier }}</span>
                </td>
                <td>
                  <span style="font-size:13px;">{{ flagEmoji(g.nationality) }}</span>
                  <span class="gms-muted gms-small" style="margin-left:4px;">{{ g.nationality }}</span>
                </td>
                <td><GmsPill :value="g.status" /></td>
                <td><span class="gms-muted gms-small">{{ hostFor(g.host)?.name ?? '—' }}</span></td>
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
                <td colspan="7">
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
          <span class="gd-status-pill" :class="activeGuest.status">● {{ capitalize(activeGuest.status) }}</span>
          <span v-if="activeGuest.status === 'confirmed'" class="gd-info-pill">Passport on file</span>
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
        <div class="gms-detail-row"><span class="gms-detail-label">Group</span><span class="gms-detail-value">{{ groupFor(activeGuest.group)?.label ?? '—' }}</span></div>
        <div class="gms-detail-row"><span class="gms-detail-label">Hosted by</span><span class="gms-detail-value">{{ hostFor(activeGuest.host)?.name ?? '—' }}</span></div>
        <div class="gms-detail-row"><span class="gms-detail-label">Title</span><span class="gms-detail-value">{{ activeGuest.title || '—' }}</span></div>

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
      <GmsBtn icon="trash" icon-only title="Delete" @click="openDelete(activeGuest.id); drawerOpen = false" />
      <GmsBtn icon="edit" @click="openEdit(activeGuest, true)">Edit</GmsBtn>
      <GmsBtn icon="ticket" @click="toast('Seating management coming soon.')">Manage seat</GmsBtn>
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

        <!-- QID -->
        <div class="gms-field">
          <label class="gms-label">QID</label>
          <input v-model="form.qid" class="gms-input" placeholder="Qatari ID" />
        </div>

        <!-- Nationality -->
        <div class="gms-field">
          <label class="gms-label">Nationality</label>
          <input v-model="form.nationality" class="gms-input" placeholder="Nationality" maxlength="2" style="text-transform:uppercase;" />
        </div>

        <!-- Group -->
        <div class="gms-field">
          <label class="gms-label">Group <span class="gf-req">*</span></label>
          <select v-model="form.group" class="gms-input gms-select">
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

      <hr class="gms-divider" style="margin:12px 0 10px;" />

      <!-- Expanders -->
      <div class="gf-expanders">
        <GmsBtn icon="plus" @click="toast('Companion flow coming soon.')">Add companion</GmsBtn>
        <GmsBtn icon="plus" @click="toast('Preferences flow coming soon.')">Flight / Accom / Transport preferences</GmsBtn>
      </div>

      <div class="gf-note">
        <span class="gf-note-badge">friendly</span>
        Only required fields shown by default — extras stay tucked away until needed.
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
        <div class="inv-rec-meta">{{ inviteRecipient.email ?? '—' }} · {{ groupFor(inviteRecipient.group)?.label ?? inviteRecipient.title ?? 'Guest' }}</div>
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
                <div class="inv-rec-row-sub">{{ groupFor(g.group)?.label ?? g.title ?? '—' }}</div>
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
