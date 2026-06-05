<script setup>
import { ref, computed, inject } from 'vue'
import { useForm } from '@inertiajs/vue3'
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
const search   = ref('')
const statusFilter = ref('all')
const statuses = ['all', 'invited', 'pending', 'confirmed', 'declined']

const filtered = computed(() => {
    let list = props.guests
    if (statusFilter.value !== 'all') list = list.filter(g => g.status === statusFilter.value)
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
    if (selected.value.size === 0) { toast('Select at least one guest.', 'info'); return }
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

// ── Template editor modal ─────────────────────────────────────────
const tplModal = ref(false)
const localTemplates = ref(props.emailTemplates.map(t => ({ ...t })))
const editingTpl = ref(null)
const tplForm = useForm({ name: '', subject: '', body: '' })

function openTplEdit(tpl) {
    editingTpl.value  = tpl
    tplForm.name    = tpl.name
    tplForm.subject = tpl.subject
    tplForm.body    = tpl.body
    tplModal.value  = true
}
</script>

<template>
  <div class="gms-view">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Invitations</h1>
        <p class="gms-view-subtitle">Manage outreach to registered guests</p>
      </div>
      <div class="gms-view-actions">
        <button
          class="gms-btn gms-btn-primary"
          :disabled="selected.size === 0"
          @click="openSend"
        >
          <GmsIcon name="send" :size="14" />
          Send to Selected
          <span v-if="selected.size" style="background:rgba(255,255,255,0.25);border-radius:999px;padding:1px 7px;font-size:11px;">{{ selected.size }}</span>
        </button>
      </div>
    </div>

    <!-- Status filters -->
    <div class="gms-toolbar">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search guests…" />
      </div>
      <button
        v-for="s in statuses" :key="s"
        class="gms-filter-btn"
        :class="{ active: statusFilter === s }"
        @click="statusFilter = s"
      >
        {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
        <span class="gms-filter-count">{{ countFor(s) }}</span>
      </button>
    </div>

    <!-- Guest table -->
    <div class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-table-wrap">
          <table class="gms-table">
            <thead>
              <tr>
                <th style="width:40px;">
                  <input type="checkbox" :checked="allSelected" @change="toggleAll" style="cursor:pointer;" />
                </th>
                <th>Guest</th>
                <th>Title / Role</th>
                <th>Tier</th>
                <th>Status</th>
                <th>Email</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="g in filtered"
                :key="g.id"
                @click="toggleSelect(g.id)"
                :style="selected.has(g.id) ? 'background:var(--gms-maroon-light)' : ''"
              >
                <td @click.stop>
                  <input type="checkbox" :checked="selected.has(g.id)" @change="toggleSelect(g.id)" style="cursor:pointer;" />
                </td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <GmsAvatar :name="g.name" size="sm" />
                    <span style="font-weight:600;">{{ g.name }}</span>
                  </div>
                </td>
                <td><span class="gms-muted gms-small">{{ g.title }}</span></td>
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
                <td><GmsPill :value="g.status" /></td>
                <td><span class="gms-muted gms-small gms-mono">{{ g.email }}</span></td>
              </tr>
              <tr v-if="!filtered.length">
                <td colspan="6">
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

    <!-- Email templates -->
    <div class="gms-card" style="margin-top:20px;">
      <div class="gms-card-header">
        <span class="gms-card-title">Email Templates</span>
      </div>
      <div class="gms-card-body-0">
        <table class="gms-table">
          <thead>
            <tr>
              <th>Template</th>
              <th>Subject</th>
              <th>Tier</th>
              <th>Last Used</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in localTemplates" :key="t.id">
              <td><span style="font-weight:600;">{{ t.name }}</span></td>
              <td><span class="gms-small gms-truncate" style="max-width:280px;display:block;">{{ t.subject }}</span></td>
              <td>
                <span v-if="t.tier" class="gms-pill" :style="{background:tiers.find(x=>x.id===t.tier)?.bg,color:tiers.find(x=>x.id===t.tier)?.color}">
                  {{ tiers.find(x=>x.id===t.tier)?.name }}
                </span>
                <span v-else class="gms-muted gms-small">All</span>
              </td>
              <td><span class="gms-muted gms-small gms-mono">{{ t.lastUsed ?? '—' }}</span></td>
              <td>
                <div class="gms-table-actions">
                  <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" @click="openTplEdit(t)">
                    <GmsIcon name="edit" :size="13" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Send modal -->
  <GmsModal :open="sendModal" title="Send Invitations" @close="sendModal = false">
    <p style="font-size:13.5px;color:var(--gms-text-2);margin-bottom:16px;">
      Sending to <strong>{{ selected.size }}</strong> guest(s).
    </p>
    <div class="gms-field">
      <label class="gms-label">Email Template</label>
      <select v-model="chosenTplId" class="gms-input gms-select">
        <option v-for="t in emailTemplates" :key="t.id" :value="t.id">{{ t.name }}</option>
      </select>
    </div>
    <div v-if="chosenTplId" style="margin-top:14px;padding:12px;background:var(--gms-surface-2);border-radius:8px;font-size:12.5px;color:var(--gms-text-2);">
      <strong>{{ emailTemplates.find(t=>t.id===chosenTplId)?.subject }}</strong>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="sendModal = false">Cancel</button>
      <button class="gms-btn gms-btn-primary" :disabled="form.processing" @click="sendInvitations">
        <GmsIcon name="send" :size="13" />
        Send Invitations
      </button>
    </template>
  </GmsModal>

  <!-- Template edit modal -->
  <GmsModal :open="tplModal" title="Edit Template" size="lg" @close="tplModal = false">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="gms-field">
        <label class="gms-label">Name</label>
        <input v-model="tplForm.name" class="gms-input" />
      </div>
      <div class="gms-field">
        <label class="gms-label">Subject</label>
        <input v-model="tplForm.subject" class="gms-input" />
      </div>
      <div class="gms-field">
        <label class="gms-label">Body</label>
        <textarea v-model="tplForm.body" class="gms-input gms-textarea" rows="8" />
      </div>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="tplModal = false">Cancel</button>
      <button class="gms-btn gms-btn-primary" @click="tplModal = false">Save Template</button>
    </template>
  </GmsModal>
</template>
