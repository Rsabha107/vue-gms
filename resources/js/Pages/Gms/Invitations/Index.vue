<script setup>
import { ref, computed, inject, onMounted, onUnmounted } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsPill from '@/Components/Gms/GmsPill.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'
import GmsDrawer from '@/Components/Gms/GmsDrawer.vue'
import GmsConfirmModal from '@/Components/Gms/GmsConfirmModal.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    invitations:    { type: Array,  default: () => [] },
    tiers:          { type: Array,  default: () => [] },
    emailTemplates: { type: Array,  default: () => [] },
    event:          { type: Object, default: () => ({}) },
})

const toast = inject('toast')

// ── Filters ───────────────────────────────────────────────────────
const view     = ref('list') // 'list' | 'services'
const search   = ref('')
const statusFilter = ref('all')
const statuses = ['all', 'draft', 'sent', 'accepted', 'partial', 'confirmed', 'declined']

const filtered = computed(() => {
    let list = props.invitations || []
    
    // For services view, only show confirmed invitations
    if (view.value === 'services') {
        list = list.filter(inv => inv.status === 'confirmed')
    } else if (statusFilter.value !== 'all') {
        list = list.filter(inv => inv.status === statusFilter.value || inv.guest_status === statusFilter.value)
    }
    
    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(inv => 
            inv.guest_name.toLowerCase().includes(q) || 
            (inv.guest_title || '').toLowerCase().includes(q)
        )
    }
    return list
})

function countFor(s) {
    const invitations = props.invitations || []
    if (s === 'all') return invitations.length
    return invitations.filter(inv => inv.status === s || inv.guest_status === s).length
}

function tierLabel(id) {
    return props.tiers.find(t => t.id === id)?.name ?? id
}

// Count accepted matches for an invitation
function acceptedMatchesCount(invitation) {
    if (!invitation.matches) return 0
    return invitation.matches.filter(m => m.response === 'yes').length
}

// Count declined matches for an invitation
function declinedMatchesCount(invitation) {
    if (!invitation.matches) return 0
    return invitation.matches.filter(m => m.response === 'no').length
}

// Count pending (no response) matches for an invitation
function pendingMatchesCount(invitation) {
    if (!invitation.matches) return 0
    return invitation.matches.filter(m => !m.response).length
}

// Compute rollup status from per-match responses
// Prefers database status for consistency, falls back to computation for real-time UI updates
function getRollupStatus(invitation) {
    // Use database status if available (updated by backend after RSVP/actions)
    if (invitation.status) {
        // Map database statuses to display values
        const statusMap = {
            'sent': 'sent',
            'accepted': 'accepted',     // Guest accepted via RSVP
            'confirmed': 'confirmed',   // Admin marked as confirmed
            'partial': 'partial',
            'declined': 'declined'
        }
        
        // If no response yet, check if it's truly pending
        if (invitation.status === 'sent' && invitation.matches && invitation.matches.length > 0) {
            const hasAnyResponse = invitation.matches.some(m => m.response)
            if (hasAnyResponse) {
                // Has responses but status not updated yet (real-time UI case)
                // Fall through to computation below
            } else {
                return 'pending'
            }
        } else if (statusMap[invitation.status]) {
            return statusMap[invitation.status]
        }
    }
    
    // Fallback: compute from match responses (for real-time UI updates before DB sync)
    if (!invitation.matches || invitation.matches.length === 0) return 'sent'
    
    const accepted = acceptedMatchesCount(invitation)
    const declined = declinedMatchesCount(invitation)
    const pending = pendingMatchesCount(invitation)
    const total = invitation.matches.length
    
    // No responses yet
    if (pending === total) return 'pending'
    
    // Some answered, some not
    if (pending > 0) return 'awaiting'
    
    // All answered
    if (accepted === total) return 'accepted'
    if (declined === total) return 'declined'
    
    // Mix of yes/no
    return 'partial'
}

// Get response summary text
function getResponseSummary(invitation) {
    const accepted = acceptedMatchesCount(invitation)
    const declined = declinedMatchesCount(invitation)
    const pending = pendingMatchesCount(invitation)
    
    const parts = []
    if (accepted > 0) parts.push(`${accepted} accepted`)
    if (declined > 0) parts.push(`${declined} declined`)
    if (pending > 0) parts.push(`${pending} pending`)
    
    return parts.join(' · ')
}

// Format date as "9 Aug 26, 14:00"
function formatInvitationDate(dateString) {
    if (!dateString) return '—'
    const date = new Date(dateString)
    const day = date.getDate()
    const month = date.toLocaleDateString('en-GB', { month: 'short' })
    const year = date.getFullYear().toString().slice(-2)
    const hours = date.getHours().toString().padStart(2, '0')
    const minutes = date.getMinutes().toString().padStart(2, '0')
    return `${day} ${month} ${year}, ${hours}:${minutes}`
}

// ── Refresh ───────────────────────────────────────────────────────
const isRefreshing = ref(false)

function refreshInvitations() {
    isRefreshing.value = true
    router.reload({ 
        only: ['invitations'],
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isRefreshing.value = false
            toast('Invitations refreshed')
        },
    })
}

// ── Invitation detail drawer ─────────────────────────────────────
const detailDrawer = ref(false)
const activeInvitation = ref(null)
const showFullInvitation = ref(false)
const actionsMenuOpen = ref(null)
const menuButtonRef = ref(null)
const menuPosition = ref({ top: 0, left: 0 })

// ── Accept on behalf confirmation modal ──────────────────────────
const acceptModal = ref(false)
const acceptingInvitation = ref(null)

function viewInvitation(inv) {
    activeInvitation.value = inv
    showFullInvitation.value = false
    detailDrawer.value = true
}

function acceptOnBehalf(invitation) {
    acceptingInvitation.value = invitation
    acceptModal.value = true
}

function confirmAcceptOnBehalf() {
    if (!acceptingInvitation.value) return

    router.post(route('gms.invitations.acceptOnBehalf', acceptingInvitation.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            acceptModal.value = false
            acceptingInvitation.value = null
            toast('Invitation accepted on behalf of guest')
        },
        onError: (errors) => {
            console.error('Accept on behalf errors:', errors)
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Failed to accept invitation.', 'error')
        },
    })
}

function markConfirmed(invitation) {
    router.post(route('gms.invitations.markConfirmed', invitation.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            actionsMenuOpen.value = null
            toast('Invitation marked as confirmed')
        },
        onError: (errors) => {
            console.error('Mark confirmed errors:', errors)
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Failed to mark as confirmed.', 'error')
        },
    })
}

function markDeclined(invitation) {
    router.post(route('gms.invitations.markDeclined', invitation.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            actionsMenuOpen.value = null
            toast('Invitation marked as declined')
        },
        onError: (errors) => {
            console.error('Mark declined errors:', errors)
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Failed to mark as declined.', 'error')
        },
    })
}

function resetToPending(invitation) {
    router.post(route('gms.invitations.resetToPending', invitation.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            actionsMenuOpen.value = null
            toast('Invitation reset to pending')
        },
        onError: (errors) => {
            console.error('Reset to pending errors:', errors)
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Failed to reset to pending.', 'error')
        },
    })
}

// Get confirmed matches for the active invitation
function getConfirmedMatchesForInvitation(invitation) {
    if (!invitation?.matches) return []
    return invitation.matches.filter(m => m.response === 'yes')
}

function openRsvpPage(invitation = null) {
    const inv = invitation || activeInvitation.value
    if (inv?.rsvp_token) {
        window.open(`/rsvp/${inv.rsvp_token}`, '_blank')
    }
}

// ── Send modal ────────────────────────────────────────────────────
function openSend() {
    // Navigate to Guests page where the invite wizard is
    router.visit(route('gms.guests.index'))
}

function toggleActionsMenu(invitationId, event) {
    if (actionsMenuOpen.value === invitationId) {
        actionsMenuOpen.value = null
        menuButtonRef.value = null
    } else {
        actionsMenuOpen.value = invitationId
        menuButtonRef.value = event.currentTarget
        
        // Calculate position
        const rect = event.currentTarget.getBoundingClientRect()
        menuPosition.value = {
            top: rect.bottom + 4,
            left: rect.right - 166 // menu width
        }
    }
}

function getMenuPosition(invitationId) {
    if (actionsMenuOpen.value === invitationId) {
        return menuPosition.value
    }
    return { top: 0, left: 0 }
}

function handleClickOutside(e) {
    if (actionsMenuOpen.value && !e.target.closest('.gms-menu-pop') && !e.target.closest('.gms-btn-icon')) {
        actionsMenuOpen.value = null
        menuButtonRef.value = null
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
    <div class="gms-view-pad">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Invitations</h1>
        <p class="gms-view-subtitle">Manage outreach to registered guests</p>
      </div>
      <div class="gms-view-actions">
        <button 
          class="gms-btn gms-btn-ghost gms-btn-sm" 
          @click="refreshInvitations"
          :disabled="isRefreshing"
          title="Refresh invitations"
          style="margin-right: 8px;"
        >
          <GmsIcon name="refresh-cw" :size="14" :class="{ 'gms-spin': isRefreshing }" />
        </button>
        <button class="gms-btn" @click="() => toast('Export feature coming soon')">
          <GmsIcon name="download" :size="14" />
          Export
        </button>
        <button class="gms-btn gms-btn-primary" @click="openSend">
          <GmsIcon name="plus" :size="14" />
          New invitation
        </button>
      </div>
    </div>

    <!-- View toggle tabs -->
    <div class="gms-seg" style="width: fit-content; margin-bottom: 20px;">
      <button
        :class="{ on: view === 'list' }"
        @click="view = 'list'"
      >
        Invitation list
      </button>
      <button
        :class="{ on: view === 'services' }"
        @click="view = 'services'"
      >
        Guest services overview
      </button>
    </div>

    <!-- Mini stats (list view only) -->
    <div v-if="view === 'list'" class="gms-stats">
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--gms-maroon);"></div>
        <div class="gms-stat-number">{{ (props.invitations || []).length }}</div>
        <div class="gms-stat-label">Total sent</div>
      </div>
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--good);"></div>
        <div class="gms-stat-number">{{ countFor('accepted') }}</div>
        <div class="gms-stat-label">Accepted</div>
      </div>
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--good);"></div>
        <div class="gms-stat-number">{{ countFor('confirmed') }}</div>
        <div class="gms-stat-label">Confirmed</div>
      </div>
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--warn);"></div>
        <div class="gms-stat-number">{{ countFor('partial') }}</div>
        <div class="gms-stat-label">Partial</div>
      </div>
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--bad);"></div>
        <div class="gms-stat-number">{{ countFor('declined') }}</div>
        <div class="gms-stat-label">Declined</div>
      </div>
    </div>

    <!-- Status filters (list view only) -->
    <div v-if="view === 'list'" class="gms-toolbar">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search guests…" />
      </div>
      <div class="gms-seg">
        <button
          v-for="s in statuses" :key="s"
          :class="{ on: statusFilter === s }"
          @click="statusFilter = s"
        >
          {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
        </button>
      </div>
      
      <span class="mxt-count" style="margin-left: auto;">{{ filtered.length }} of {{ (props.invitations || []).length }}</span>
    </div>

    <!-- Guest table (list view) -->
    <div v-if="view === 'list'" class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-table-wrap">
          <table class="gms-table">
            <thead>
              <tr>
                <th>Guest</th>
                <th>Service</th>
                <th>Matches</th>
                <th>Status</th>
                <th>Sent / Responded</th>
                <th>RSVP</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in filtered" :key="inv.id">
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <GmsAvatar :name="inv.guest_name" size="sm" />
                    <div>
                      <div style="font-weight:600;font-size:13px;">{{ inv.guest_name }}</div>
                      <div style="font-size:11.5px;color:var(--gms-text-3);">{{ inv.guest_email }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span
                    class="gms-pill"
                    :style="{
                      background: tiers.find(t=>t.id===inv.guest_tier)?.bg ?? '#f3f4f6',
                      color: tiers.find(t=>t.id===inv.guest_tier)?.color ?? '#374151',
                      fontSize:'10.5px'
                    }"
                  >{{ tierLabel(inv.guest_tier) }}</span>
                </td>
                <td>
                  <div style="display:flex;flex-direction:column;gap:4px;">
                    <!-- Response strip dots -->
                    <div style="display:flex;align-items:center;gap:3px;">
                      <div
                        v-for="(match, idx) in inv.matches"
                        :key="idx"
                        :style="{
                          width: '8px',
                          height: '8px',
                          borderRadius: '50%',
                          background: match.response === 'yes' ? 'var(--good)' : match.response === 'no' ? 'var(--bad)' : '#d1d5db',
                        }"
                        :title="match.response === 'yes' ? 'Accepted' : match.response === 'no' ? 'Declined' : 'Pending'"
                      ></div>
                    </div>
                    <!-- Summary text -->
                    <div style="font-size:11px;color:var(--gms-text-3);">
                      {{ getResponseSummary(inv) }}
                    </div>
                  </div>
                </td>
                <td>
                  <GmsPill :value="getRollupStatus(inv)" />
                </td>
                <td>
                  <div class="gms-mono" style="font-size:11.5px;color:var(--gms-text-3);">
                    <div>{{ formatInvitationDate(inv.sent_at) }}</div>
                    <div>{{ inv.responded_at ? formatInvitationDate(inv.responded_at) : '' }}</div>
                  </div>
                </td>
                <td>
                  <span v-if="inv.responded_at" class="gms-pill good">Responded</span>
                  <span v-else class="gms-pill warn">Pending</span>
                </td>
                <td>
                  <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;position:relative;">
                    <button 
                      class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" 
                      title="Resend invitation"
                      @click="() => toast('Resend feature coming soon')"
                    >
                      <GmsIcon name="refresh-cw" :size="14" />
                    </button>
                    <button 
                      class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" 
                      title="Accept on behalf"
                      @click="acceptOnBehalf(inv)"
                    >
                      <GmsIcon name="check-circle" :size="14" />
                    </button>
                    <button 
                      class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" 
                      title="View invitation"
                      @click="viewInvitation(inv)"
                    >
                      <GmsIcon name="eye" :size="14" />
                    </button>
                    <button 
                      class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" 
                      title="More actions"
                      @click="(e) => toggleActionsMenu(inv.id, e)"
                    >
                      <GmsIcon name="more-vertical" :size="14" />
                    </button>

                    <!-- Actions dropdown menu -->
                    <Teleport to="body">
                      <div 
                        v-if="actionsMenuOpen === inv.id" 
                        class="gms-menu-pop"
                        :style="{
                          position: 'fixed',
                          top: getMenuPosition(inv.id).top + 'px',
                          left: getMenuPosition(inv.id).left + 'px',
                          right: 'auto'
                        }"
                      >
                        <button class="gms-menu-item" @click="() => { toast('Send/update invite feature coming soon'); actionsMenuOpen = null }">
                          <GmsIcon name="mail" :size="16" />
                          Send/update invite
                        </button>
                        <button class="gms-menu-item" @click="() => { toast('Resend email feature coming soon'); actionsMenuOpen = null }">
                          <GmsIcon name="refresh-cw" :size="16" />
                          Resend email
                        </button>
                        <button class="gms-menu-item" @click="() => { acceptOnBehalf(inv); actionsMenuOpen = null }">
                          <GmsIcon name="check-circle" :size="16" />
                          Accept on behalf
                        </button>
                        <div class="gms-menu-sep"></div>
                        <button class="gms-menu-item" @click="() => { markConfirmed(inv); }">
                          <GmsIcon name="badge" :size="16" />
                          Mark confirmed
                        </button>
                        <button class="gms-menu-item" @click="() => { markDeclined(inv); }">
                          <GmsIcon name="x" :size="16" />
                          Mark declined
                        </button>
                        <button class="gms-menu-item" @click="() => { resetToPending(inv); }">
                          <GmsIcon name="refresh-cw" :size="16" />
                          Reset to pending
                        </button>
                      </div>
                    </Teleport>
                  </div>
                </td>
              </tr>
              <tr v-if="!filtered.length">
                <td colspan="7">
                  <div class="gms-empty">
                    <div class="gms-empty-title">No invitations match this filter</div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Services overview table (services view) -->
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
                <th>Invite</th>
                <th>Flight</th>
                <th>Hotel</th>
                <th>Seat</th>
                <th>Transport</th>
                <th>A &amp; D</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in filtered" :key="inv.id">
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <GmsAvatar :name="inv.guest_name" size="sm" />
                    <span style="font-weight:600;">{{ inv.guest_name }}</span>
                  </div>
                </td>
                <td><span class="gms-muted gms-small">{{ inv.guest_group }}</span></td>
                <td>
                  <div style="display:flex;flex-direction:column;gap:4px;">
                    <!-- Response strip dots -->
                    <div style="display:flex;align-items:center;gap:3px;">
                      <div
                        v-for="(match, idx) in inv.matches"
                        :key="idx"
                        :style="{
                          width: '7px',
                          height: '7px',
                          borderRadius: '50%',
                          background: match.response === 'yes' ? 'var(--good)' : match.response === 'no' ? 'var(--bad)' : '#d1d5db',
                        }"
                        :title="match.response === 'yes' ? 'Accepted' : match.response === 'no' ? 'Declined' : 'Pending'"
                      ></div>
                    </div>
                    <!-- Rollup status pill -->
                    <GmsPill :value="getRollupStatus(inv)" />
                  </div>
                </td>
                <td>
                  <GmsPill v-if="inv.services?.flight" :value="inv.services.flight" />
                  <span v-else class="gms-muted">—</span>
                </td>
                <td>
                  <GmsPill v-if="inv.services?.accommodation" :value="inv.services.accommodation" />
                  <span v-else class="gms-muted">—</span>
                </td>
                <td>
                  <GmsPill v-if="inv.services?.seat" :value="inv.services.seat" />
                  <span v-else class="gms-muted">—</span>
                </td>
                <td>
                  <GmsPill v-if="inv.services?.transport" :value="inv.services.transport" />
                  <span v-else class="gms-muted">—</span>
                </td>
                <td>
                  <GmsPill v-if="inv.services?.ad" :value="inv.services.ad" />
                  <span v-else class="gms-muted">—</span>
                </td>
              </tr>
              <tr v-if="!filtered.length">
                <td colspan="8">
                  <div class="gms-empty">
                    <div class="gms-empty-title">No confirmed invitations</div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    </div>
  </div>

  <!-- Invitation Detail Drawer -->
  <GmsDrawer 
    :open="detailDrawer" 
    :title="activeInvitation?.guest_name || 'Invitation Details'"
    :subtitle="activeInvitation?.guest_email"
    @close="detailDrawer = false"
  >
    <div v-if="activeInvitation" style="display:flex;flex-direction:column;gap:20px;">
      
      <!-- Matches Summary with Status -->
      <div v-if="!showFullInvitation && activeInvitation.matches && activeInvitation.matches.length > 0">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
          <div class="gms-section-title">Matches & Responses</div>
          <button 
            class="gms-btn gms-btn-ghost gms-btn-sm" 
            @click="showFullInvitation = true"
            style="font-size:11px;"
          >
            <GmsIcon name="eye" :size="12" />
            View full invitation
          </button>
        </div>
        
        <div style="display:flex;flex-direction:column;gap:8px;">
          <div 
            v-for="match in activeInvitation.matches" 
            :key="match.id"
            class="gms-card"
            style="padding:12px;cursor:pointer;transition:all 0.15s;border:1px solid var(--gms-border);"
            @click="showFullInvitation = true"
            @mouseenter="$event.currentTarget.style.borderColor = 'var(--gms-maroon)'"
            @mouseleave="$event.currentTarget.style.borderColor = 'var(--gms-border)'"
          >
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
              <span class="gms-pill" style="background:var(--gms-maroon-tint);color:var(--gms-maroon);font-size:10px;font-weight:700;text-transform:uppercase;">
                {{ match.stage }}
              </span>
              <span v-if="match.response === 'yes'" class="gms-pill" style="background:var(--good-soft);color:var(--good);font-size:10px;font-weight:600;">
                ✓ Accepted
              </span>
              <span v-else-if="match.response === 'no'" class="gms-pill" style="background:var(--bad-soft);color:var(--bad);font-size:10px;font-weight:600;">
                ✕ Declined
              </span>
              <span v-else class="gms-pill" style="background:#f3f4f6;color:#6b7280;font-size:10px;">
                ⊙ Pending
              </span>
            </div>
            
            <div style="font-weight:600;font-size:14px;margin-bottom:4px;color:var(--gms-text);">
              {{ match.homeTeam }} vs {{ match.awayTeam }}
            </div>
            
            <div style="font-size:12px;color:var(--gms-text-2);">
              {{ match.venue }} • {{ match.date }} • {{ match.time }}
            </div>
            
            <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--gms-border);font-size:10.5px;color:var(--gms-text-3);display:flex;align-items:center;justify-content:space-between;">
              <span>Click to view complete invitation</span>
              <GmsIcon name="arrow-right" :size="12" style="opacity:0.5;" />
            </div>
          </div>
        </div>
      </div>
      
      <!-- Empty state for no matches -->
      <div v-if="!showFullInvitation && (!activeInvitation.matches || activeInvitation.matches.length === 0)" class="gms-empty" style="padding:40px 0;">
        <div class="gms-empty-title">No matches offered</div>
        <div class="gms-empty-sub">This invitation has no matches.</div>
        <button 
          class="gms-btn gms-btn-ghost gms-btn-sm" 
          @click="showFullInvitation = true"
          style="margin-top:12px;font-size:12px;"
        >
          <GmsIcon name="eye" :size="12" />
          View full invitation
        </button>
      </div>
      
      <!-- Full Invitation Details (toggle view) -->
      <div v-if="showFullInvitation" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
        <div class="gms-section-title">Complete Invitation</div>
        <button 
          class="gms-btn gms-btn-ghost gms-btn-sm" 
          @click="showFullInvitation = false"
          style="font-size:11px;"
        >
          <GmsIcon name="chevron-left" :size="12" />
          Back to matches
        </button>
      </div>
      
      <!-- Status & Dates -->
      <div v-if="showFullInvitation">
        <div class="gms-detail-row">
          <span class="gms-detail-label">Overall Status</span>
          <GmsPill :value="getRollupStatus(activeInvitation)" />
        </div>
        <div class="gms-detail-row">
          <span class="gms-detail-label">Response Summary</span>
          <span class="gms-detail-value">{{ getResponseSummary(activeInvitation) || 'No responses yet' }}</span>
        </div>
        <div class="gms-detail-row">
          <span class="gms-detail-label">Service Level</span>
          <span
            class="gms-pill"
            :style="{
              background: tiers.find(t=>t.id===activeInvitation.guest_tier)?.bg ?? '#f3f4f6',
              color: tiers.find(t=>t.id===activeInvitation.guest_tier)?.color ?? '#374151',
              fontSize:'10.5px'
            }"
          >{{ tierLabel(activeInvitation.guest_tier) }}</span>
        </div>
        <div class="gms-detail-row">
          <span class="gms-detail-label">Group</span>
          <span class="gms-detail-value">{{ activeInvitation.guest_group || '—' }}</span>
        </div>
        <div class="gms-detail-row">
          <span class="gms-detail-label">Sent</span>
          <span class="gms-detail-value">{{ formatInvitationDate(activeInvitation.sent_at) }}</span>
        </div>
        <div class="gms-detail-row">
          <span class="gms-detail-label">Responded</span>
          <span class="gms-detail-value">{{ activeInvitation.responded_at ? formatInvitationDate(activeInvitation.responded_at) : 'Pending' }}</span>
        </div>
      </div>

      <!-- Subject -->
      <div v-if="showFullInvitation">
        <div class="gms-section-title" style="margin-bottom:8px;">Subject</div>
        <div style="padding:10px 12px;background:var(--gms-surface);border:1px solid var(--gms-border);border-radius:8px;font-size:13px;">
          {{ activeInvitation.subject }}
        </div>
      </div>

      <!-- Body -->
      <div v-if="showFullInvitation">
        <div class="gms-section-title" style="margin-bottom:8px;">Message</div>
        <div style="padding:12px;background:var(--gms-surface);border:1px solid var(--gms-border);border-radius:8px;font-size:13px;line-height:1.6;white-space:pre-wrap;">
          {{ activeInvitation.body }}
        </div>
      </div>

      <!-- Matches Ledger -->
      <div v-if="showFullInvitation">
        <div class="gms-section-title" style="margin-bottom:8px;">Per-Match Responses</div>
        <div style="display:flex;flex-direction:column;gap:6px;">
          <div 
            v-for="match in activeInvitation.matches" 
            :key="match.id"
            style="padding:12px;background:var(--gms-surface);border:1px solid var(--gms-border);border-radius:8px;"
          >
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
              <!-- Match info -->
              <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                  <span class="gms-pill" style="background:var(--gms-maroon-tint);color:var(--gms-maroon);font-size:10px;font-weight:700;text-transform:uppercase;">
                    {{ match.stage }}
                  </span>
                  <span style="font-weight:600;font-size:13px;">
                    {{ match.homeTeam }} vs {{ match.awayTeam }}
                  </span>
                </div>
                <div style="font-size:11.5px;color:var(--gms-text-3);">
                  {{ match.date }} · {{ match.venue }} · {{ match.time }}
                </div>
              </div>
              
              <!-- Response & Consequence -->
              <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;min-width:140px;">
                <span v-if="match.response === 'yes'" class="gms-pill" style="background:var(--good-soft);color:var(--good);font-size:10px;font-weight:600;">
                  ✓ Accepted
                </span>
                <span v-else-if="match.response === 'no'" class="gms-pill" style="background:var(--bad-soft);color:var(--bad);font-size:10px;font-weight:600;">
                  ✕ Declined
                </span>
                <span v-else class="gms-pill" style="background:#f3f4f6;color:#6b7280;font-size:10px;">
                  ⊙ Pending
                </span>
                
                <!-- Consequence -->
                <div style="font-size:10.5px;color:var(--gms-text-3);text-align:right;">
                  <span v-if="match.response === 'yes'" style="color:var(--good);">
                    → Seat to be assigned
                  </span>
                  <span v-else-if="match.response === 'no'" style="color:var(--gms-text-3);">
                    → Seat released
                  </span>
                  <span v-else style="color:var(--gms-text-3);">
                    → Awaiting response
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="detailDrawer = false">Close</button>
      <button 
        class="gms-btn gms-btn-primary"
        @click="openRsvpPage"
      >
        <GmsIcon name="arrow-right" :size="13" />
        View RSVP Page
      </button>
    </template>
  </GmsDrawer>

  <!-- Accept on behalf confirmation modal -->
  <GmsConfirmModal
    :open="acceptModal"
    title="Accept Invitation on Behalf"
    :message="acceptingInvitation ? `Are you sure you want to accept this invitation on behalf of <strong style='color: var(--gms-text);'>${acceptingInvitation.guest_name}</strong>?` : ''"
    description="This action will:"
    :details="[
      'Mark the invitation as <strong style=\'color: var(--good);\'>confirmed</strong>',
      `Accept <strong>all ${acceptingInvitation?.matches?.length || 0} matches</strong> offered in this invitation`,
      'Update the guest status to <strong style=\'color: var(--good);\'>confirmed</strong>'
    ]"
    confirm-text="Accept on Behalf"
    confirm-icon="check-circle"
    @confirm="confirmAcceptOnBehalf"
    @close="acceptModal = false; acceptingInvitation = null"
  >
    <p style="font-size: 13px; color: var(--gms-text-3); margin: 0;">
      The guest will not receive a notification about this action.
    </p>
  </GmsConfirmModal>

</template>
