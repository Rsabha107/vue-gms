<script setup>
import { ref, computed, inject } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsPill from '@/Components/Gms/GmsPill.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    guests:         { type: Array,  default: () => [] },
    tiers:          { type: Array,  default: () => [] },
    emailTemplates: { type: Array,  default: () => [] },
    event:          { type: Object, default: () => ({}) },
})

const toast = inject('toast')

// ── Filters ───────────────────────────────────────────────────────
const view     = ref('list') // 'list' | 'services'
const search   = ref('')
const statusFilter = ref('all')
const statuses = ['all', 'invited', 'pending', 'confirmed', 'declined']

const filtered = computed(() => {
    let list = props.guests
    
    // For services view, only show confirmed/accepted guests
    if (view.value === 'services') {
        list = list.filter(g => g.status === 'confirmed' || g.status === 'accepted')
    } else if (statusFilter.value !== 'all') {
        list = list.filter(g => g.status === statusFilter.value)
    }
    
    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(g => g.name.toLowerCase().includes(q) || g.title.toLowerCase().includes(q))
    }
    return list
})

function countFor(s) {
    if (s === 'all') return props.guests.length
    return props.guests.filter(g => g.status === s).length
}

function tierLabel(id) {
    return props.tiers.find(t => t.id === id)?.name ?? id
}

// Stats for list view
const acceptedCount = computed(() => {
    return props.guests.filter(g => g.status === 'confirmed' || g.status === 'accepted').length
})

// ── Selection ─────────────────────────────────────────────────────
const selected = ref(new Set())

function toggleSelect(id) {
    if (selected.value.has(id)) selected.value.delete(id)
    else selected.value.add(id)
    selected.value = new Set(selected.value) // force reactivity
}

function toggleAll() {
    if (selected.value.size === filtered.value.length) {
        selected.value = new Set()
    } else {
        selected.value = new Set(filtered.value.map(g => g.id))
    }
}

const allSelected = computed(() =>
    filtered.value.length > 0 && selected.value.size === filtered.value.length
)

// ── Send modal ────────────────────────────────────────────────────
const sendModal    = ref(false)
const chosenTplId  = ref(props.emailTemplates[0]?.id ?? '')

const form = useForm({ guestIds: [], templateId: '' })

function openSend() {
    // Open invitation wizard/modal
    chosenTplId.value = props.emailTemplates[0]?.id ?? ''
    sendModal.value   = true
}

function sendInvitations() {
    form.guestIds   = [...selected.value]
    form.templateId = chosenTplId.value
    form.post(route('gms.invitations.send'), {
        onSuccess: () => {
            sendModal.value = false
            selected.value  = new Set()
            toast(`${form.guestIds.length} invitation(s) sent.`)
        },
        onError: () => toast('Failed to send.', 'error'),
        preserveScroll: true,
    })
}


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
        <div class="gms-stat-number">{{ props.guests.length }}</div>
        <div class="gms-stat-label">Sent</div>
      </div>
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--good);"></div>
        <div class="gms-stat-number">{{ acceptedCount }}</div>
        <div class="gms-stat-label">Accepted</div>
      </div>
      <div class="gms-stat">
        <div class="gms-stat-strip" style="background: var(--warn);"></div>
        <div class="gms-stat-number">{{ countFor('pending') }}</div>
        <div class="gms-stat-label">Pending</div>
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
                <th>Sessions</th>
                <th>Status</th>
                <th>Sent / Accepted</th>
                <th>Passport</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="g in filtered" :key="g.id">
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
                  <span
                    class="gms-pill"
                    :style="{
                      background: tiers.find(t=>t.id===g.tier)?.bg ?? '#f3f4f6',
                      color: tiers.find(t=>t.id===g.tier)?.color ?? '#374151',
                      fontSize:'10.5px'
                    }"
                  >{{ tierLabel(g.tier) }}</span>
                </td>
                <td>
                  <span style="font-size:12.5px;color:var(--gms-text-2);">—</span>
                </td>
                <td>
                  <GmsPill :value="g.status" />
                </td>
                <td>
                  <div class="gms-mono" style="font-size:11.5px;color:var(--gms-text-3);">
                    <div>—</div>
                    <div></div>
                  </div>
                </td>
                <td>
                  <span class="gms-pill" style="background:var(--warn-soft);color:var(--warn);font-size:10.5px;">Pending</span>
                </td>
                <td>
                  <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
                    <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" title="Edit">
                      <GmsIcon name="edit" :size="14" />
                    </button>
                    <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" title="More">
                      <GmsIcon name="more-vertical" :size="14" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!filtered.length">
                <td colspan="7">
                  <div class="gms-empty">
                    <div class="gms-empty-title">No guests match this filter</div>
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
              <tr v-for="g in filtered" :key="g.id">
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <GmsAvatar :name="g.name" size="sm" />
                    <span style="font-weight:600;">{{ g.name }}</span>
                  </div>
                </td>
                <td><span class="gms-muted gms-small">{{ g.group }}</span></td>
                <td><GmsPill :value="g.status" /></td>
                <td><span class="gms-muted">—</span></td>
                <td><span class="gms-muted">—</span></td>
                <td><span class="gms-muted">—</span></td>
                <td><span class="gms-muted">—</span></td>
                <td><span class="gms-muted">—</span></td>
              </tr>
              <tr v-if="!filtered.length">
                <td colspan="8">
                  <div class="gms-empty">
                    <div class="gms-empty-title">No confirmed guests</div>
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

  <!-- Send modal -->
  <GmsModal :open="sendModal" title="New Invitation" @close="sendModal = false">
    <p style="font-size:13.5px;color:var(--gms-text-2);margin-bottom:16px;">
      Create a new invitation. Full wizard coming soon.
    </p>
    <div class="gms-field">
      <label class="gms-label">Email Template</label>
      <select v-model="chosenTplId" class="gms-input gms-select">
        <option v-for="t in emailTemplates" :key="t.id" :value="t.id">{{ t.name }}</option>
      </select>
      <div style="font-size:11px;color:var(--gms-text-3);margin-top:6px;">
        <Link href="/gms/email-templates" style="color:var(--gms-maroon);text-decoration:none;font-weight:500;">
          Manage templates →
        </Link>
      </div>
    </div>
    <div v-if="chosenTplId" style="margin-top:14px;padding:12px;background:var(--gms-surface-2);border-radius:8px;font-size:12.5px;color:var(--gms-text-2);">
      <strong>{{ emailTemplates.find(t=>t.id===chosenTplId)?.subject }}</strong>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="sendModal = false">Cancel</button>
      <button class="gms-btn gms-btn-primary" :disabled="form.processing" @click="() => { sendModal = false; toast('Invitation wizard coming soon') }">
        <GmsIcon name="send" :size="13" />
        Create Invitation
      </button>
    </template>
  </GmsModal>


</template>
