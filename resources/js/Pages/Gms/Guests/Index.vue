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
const drawerOpen   = ref(false)
const activeGuest  = ref(null)
const actionsMenuOpen = ref(null) // Track which guest's actions menu is open

function openDrawer(g) {
    activeGuest.value = g
    drawerOpen.value  = true
}

function tierFor(id)  { return props.tiers.find(t => t.id === id) }
function groupFor(id) { return props.groups.find(g => g.id === id) }
function hostFor(id)  { return props.hosts.find(h => h.id === id) }
function hotelFor(id) { return props.hotels.find(h => h.id === id) }

// ── Create / Edit modal ──────────────────────────────────────────
const guestModal   = ref(false)
const editingGuest = ref(null)

const form = useForm({
    name:         '',
    firstName:    '',
    lastName:     '',
    title:        '',
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
    form.firstName    = g.firstName ?? ''
    form.lastName     = g.lastName  ?? ''
    form.title        = g.title     ?? ''
    form.tier         = g.tier
    form.group        = g.group     ?? ''
    form.host         = g.host      ?? ''
    form.hotel        = g.hotel     ?? ''
    form.nationality  = g.nationality ?? ''
    form.status       = g.status
    form.email        = g.email     ?? ''
    form.phone        = g.phone     ?? ''
    form.dietaryNotes = g.dietaryNotes ?? ''
    form.notes        = g.notes     ?? ''
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
const inviteModal = ref(false)
const inviteStep  = ref(1)
const inviteRecipient = ref(null)
const selectedMatches = ref([])
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
    if (idx > -1) {
        selectedMatches.value.splice(idx, 1)
    } else {
        selectedMatches.value.push(matchId)
    }
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

function venueFor(id) { 
    return props.venues.find(v => v.id === id) 
}

function getTeamFlag(teamName) {
    const flagMap = {
        'Qatar': '🇶🇦',
        'Brazil': '🇧🇷',
        'France': '🇫🇷',
        'Germany': '🇩🇪',
        'Italy': '🇮🇹',
        'TBD': '🏳️',
    }
    return flagMap[teamName] || '⚽'
}

// Close actions menu when clicking outside
function handleClickOutside(e) {
    if (actionsMenuOpen.value && !e.target.closest('.gms-menu-pop') && !e.target.closest('.gms-btn-icon')) {
        actionsMenuOpen.value = null
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
        <button class="gms-btn gms-btn-ghost">
          <GmsIcon name="download" :size="14" />
          Export
        </button>
        <button class="gms-btn gms-btn-primary" @click="openNew">
          <GmsIcon name="plus" :size="14" />
          Add Guest
        </button>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="gms-stats">
      <div class="gms-stat">
        <div class="gms-stat-strip"></div>
        <div class="gms-stat-number">{{ localGuests.length }}</div>
        <div class="gms-stat-label">Total Guests</div>
      </div>
      <div class="gms-stat clickable" @click="statusFilter = 'confirmed'">
        <div class="gms-stat-strip" style="background: #15803d;"></div>
        <div class="gms-stat-number">{{ countForStatus('confirmed') }}</div>
        <div class="gms-stat-label">Confirmed</div>
      </div>
      <div class="gms-stat clickable" @click="statusFilter = 'pending'">
        <div class="gms-stat-strip" style="background: #a16207;"></div>
        <div class="gms-stat-number">{{ countForStatus('pending') }}</div>
        <div class="gms-stat-label">Pending</div>
      </div>
      <div class="gms-stat clickable" @click="statusFilter = 'invited'">
        <div class="gms-stat-strip" style="background: #1d4ed8;"></div>
        <div class="gms-stat-number">{{ countForStatus('invited') }}</div>
        <div class="gms-stat-label">Invited</div>
      </div>
      <div class="gms-stat clickable" @click="statusFilter = 'declined'">
        <div class="gms-stat-strip" style="background: #dc2626;"></div>
        <div class="gms-stat-number">{{ countForStatus('declined') }}</div>
        <div class="gms-stat-label">Declined</div>
      </div>
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
  <GmsDrawer
    :open="drawerOpen"
    :title="activeGuest?.name ?? ''"
    :subtitle="activeGuest?.title ?? ''"
    @close="drawerOpen = false"
  >
    <template #header-prefix>
      <GmsAvatar v-if="activeGuest" :name="activeGuest.name" size="lg" />
    </template>

    <template v-if="activeGuest">
      <!-- Status + Tier pills -->
      <div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;">
        <GmsPill :value="activeGuest.status" />
        <span
          class="gms-pill"
          :style="{
            background: tierFor(activeGuest.tier)?.bg,
            color:      tierFor(activeGuest.tier)?.color,
          }"
        >{{ tierFor(activeGuest.tier)?.name ?? activeGuest.tier }}</span>
        <span v-if="activeGuest.nationality" class="gms-pill" style="background:var(--gms-surface-3);color:var(--gms-text-2);">
          {{ flagEmoji(activeGuest.nationality) }} {{ activeGuest.nationality }}
        </span>
      </div>

      <!-- Details -->
      <div class="gms-section-title">Contact</div>
      <div class="gms-detail-row"><span class="gms-detail-label">Email</span><span class="gms-detail-value gms-mono gms-small">{{ activeGuest.email || '—' }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Phone</span><span class="gms-detail-value gms-mono gms-small">{{ activeGuest.phone || '—' }}</span></div>

      <div class="gms-section-title" style="margin-top:16px;">Protocol</div>
      <div class="gms-detail-row"><span class="gms-detail-label">Group</span><span class="gms-detail-value">{{ groupFor(activeGuest.group)?.label ?? '—' }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Host</span><span class="gms-detail-value">{{ hostFor(activeGuest.host)?.name ?? '—' }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Hotel</span><span class="gms-detail-value">{{ hotelFor(activeGuest.hotel)?.name ?? '—' }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Dietary</span><span class="gms-detail-value">{{ activeGuest.dietaryNotes || 'None' }}</span></div>

      <!-- Tier facilities -->
      <div v-if="tierFor(activeGuest.tier)?.facilities?.length" style="margin-top:16px;">
        <div class="gms-section-title">Tier Facilities</div>
        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;">
          <span
            v-for="f in tierFor(activeGuest.tier).facilities"
            :key="f"
            class="gms-pill"
            :style="{ background: tierFor(activeGuest.tier).bg, color: tierFor(activeGuest.tier).color }"
          >{{ f }}</span>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="activeGuest.notes" style="margin-top:16px;">
        <div class="gms-section-title">Notes</div>
        <p style="font-size:13px;color:var(--gms-text-2);margin-top:6px;line-height:1.6;">{{ activeGuest.notes }}</p>
      </div>
    </template>

    <template #footer>
      <button class="gms-btn gms-btn-ghost" style="flex:1;" @click="openEdit(activeGuest, true)">
        <GmsIcon name="edit" :size="13" /> Edit
      </button>
      <button class="gms-btn gms-btn-danger" @click="openDelete(activeGuest.id); drawerOpen = false">
        <GmsIcon name="trash" :size="13" /> Delete
      </button>
    </template>
  </GmsDrawer>

  <!-- ── Create / Edit Modal ──────────────────────────────────────── -->
  <GmsModal
    :open="guestModal"
    :title="editingGuest ? 'Edit Guest' : 'New Guest'"
    size="lg"
    @close="guestModal = false"
  >
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">First Name</label>
          <input v-model="form.firstName" class="gms-input" placeholder="First name" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Last Name</label>
          <input v-model="form.lastName" class="gms-input" placeholder="Last name" />
        </div>
        <div class="gms-field gms-form-full">
          <label class="gms-label">Full Name / Display Name</label>
          <input v-model="form.name" class="gms-input" placeholder="e.g. H.H. Sheikh Tamim Al-Thani" />
          <span v-if="form.errors.name" class="gms-error">{{ form.errors.name }}</span>
        </div>
        <div class="gms-field gms-form-full">
          <label class="gms-label">Title / Role</label>
          <input v-model="form.title" class="gms-input" placeholder="e.g. President of France" />
        </div>
      </div>

      <hr class="gms-divider" />

      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Service Level (Tier)</label>
          <select v-model="form.tier" class="gms-input gms-select">
            <option v-for="t in tiers" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
          <span v-if="form.errors.tier" class="gms-error">{{ form.errors.tier }}</span>
        </div>
        <div class="gms-field">
          <label class="gms-label">Status</label>
          <select v-model="form.status" class="gms-input gms-select">
            <option value="invited">Invited</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="declined">Declined</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Group</label>
          <select v-model="form.group" class="gms-input gms-select">
            <option value="">— None —</option>
            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.label }}</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Nationality (2-letter)</label>
          <input v-model="form.nationality" class="gms-input" placeholder="QA, FR, GB…" maxlength="2" style="text-transform:uppercase;" />
        </div>
      </div>

      <hr class="gms-divider" />

      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Email</label>
          <input v-model="form.email" type="email" class="gms-input" placeholder="guest@example.com" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Phone</label>
          <input v-model="form.phone" class="gms-input" placeholder="+974 …" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Assigned Host</label>
          <select v-model="form.host" class="gms-input gms-select">
            <option value="">— None —</option>
            <option v-for="h in hosts" :key="h.id" :value="h.id">{{ h.name }}</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Hotel</label>
          <select v-model="form.hotel" class="gms-input gms-select">
            <option value="">— None —</option>
            <option v-for="h in hotels" :key="h.id" :value="h.id">{{ h.name }}</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Dietary Requirements</label>
          <input v-model="form.dietaryNotes" class="gms-input" placeholder="Halal, Vegan, …" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Notes</label>
          <input v-model="form.notes" class="gms-input" placeholder="Internal notes…" />
        </div>
      </div>
    </div>

    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="guestModal = false">Cancel</button>
      <button class="gms-btn gms-btn-primary" :disabled="form.processing" @click="saveGuest">
        {{ editingGuest ? 'Save Changes' : 'Add Guest' }}
      </button>
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
  <GmsModal :open="inviteModal" title="Invite to Doha Cup" size="lg" @close="inviteModal = false">
    <!-- Wizard steps indicator -->
    <div class="gms-wizard-steps">
      <div class="gms-wizard-step" :class="{ active: inviteStep === 1, done: inviteStep > 1 }">
        <span class="gms-wizard-num">1</span>
        <span class="gms-wizard-label">Recipient</span>
      </div>
      <div class="gms-wizard-line" :class="{ done: inviteStep > 1 }"></div>
      <div class="gms-wizard-step" :class="{ active: inviteStep === 2, done: inviteStep > 2 }">
        <span class="gms-wizard-num">2</span>
        <span class="gms-wizard-label">Matches</span>
      </div>
      <div class="gms-wizard-line" :class="{ done: inviteStep > 2 }"></div>
      <div class="gms-wizard-step" :class="{ active: inviteStep === 3 }">
        <span class="gms-wizard-num">3</span>
        <span class="gms-wizard-label">Email & Send</span>
      </div>
    </div>

    <!-- Step 1: Recipient -->
    <div v-if="inviteStep === 1" style="padding-top:16px;">
      <div class="recipient-bar" v-if="inviteRecipient">
        <GmsAvatar :name="inviteRecipient.name" size="md" />
        <div style="flex:1;">
          <div style="font-weight:600;font-size:14px;">{{ inviteRecipient.name }}</div>
          <div style="font-size:11.5px;color:var(--gms-text-3);">{{ inviteRecipient.title || 'Guest' }}</div>
        </div>
        <span
          class="gms-pill"
          :style="{
            background: tierFor(inviteRecipient.tier)?.bg,
            color: tierFor(inviteRecipient.tier)?.color,
          }"
        >{{ tierFor(inviteRecipient.tier)?.name }}</span>
      </div>
      <div style="font-size:13px;color:var(--gms-text-2);text-align:center;padding:20px;">
        Ready to compose an invitation for <strong>{{ inviteRecipient?.name }}</strong>.
      </div>
    </div>

    <!-- Step 2: Match Selection -->
    <div v-if="inviteStep === 2" style="padding-top:16px;">
      <div class="recipient-bar" v-if="inviteRecipient" style="margin-bottom:16px;">
        <span class="rl">Recipient</span>
        <GmsAvatar :name="inviteRecipient.name" size="sm" />
        <div style="flex:1;font-weight:600;font-size:13px;">{{ inviteRecipient.name }}</div>
      </div>

      <div style="font-size:12px;font-weight:600;color:var(--gms-text-2);margin-bottom:12px;text-transform:uppercase;letter-spacing:0.5px;">
        Select Matches to Include
      </div>

      <div class="match-list">
        <div
          v-for="match in matches"
          :key="match.id"
          class="match-card"
          :class="{ sel: selectedMatches.includes(match.id) }"
          @click="toggleMatch(match.id)"
        >
          <div class="mc-top">
            <span class="mc-stage" :class="{ final: match.stage === 'Final' }">{{ match.stage }}</span>
            <div class="mc-check">
              <GmsIcon v-if="selectedMatches.includes(match.id)" name="check" :size="12" />
            </div>
          </div>

          <div class="mc-teams" v-if="match.awayTeam">
            <div class="team">
              <span class="fl">{{ getTeamFlag(match.homeTeam) }}</span>
              <span>{{ match.homeTeam }}</span>
            </div>
            <span class="vs">vs</span>
            <div class="team r">
              <span>{{ match.awayTeam }}</span>
              <span class="fl">{{ getTeamFlag(match.awayTeam) }}</span>
            </div>
          </div>

          <div v-else style="font-weight:700;font-size:14px;text-align:center;">
            {{ match.name }}
          </div>

          <div class="mc-meta">
            <span>{{ match.date }}</span>
            <div class="dot"></div>
            <span>{{ match.kickoff }}</span>
            <div class="dot"></div>
            <span>{{ venueFor(match.venueId)?.name || 'TBD' }}</span>
          </div>
        </div>
      </div>

      <div v-if="!matches.length" class="gms-empty" style="padding:30px;">
        <div class="gms-empty-title">No matches available</div>
        <div class="gms-empty-sub">Please configure matches for this event.</div>
      </div>
    </div>

    <!-- Step 3: Email Composition & Preview -->
    <div v-if="inviteStep === 3" style="padding-top:16px;">
      <div class="recipient-bar" v-if="inviteRecipient" style="margin-bottom:16px;">
        <span class="rl">Recipient</span>
        <GmsAvatar :name="inviteRecipient.name" size="sm" />
        <div style="flex:1;font-weight:600;font-size:13px;">{{ inviteRecipient.name }}</div>
        <span style="font-size:11px;color:var(--gms-text-3);">
          {{ selectedMatches.length }} {{ selectedMatches.length === 1 ? 'match' : 'matches' }}
        </span>
      </div>

      <div class="email-grid">
        <!-- Left: Email Editor -->
        <div>
          <div class="gms-field">
            <label class="gms-label">Subject</label>
            <input v-model="emailSubject" class="gms-input" />
          </div>

          <div class="gms-field">
            <label class="gms-label">Message Body</label>
            <textarea
              v-model="emailBody"
              class="body-area"
              style="font-family:var(--gms-font-ui);font-size:13px;min-height:260px;"
            ></textarea>
          </div>

          <div class="tags-row">
            <button class="tag-chip" @click="insertTag('guest_name')">{{guest_name}}</button>
            <button class="tag-chip" @click="insertTag('tier_name')">{{tier_name}}</button>
            <button class="tag-chip" @click="insertTag('match_list')">{{match_list}}</button>
          </div>
        </div>

        <!-- Right: Preview -->
        <div>
          <div class="preview-label">
            <GmsIcon name="eye" :size="13" />
            Preview
          </div>
          <div class="gms-card" style="padding:16px;background:var(--gms-surface-2);border:1px solid var(--gms-border);border-radius:10px;">
            <div style="font-weight:700;font-size:14px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--gms-border);">
              {{ emailSubject }}
            </div>
            <div style="font-size:13px;line-height:1.65;color:var(--gms-text);white-space:pre-wrap;">
              {{ previewEmail }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <button
        v-if="inviteStep > 1"
        class="gms-btn gms-btn-ghost"
        @click="prevInviteStep"
      >
        <GmsIcon name="chevron-left" :size="13" />
        Back
      </button>
      <button class="gms-btn gms-btn-ghost" @click="inviteModal = false">Cancel</button>
      <button
        v-if="inviteStep < 3"
        class="gms-btn gms-btn-primary"
        @click="nextInviteStep"
      >
        Next
        <GmsIcon name="chevron-right" :size="13" />
      </button>
      <button
        v-if="inviteStep === 3"
        class="gms-btn gms-btn-primary"
        :disabled="!selectedMatches.length"
        @click="sendInvitation"
      >
        <GmsIcon name="mail" :size="13" />
        Send Invitation
      </button>
    </template>
  </GmsModal>
</template>
