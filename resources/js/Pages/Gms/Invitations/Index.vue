<script setup>
import { ref, computed, inject, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsPill from '@/Components/Gms/GmsPill.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'
import GmsDrawer from '@/Components/Gms/GmsDrawer.vue'
import GmsBtn from '@/Components/Gms/GmsBtn.vue'
import GmsConfirmModal from '@/Components/Gms/GmsConfirmModal.vue'
import InviteWizard from '@/Components/Gms/InviteWizard.vue'

defineOptions({ 
    layout: GmsLayout,
    inheritAttrs: false 
})

const props = defineProps({
    roster:         { type: Array,  default: () => [] },
    directory:      { type: Array,  default: () => [] },
    tiers:          { type: Array,  default: () => [] },
    emailTemplates: { type: Array,  default: () => [] },
    matches:        { type: Array,  default: () => [] },
    event:          { type: Object, default: () => ({}) },
})

const toast = inject('toast')

// ── View toggle ─────────────────────────────────────────────────
const view = ref('list') // 'list' | 'services'

// ── Filters ─────────────────────────────────────────────────────
const search       = ref('')
const statusFilter = ref('all')

const statuses = ['all', 'not_invited', 'invited', 'pending', 'accepted', 'declined']

const filtered = computed(() => {
    let list = props.roster || []

    if (view.value === 'services') {
        list = list.filter(g => g.status === 'confirmed' || g.status === 'accepted')
    } else if (statusFilter.value !== 'all') {
        list = list.filter(g => g.status === statusFilter.value)
    }

    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(g =>
            g.name.toLowerCase().includes(q) ||
            (g.email || '').toLowerCase().includes(q)
        )
    }
    return list
})

// ── Stats ───────────────────────────────────────────────────────
const rosterCount     = computed(() => (props.roster || []).length)
const notInvitedCount = computed(() => (props.roster || []).filter(g => g.status === 'not_invited').length)
const invitedCount    = computed(() => (props.roster || []).filter(g => g.status === 'invited').length)
const pendingCount    = computed(() => (props.roster || []).filter(g => g.status === 'pending').length)
const acceptedCount   = computed(() => (props.roster || []).filter(g => g.status === 'accepted' || g.status === 'confirmed').length)
const declinedCount   = computed(() => (props.roster || []).filter(g => g.status === 'declined').length)

function tierLabel(id) {
    return props.tiers.find(t => t.id === id)?.name ?? id
}

function tierStyle(id) {
    const t = props.tiers.find(t => t.id === id)
    return { background: t?.bg ?? '#f3f4f6', color: t?.color ?? '#374151', fontSize: '10.5px' }
}

function formatDate(dateString) {
    if (!dateString) return '—'
    const d = new Date(dateString)
    const day = d.getDate()
    const mon = d.toLocaleDateString('en-GB', { month: 'short' })
    const yr = d.getFullYear().toString().slice(-2)
    const h = d.getHours().toString().padStart(2, '0')
    const m = d.getMinutes().toString().padStart(2, '0')
    return `${day} ${mon} ${yr}, ${h}:${m}`
}

function statusLabel(s) {
    const map = { not_invited: 'Not invited', invited: 'Invited', pending: 'Pending', accepted: 'Confirmed', confirmed: 'Confirmed', declined: 'Declined' }
    return map[s] || s
}

// ── Refresh ─────────────────────────────────────────────────────
const isRefreshing = ref(false)
function refreshRoster() {
    isRefreshing.value = true
    router.reload({
        only: ['roster', 'directory'],
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { isRefreshing.value = false; toast('Roster refreshed') },
    })
}

// ── Add guests picker modal ─────────────────────────────────────
const addGuestsModal = ref(false)
const pickerSearch   = ref('')
const pickerSelected = ref(new Set())
const isAddingGuests = ref(false)

const filteredDirectory = computed(() => {
    const q = pickerSearch.value.toLowerCase()
    if (!q) return props.directory
    return props.directory.filter(g =>
        g.name.toLowerCase().includes(q) ||
        (g.group || '').toLowerCase().includes(q)
    )
})

function togglePickerSelect(guestId) {
    if (pickerSelected.value.has(guestId)) pickerSelected.value.delete(guestId)
    else pickerSelected.value.add(guestId)
    pickerSelected.value = new Set(pickerSelected.value)
}

function openAddGuests() {
    pickerSearch.value = ''
    pickerSelected.value = new Set()
    addGuestsModal.value = true
}

function confirmAddGuests() {
    if (!pickerSelected.value.size || isAddingGuests.value) return
    isAddingGuests.value = true
    const ids = [...pickerSelected.value]

    router.post(route('gms.invitations.addGuests'), { guest_ids: ids }, {
        preserveScroll: true,
        onSuccess: () => {
            isAddingGuests.value = false
            addGuestsModal.value = false
            pickerSelected.value = new Set()
            toast(`${ids.length} guest(s) added to roster`)
        },
        onError: () => { isAddingGuests.value = false; toast('Failed to add guests', 'error') },
    })
}

// ── Guest detail drawer ─────────────────────────────────────────
const guestDrawerOpen  = ref(false)
const activeGuest      = ref(null)
const actionsMenuOpen  = ref(null)
const menuPosition = ref({ top: 0, left: 0 })

function openGuestDrawer(g) {
    activeGuest.value = g
    guestDrawerOpen.value = true
}

// ── Invite wizard ───────────────────────────────────────────
const wizardOpen = ref(false)
const wizardGuest = ref(null)

function openInviteWizard(guest = null) {
    wizardGuest.value = guest
    wizardOpen.value = true
}

// ── Actions ─────────────────────────────────────────────────────
const acceptModal        = ref(false)
const acceptingGuest     = ref(null)
const isAccepting        = ref(false)

function acceptOnBehalf(guest) {
    if (!guest.invitation) { toast('No invitation found', 'error'); return }
    acceptingGuest.value = guest
    acceptModal.value = true
}

function confirmAcceptOnBehalf() {
    if (!acceptingGuest.value?.invitation) return
    isAccepting.value = true
    router.post(route('gms.invitations.acceptOnBehalf', acceptingGuest.value.invitation.id), {}, {
        preserveScroll: true,
        onSuccess: () => { isAccepting.value = false; acceptModal.value = false; acceptingGuest.value = null; toast('Invitation accepted on behalf of guest') },
        onError: (errors) => { isAccepting.value = false; toast(Object.values(errors)[0] || 'Failed to accept.', 'error') },
    })
}

function markConfirmed(guest) {
    if (!guest.invitation) return
    router.post(route('gms.invitations.markConfirmed', guest.invitation.id), {}, {
        preserveScroll: true,
        onSuccess: () => { actionsMenuOpen.value = null; toast('Marked as confirmed') },
        onError: (errors) => { toast(Object.values(errors)[0] || 'Failed.', 'error') },
    })
}

function markDeclined(guest) {
    if (!guest.invitation) return
    router.post(route('gms.invitations.markDeclined', guest.invitation.id), {}, {
        preserveScroll: true,
        onSuccess: () => { actionsMenuOpen.value = null; toast('Marked as declined') },
        onError: (errors) => { toast(Object.values(errors)[0] || 'Failed.', 'error') },
    })
}

function resetToPending(guest) {
    if (!guest.invitation) return
    router.post(route('gms.invitations.resetToPending', guest.invitation.id), {}, {
        preserveScroll: true,
        onSuccess: () => { actionsMenuOpen.value = null; toast('Reset to not invited') },
        onError: (errors) => { toast(Object.values(errors)[0] || 'Failed.', 'error') },
    })
}

function removeFromRoster(guest) {
    router.delete(route('gms.invitations.removeGuest', guest.id), {
        preserveScroll: true,
        onSuccess: () => { actionsMenuOpen.value = null; guestDrawerOpen.value = false; toast(`${guest.name} removed from roster`) },
        onError: (errors) => { toast(Object.values(errors)[0] || 'Failed.', 'error') },
    })
}

function toggleActionsMenu(guestId, event) {
    if (actionsMenuOpen.value === guestId) {
        actionsMenuOpen.value = null
        return
    }
    
    // Calculate menu position
    const button = event.currentTarget
    const rect = button.getBoundingClientRect()
    menuPosition.value = {
        top: rect.bottom + window.scrollY + 4,
        left: rect.left + window.scrollX - 150 + rect.width
    }
    actionsMenuOpen.value = guestId
}

function getServiceStatusIcon(status) {
  // console.log('getServiceStatusIcon called with status:', status)
    if (!status) return 'minus'
    if (status === 'confirmed' || status === 'assigned') return 'check-circle'
    if (status === 'pending' || status === 'new') return 'clock'
    if (status === 'cancelled') return 'x-circle'
    return 'minus'
}

function getServiceStatusColor(status) {
  // console.log('getServiceStatusIcon called with status:', status)
    if (!status) return 'var(--gms-text-3)'
    if (status === 'confirmed' || status === 'assigned') return 'var(--good)'
    if (status === 'pending' || status === 'new') return 'var(--warn)'
    if (status === 'cancelled') return 'var(--bad)'
    return 'var(--gms-text-3)'
}

function handleClickOutside(e) {
    if (actionsMenuOpen.value && !e.target.closest('.gms-menu-pop') && !e.target.closest('.gms-btn-icon')) actionsMenuOpen.value = null
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <div class="gms-view">
    <div class="gms-view-pad">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Invitations</h1>
        <p class="gms-view-subtitle">Roster for {{ event?.name ?? 'event' }} — everyone added to {{ event?.name ?? 'event' }} to invite them.</p>
      </div>
      <div class="gms-view-actions">
        <GmsBtn icon="download" @click="() => toast('Export feature coming soon')">Export</GmsBtn>
        <GmsBtn icon="plus" @click="openAddGuests">Add guests</GmsBtn>
        <GmsBtn variant="primary" icon="mail" @click="openInviteWizard(null)">New invitation</GmsBtn>
      </div>
    </div>

    <!-- View toggle -->
    <div class="gms-seg" style="width: fit-content; margin-bottom: 20px;">
      <button :class="{ on: view === 'list' }" @click="view = 'list'">Invitation list</button>
      <button :class="{ on: view === 'services' }" @click="view = 'services'">Guest services overview</button>
    </div>

    <!-- Stats strip -->
    <div v-if="view === 'list'" class="gms-stats">
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--gms-maroon);"></div>
        <div class="gms-stat-number">{{ rosterCount }}</div>
        <div class="gms-stat-label">On roster</div>
      </div>
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--ink-3);"></div>
        <div class="gms-stat-number">{{ notInvitedCount }}</div>
        <div class="gms-stat-label">Not invited</div>
      </div>
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--warn);"></div>
        <div class="gms-stat-number">{{ invitedCount }}</div>
        <div class="gms-stat-label">Invited · awaiting</div>
      </div>
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: #f59e0b;"></div>
        <div class="gms-stat-number">{{ pendingCount }}</div>
        <div class="gms-stat-label">Pending</div>
      </div>
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--good);"></div>
        <div class="gms-stat-number">{{ acceptedCount }}</div>
        <div class="gms-stat-label">Accepted</div>
      </div>
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--bad);"></div>
        <div class="gms-stat-number">{{ declinedCount }}</div>
        <div class="gms-stat-label">Declined</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div v-if="view === 'list'" class="gms-toolbar">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search name or email…" />
      </div>
      <div class="gms-seg">
        <button v-for="s in statuses" :key="s" :class="{ on: statusFilter === s }" @click="statusFilter = s">
          {{ s === 'all' ? 'All' : statusLabel(s) }}
        </button>
      </div>
      <GmsBtn variant="ghost" icon="refresh-cw" icon-only :disabled="isRefreshing" :processing="isRefreshing" @click="refreshRoster" title="Refresh" style="margin-left:4px;" />
      <span class="mxt-count" style="margin-left: auto;">{{ filtered.length }} of {{ rosterCount }}</span>
    </div>

    <!-- Roster table (list view) -->
    <div v-if="view === 'list'" class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-table-wrap">
          <table class="gms-table">
            <thead>
              <tr>
                <th>Guest</th>
                <th>Service</th>
                <th>Sessions</th>
                <th>Status</th>
                <th>Sent / Accepted</th>
                <th>Passport</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="g in filtered" :key="g.id" @click="openGuestDrawer(g)" style="cursor:pointer;">
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <GmsAvatar :name="g.name" size="sm" />
                    <div>
                      <div style="font-weight:600;font-size:13px;">{{ g.name }}</div>
                      <div style="font-size:11.5px;color:var(--gms-text-3);">{{ g.email }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="gms-pill" :style="tierStyle(g.tier)">{{ tierLabel(g.tier) }}</span>
                </td>
                <td>
                  <span class="gms-muted gms-small">{{ g.sessions }} sessions</span>
                </td>
                <td>
                  <span class="inv-status-pill" :class="g.status">
                    <span class="inv-status-dot"></span>
                    {{ statusLabel(g.status) }}
                  </span>
                </td>
                <td>
                  <div class="gms-mono" style="font-size:11.5px;color:var(--gms-text-3);">
                    <div>{{ g.invitation?.sent_at ? formatDate(g.invitation.sent_at) : '—' }}</div>
                    <div>{{ g.invitation?.responded_at ? formatDate(g.invitation.responded_at) : '' }}</div>
                  </div>
                </td>
                <td>
                  <span v-if="g.passport" class="gms-pill" style="background:var(--good-soft);color:var(--good);font-size:10.5px;">On file</span>
                  <span v-else class="gms-muted gms-small">—</span>
                </td>
                <td @click.stop>
                  <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
                    <!-- Invite button for not-invited guests -->
                    <GmsBtn
                      v-if="g.status === 'not_invited'"
                      variant="ghost"
                      icon="mail"
                      @click="openInviteWizard(g)"
                      style="font-size:12px;"
                    >Invite</GmsBtn>
                    <!-- Edit button for invited guests -->
                    <GmsBtn
                      v-else
                      variant="ghost"
                      icon="edit"
                      icon-only
                      title="Edit invitation"
                      @click="openGuestDrawer(g)"
                    />
                    <!-- More actions -->
                    <GmsBtn variant="ghost" icon="more-vertical" icon-only title="More actions" @click.stop="toggleActionsMenu(g.id, $event)" />
                  </div>
                </td>
              </tr>
              <tr v-if="!filtered.length">
                <td colspan="7">
                  <div class="gms-empty">
                    <div class="gms-empty-title">No guests on roster yet</div>
                    <div class="gms-empty-sub">Add guests from the directory to get started.</div>
                    <GmsBtn icon="plus" style="margin-top:12px;" @click="openAddGuests">Add guests</GmsBtn>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Services overview table -->
    <div v-if="view === 'services'" class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-toolbar" style="border-bottom: 1px solid var(--gms-border); padding: 12px 16px;">
          <div class="gms-search-wrap">
            <GmsIcon name="search" :size="14" class="gms-search-icon" />
            <input v-model="search" class="gms-search-input" placeholder="Search confirmed guests…" />
          </div>
        </div>
        <div class="gms-table-wrap" style="overflow-x:auto;">
          <table class="gms-table" style="min-width:860px;">
            <thead>
              <tr>
                <th>Guest</th>
                <th>Group</th>
                <th>Status</th>
                <th>Flight</th>
                <th>Hotel</th>
                <th>Seat</th>
                <th>Transport</th>
                <th>A &amp; D</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="g in filtered" :key="g.id">
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <GmsAvatar :name="g.name" size="sm" />
                    <span style="font-weight:600;">{{ g.name }}</span>
                  </div>
                </td>
                <td><span class="gms-muted gms-small">{{ g.group }}</span></td>
                <td><span class="inv-status-pill" :class="g.status"><span class="inv-status-dot"></span>{{ statusLabel(g.status) }}</span></td>
                <td>
                  <span v-if="g.services?.flight" class="inv-status-pill" :class="g.services.flight">
                    <span class="inv-status-dot"></span>
                    {{ g.services.flight.charAt(0).toUpperCase() + g.services.flight.slice(1) }}
                  </span>
                  <span v-else class="gms-muted">—</span>
                </td>
                <td>
                  <span v-if="g.services?.accommodation" class="inv-status-pill" :class="g.services.accommodation">
                    <span class="inv-status-dot"></span>
                    {{ g.services.accommodation.charAt(0).toUpperCase() + g.services.accommodation.slice(1) }}
                  </span>
                  <span v-else class="gms-muted">—</span>
                </td>
                <td>
                  <span v-if="g.services?.seat" class="inv-status-pill" :class="g.services.seat">
                    <span class="inv-status-dot"></span>
                    {{ g.services.seat.charAt(0).toUpperCase() + g.services.seat.slice(1) }}
                  </span>
                  <span v-else class="gms-muted">—</span>
                </td>
                <td>
                  <span v-if="g.services?.transport" class="inv-status-pill" :class="g.services.transport">
                    <span class="inv-status-dot"></span>
                    {{ g.services.transport.charAt(0).toUpperCase() + g.services.transport.slice(1) }}
                  </span>
                  <span v-else class="gms-muted">—</span>
                </td>
                <td>
                  <span v-if="g.services?.ad" class="inv-status-pill" :class="g.services.ad">
                    <span class="inv-status-dot"></span>
                    {{ g.services.ad.charAt(0).toUpperCase() + g.services.ad.slice(1) }}
                  </span>
                  <span v-else class="gms-muted">—</span>
                </td>
              </tr>
              <tr v-if="!filtered.length">
                <td colspan="8"><div class="gms-empty"><div class="gms-empty-title">No confirmed guests yet</div></div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    </div>
  </div>

  <!-- ── Add Guests Picker Modal ──────────────────────────────────── -->
  <GmsModal :open="addGuestsModal" :title="'Add guests to ' + (event?.name ?? 'event')" size="sm" @close="addGuestsModal = false">
    <p style="font-size:13px;color:var(--gms-text-2);margin-bottom:16px;">
      Pull people from the directory onto this event's roster. They'll start as <strong>Not invited</strong>.
    </p>

    <div class="gms-search-wrap" style="margin-bottom:12px;">
      <GmsIcon name="search" :size="14" class="gms-search-icon" />
      <input v-model="pickerSearch" class="gms-search-input" placeholder="Search directory…" />
    </div>

    <div class="add-picker-list">
      <div
        v-for="g in filteredDirectory" :key="g.id"
        class="add-picker-row"
        :class="{ sel: pickerSelected.has(g.id) }"
        @click="togglePickerSelect(g.id)"
      >
        <label class="dir-check-wrap" @click.stop>
          <input type="checkbox" :checked="pickerSelected.has(g.id)" @change="togglePickerSelect(g.id)" class="dir-check" />
        </label>
        <GmsAvatar :name="g.name" size="sm" />
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;font-size:13px;">{{ g.name }}</div>
          <div style="font-size:11px;color:var(--gms-text-3);">
            {{ g.group ?? '' }}
            <span v-if="g.group && g.nationality"> · </span>
            <span v-if="g.nationality" style="text-transform:uppercase;letter-spacing:0.04em;">{{ g.nationality }}</span>
            {{ g.guestType === 'international' ? " Int'l" : ' Local' }}
          </div>
        </div>
        <span class="gms-pill" :style="tierStyle(g.tier)">{{ tierLabel(g.tier) }}</span>
      </div>
      <div v-if="!filteredDirectory.length" class="gms-empty" style="padding:20px 0;">
        <div class="gms-empty-title">No guests available</div>
        <div class="gms-empty-sub">All directory guests are already on this event.</div>
      </div>
    </div>

    <template #footer>
      <div style="display:flex;align-items:center;justify-content:space-between;width:100%;">
        <span style="font-size:12px;color:var(--gms-text-3);">{{ pickerSelected.size }} selected</span>
        <div style="display:flex;gap:8px;">
          <GmsBtn @click="addGuestsModal = false">Cancel</GmsBtn>
          <GmsBtn variant="primary" icon="plus" :disabled="!pickerSelected.size || isAddingGuests" :processing="isAddingGuests" @click="confirmAddGuests">
            Add {{ pickerSelected.size || '' }} to roster
          </GmsBtn>
        </div>
      </div>
    </template>
  </GmsModal>

  <!-- ── Guest profile drawer ──────────────────────────────────────── -->
  <GmsDrawer
    :open="guestDrawerOpen"
    :title="activeGuest?.name || 'Guest Profile'"
    :subtitle="activeGuest?.title"
    @close="guestDrawerOpen = false; activeGuest = null"
  >
    <template v-if="activeGuest" #header-prefix>
      <GmsAvatar :name="activeGuest.name" size="lg" />
    </template>

    <div v-if="activeGuest" style="padding: 24px;">
      <div class="gms-section-title" style="margin-bottom: 16px;">Guest Information</div>

      <div class="gms-detail-row"><span class="gms-detail-label">Reference</span><span class="gms-detail-value gms-mono">{{ activeGuest.reference_number || activeGuest.id }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Full Name</span><span class="gms-detail-value">{{ activeGuest.name }}</span></div>
      <div v-if="activeGuest.title" class="gms-detail-row"><span class="gms-detail-label">Title</span><span class="gms-detail-value">{{ activeGuest.title }}</span></div>
      <div v-if="activeGuest.email" class="gms-detail-row"><span class="gms-detail-label">Email</span><span class="gms-detail-value">{{ activeGuest.email }}</span></div>
      <div v-if="activeGuest.phone" class="gms-detail-row"><span class="gms-detail-label">Phone</span><span class="gms-detail-value">{{ activeGuest.phone }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Service Level</span><span class="gms-detail-value"><span class="gms-pill" :style="tierStyle(activeGuest.tier)">{{ tierLabel(activeGuest.tier) }}</span></span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Status</span><span class="gms-detail-value"><span class="inv-status-pill" :class="activeGuest.status"><span class="inv-status-dot"></span>{{ statusLabel(activeGuest.status) }}</span></span></div>
      <div v-if="activeGuest.group" class="gms-detail-row"><span class="gms-detail-label">Group</span><span class="gms-detail-value">{{ activeGuest.group }}</span></div>
      <div v-if="activeGuest.notes" class="gms-detail-row"><span class="gms-detail-label">Notes</span><span class="gms-detail-value">{{ activeGuest.notes }}</span></div>

      <!-- Services Status -->
      <div class="gms-section-title" style="margin-top: 32px; margin-bottom: 16px;">Services</div>
      <div class="gms-detail-row"><span class="gms-detail-label">Flight</span><span class="gms-detail-value" style="display:flex;align-items:center;gap:8px;"><GmsIcon :name="getServiceStatusIcon(activeGuest.services?.flight)" :size="16" :style="{ color: getServiceStatusColor(activeGuest.services?.flight) }" /><span style="text-transform:capitalize;">{{ activeGuest.services?.flight || 'Not requested' }}</span></span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Accommodation</span><span class="gms-detail-value" style="display:flex;align-items:center;gap:8px;"><GmsIcon :name="getServiceStatusIcon(activeGuest.services?.accommodation)" :size="16" :style="{ color: getServiceStatusColor(activeGuest.services?.accommodation) }" /><span style="text-transform:capitalize;">{{ activeGuest.services?.accommodation || 'Not requested' }}</span></span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Seating</span><span class="gms-detail-value" style="display:flex;align-items:center;gap:8px;"><GmsIcon :name="getServiceStatusIcon(activeGuest.services?.seat)" :size="16" :style="{ color: getServiceStatusColor(activeGuest.services?.seat) }" /><span style="text-transform:capitalize;">{{ activeGuest.services?.seat || 'Not assigned' }}</span></span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Transport</span><span class="gms-detail-value" style="display:flex;align-items:center;gap:8px;"><GmsIcon :name="getServiceStatusIcon(activeGuest.services?.transport)" :size="16" :style="{ color: getServiceStatusColor(activeGuest.services?.transport) }" /><span style="text-transform:capitalize;">{{ activeGuest.services?.transport || 'Not requested' }}</span></span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Arrival & Departure</span><span class="gms-detail-value" style="display:flex;align-items:center;gap:8px;"><GmsIcon :name="getServiceStatusIcon(activeGuest.services?.ad)" :size="16" :style="{ color: getServiceStatusColor(activeGuest.services?.ad) }" /><span style="text-transform:capitalize;">{{ activeGuest.services?.ad || 'Not requested' }}</span></span></div>

      <!-- Invitation Details -->
      <div v-if="activeGuest.invitation">
        <div class="gms-section-title" style="margin-top: 32px; margin-bottom: 16px;">Invitation</div>
        <div class="gms-detail-row"><span class="gms-detail-label">Sent</span><span class="gms-detail-value">{{ formatDate(activeGuest.invitation.sent_at) }}</span></div>
        <div v-if="activeGuest.invitation.responded_at" class="gms-detail-row"><span class="gms-detail-label">Responded</span><span class="gms-detail-value">{{ formatDate(activeGuest.invitation.responded_at) }}</span></div>

        <div v-if="activeGuest.invitation.matches?.length > 0" style="margin-top:16px;">
          <div style="font-size:12px;font-weight:600;color:var(--gms-text-2);margin-bottom:8px;">Matches ({{ activeGuest.invitation.matches.length }})</div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <div v-for="m in activeGuest.invitation.matches" :key="m.id" style="padding:10px;border:1px solid var(--gms-border);border-radius:8px;">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <span class="gms-pill" style="background:var(--gms-maroon-tint);color:var(--gms-maroon);font-size:10px;font-weight:700;text-transform:uppercase;">{{ m.stage }}</span>
                <span v-if="m.response === 'yes'" class="gms-pill" style="background:var(--good-soft);color:var(--good);font-size:10px;">✓ Accepted</span>
                <span v-else-if="m.response === 'no'" class="gms-pill" style="background:var(--bad-soft);color:var(--bad);font-size:10px;">✕ Declined</span>
                <span v-else class="gms-pill" style="background:#f3f4f6;color:#6b7280;font-size:10px;">Pending</span>
              </div>
              <div style="font-weight:600;font-size:13px;">{{ m.homeTeam }} vs {{ m.awayTeam }}</div>
              <div style="font-size:11px;color:var(--gms-text-3);">{{ m.venue }} · {{ m.time }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <GmsBtn variant="ghost" @click="guestDrawerOpen = false; activeGuest = null">Close</GmsBtn>
      <GmsBtn variant="primary" icon="arrow-right" @click="router.visit(route('gms.guests.index'))">View Full Profile</GmsBtn>
    </template>
  </GmsDrawer>

  <!-- Accept on behalf confirmation modal -->
  <GmsConfirmModal
    :open="acceptModal"
    :loading="isAccepting"
    title="Accept Invitation on Behalf"
    :message="acceptingGuest ? `Are you sure you want to accept this invitation on behalf of <strong style='color: var(--gms-text);'>${acceptingGuest.name}</strong>?` : ''"
    description="This action will:"
    :details="[
      'Mark the invitation as <strong style=\'color: var(--good);\'>confirmed</strong>',
      `Accept <strong>all ${acceptingGuest?.invitation?.matches?.length || 0} matches</strong> offered`,
      'Update the guest status to <strong style=\'color: var(--good);\'>confirmed</strong>'
    ]"
    confirm-text="Accept on Behalf"
    confirm-icon="check-circle"
    @confirm="confirmAcceptOnBehalf"
    @close="acceptModal = false; acceptingGuest = null"
  >
    <p style="font-size: 13px; color: var(--gms-text-3); margin: 0;">
      The guest will not receive a notification about this action.
    </p>
  </GmsConfirmModal>

  <!-- Teleported actions menu -->
  <Teleport to="body">
    <div 
      v-if="actionsMenuOpen !== null" 
      class="gms-menu-pop"
      :style="{
        position: 'absolute',
        top: menuPosition.top + 'px',
        left: menuPosition.left + 'px',
        zIndex: 9999
      }"
    >
      <button class="gms-menu-item" @click="openGuestDrawer(filtered.find(g => g.id === actionsMenuOpen)); actionsMenuOpen = null"><GmsIcon name="eye" :size="16" /> View profile</button>
      <button v-if="filtered.find(g => g.id === actionsMenuOpen)?.invitation" class="gms-menu-item" @click="acceptOnBehalf(filtered.find(g => g.id === actionsMenuOpen)); actionsMenuOpen = null"><GmsIcon name="check-circle" :size="16" /> Accept on behalf</button>
      <button v-if="filtered.find(g => g.id === actionsMenuOpen)?.invitation" class="gms-menu-item" @click="markConfirmed(filtered.find(g => g.id === actionsMenuOpen))"><GmsIcon name="badge" :size="16" /> Mark confirmed</button>
      <button v-if="filtered.find(g => g.id === actionsMenuOpen)?.invitation" class="gms-menu-item" @click="markDeclined(filtered.find(g => g.id === actionsMenuOpen))"><GmsIcon name="x" :size="16" /> Mark declined</button>
      <button v-if="filtered.find(g => g.id === actionsMenuOpen)?.invitation" class="gms-menu-item" @click="resetToPending(filtered.find(g => g.id === actionsMenuOpen))"><GmsIcon name="refresh-cw" :size="16" /> Reset to not invited</button>
      <div class="gms-menu-sep"></div>
      <button class="gms-menu-item danger" @click="removeFromRoster(filtered.find(g => g.id === actionsMenuOpen))"><GmsIcon name="trash" :size="16" /> Remove from roster</button>
    </div>
  </Teleport>

  <!-- Invite Wizard -->
  <InviteWizard
    :open="wizardOpen"
    :initial-guest="wizardGuest"
    :guests="directory"
    :matches="matches"
    :tiers="tiers"
    :email-templates="emailTemplates"
    :event="event"
    @close="wizardOpen = false; wizardGuest = null"
  />
</template>
