<script setup>
import { ref, computed, inject } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsPill from '@/Components/Gms/GmsPill.vue'
import GmsDrawer from '@/Components/Gms/GmsDrawer.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    requests: { type: Array,  default: () => [] },
    guests:   { type: Array,  default: () => [] },
    tiers:    { type: Array,  default: () => [] },
    event:    { type: Object, default: () => ({}) },
})

const toast = inject('toast')
const localReqs = ref(props.requests.map(r => ({ ...r })))
const search       = ref('')
const statusFilter = ref('all')
const statuses     = ['all', 'confirmed', 'pending', 'cancelled']
const statusColors = {
    confirmed: { bg: '#dcfce7', fg: '#15803d' },
    pending:   { bg: '#fef9c3', fg: '#a16207' },
    cancelled: { bg: '#f3f4f6', fg: '#6b7280' },
}

const filtered = computed(() => {
    let list = localReqs.value
    if (statusFilter.value !== 'all') list = list.filter(r => r.status === statusFilter.value)
    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(r => r.guestName.toLowerCase().includes(q) || r.type.toLowerCase().includes(q) || r.vehicle.toLowerCase().includes(q))
    }
    return list
})

function countFor(s) {
    if (s === 'all') return localReqs.value.length
    return localReqs.value.filter(r => r.status === s).length
}

const drawerOpen = ref(false)
const activeReq  = ref(null)
function openDrawer(r) { activeReq.value = r; drawerOpen.value = true }

function changeStatus(id, status) {
    const idx = localReqs.value.findIndex(r => r.id === id)
    if (idx !== -1) localReqs.value[idx] = { ...localReqs.value[idx], status }
    router.patch(route('gms.transport.status', id), { status }, { preserveScroll: true })
    toast(`Status updated to ${status}.`)
}

function deleteReq(id) {
    localReqs.value = localReqs.value.filter(r => r.id !== id)
    if (activeReq.value?.id === id) drawerOpen.value = false
    router.delete(route('gms.transport.destroy', id), { preserveScroll: true })
    toast('Request deleted.')
}

const newModal = ref(false)
const form = useForm({ guestId:'', type:'VIP Transfer', vehicle:'', pickupLocation:'', dropoffLocation:'', datetime:'', notes:'' })

function saveNew() {
    const guest = props.guests.find(g => g.id === form.guestId)
    form.post(route('gms.transport.store'), {
        onSuccess: () => {
            localReqs.value.unshift({ id:'TRN-'+Date.now(), ...form.data(), guestName: guest?.name ?? '', driver:'TBD', status:'pending' })
            newModal.value = false; form.reset(); toast('Transport request created.')
        },
        onError: () => toast('Failed.','error'),
        preserveScroll: true,
    })
}
</script>

<template>
  <div class="gms-view">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Transport</h1>
        <p class="gms-view-subtitle">{{ localReqs.length }} transfer requests</p>
      </div>
      <div class="gms-view-actions">
        <button class="gms-btn gms-btn-primary" @click="newModal = true">
          <GmsIcon name="plus" :size="14" /> New Request
        </button>
      </div>
    </div>

    <div class="gms-toolbar">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search guest, vehicle…" />
      </div>
      <button v-for="s in statuses" :key="s" class="gms-filter-btn" :class="{ active: statusFilter===s }" @click="statusFilter=s">
        {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
        <span class="gms-filter-count">{{ countFor(s) }}</span>
      </button>
    </div>

    <div class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-table-wrap">
          <table class="gms-table">
            <thead>
              <tr><th>Guest</th><th>Type</th><th>Vehicle</th><th>From</th><th>To</th><th>Date/Time</th><th>Driver</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="r in filtered" :key="r.id" @click="openDrawer(r)">
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <GmsAvatar :name="r.guestName" size="sm" />
                    <span style="font-weight:600;font-size:13px;">{{ r.guestName }}</span>
                  </div>
                </td>
                <td><span class="gms-small">{{ r.type }}</span></td>
                <td><span class="gms-small gms-muted">{{ r.vehicle }}</span></td>
                <td><span class="gms-small gms-truncate" style="max-width:120px;display:block;">{{ r.pickupLocation }}</span></td>
                <td><span class="gms-small gms-truncate" style="max-width:120px;display:block;">{{ r.dropoffLocation }}</span></td>
                <td><span class="gms-small gms-mono">{{ r.datetime }}</span></td>
                <td><span class="gms-small gms-muted">{{ r.driver }}</span></td>
                <td><GmsPill type="custom" :value="r.status" :bg="statusColors[r.status]?.bg ?? '#f3f4f6'" :fg="statusColors[r.status]?.fg ?? '#374151'" /></td>
                <td @click.stop>
                  <button class="gms-btn gms-btn-danger gms-btn-sm gms-btn-icon" @click="deleteReq(r.id)"><GmsIcon name="trash" :size="13" /></button>
                </td>
              </tr>
              <tr v-if="!filtered.length"><td colspan="9"><div class="gms-empty"><div class="gms-empty-title">No requests</div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <GmsDrawer :open="drawerOpen" :title="activeReq?.type ?? ''" :subtitle="activeReq?.guestName" @close="drawerOpen = false">
    <template v-if="activeReq">
      <div style="display:flex;gap:8px;margin-bottom:16px;">
        <GmsPill type="custom" :value="activeReq.status" :bg="statusColors[activeReq.status]?.bg" :fg="statusColors[activeReq.status]?.fg" />
      </div>
      <div class="gms-section-title">Transfer Details</div>
      <div class="gms-detail-row"><span class="gms-detail-label">Guest</span><span class="gms-detail-value">{{ activeReq.guestName }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Type</span><span class="gms-detail-value">{{ activeReq.type }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Vehicle</span><span class="gms-detail-value">{{ activeReq.vehicle }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Pick-up</span><span class="gms-detail-value">{{ activeReq.pickupLocation }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Drop-off</span><span class="gms-detail-value">{{ activeReq.dropoffLocation }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Date/Time</span><span class="gms-detail-value gms-mono">{{ activeReq.datetime }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Driver</span><span class="gms-detail-value">{{ activeReq.driver }}</span></div>
      <div v-if="activeReq.notes" class="gms-detail-row"><span class="gms-detail-label">Notes</span><span class="gms-detail-value">{{ activeReq.notes }}</span></div>
    </template>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="changeStatus(activeReq.id,'confirmed'); drawerOpen=false">
        <GmsIcon name="check" :size="13" /> Confirm
      </button>
      <button class="gms-btn gms-btn-danger gms-btn-sm" @click="changeStatus(activeReq.id,'cancelled'); drawerOpen=false">Cancel</button>
    </template>
  </GmsDrawer>

  <GmsModal :open="newModal" title="New Transport Request" @close="newModal = false">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="gms-field">
        <label class="gms-label">Guest</label>
        <select v-model="form.guestId" class="gms-input gms-select">
          <option value="">— Select —</option>
          <option v-for="g in guests" :key="g.id" :value="g.id">{{ g.name }}</option>
        </select>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Transfer Type</label>
          <select v-model="form.type" class="gms-input gms-select">
            <option>VIP Transfer</option><option>VIP Escort</option><option>Motorcade</option><option>Private Car</option><option>Royal Convoy</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Vehicle</label>
          <input v-model="form.vehicle" class="gms-input" placeholder="Mercedes S-Class" />
        </div>
        <div class="gms-field gms-form-full">
          <label class="gms-label">Pick-up Location</label>
          <input v-model="form.pickupLocation" class="gms-input" placeholder="HIA Terminal 1" />
        </div>
        <div class="gms-field gms-form-full">
          <label class="gms-label">Drop-off Location</label>
          <input v-model="form.dropoffLocation" class="gms-input" placeholder="Four Seasons Doha" />
        </div>
        <div class="gms-field gms-form-full">
          <label class="gms-label">Date &amp; Time</label>
          <input v-model="form.datetime" type="datetime-local" class="gms-input" />
        </div>
      </div>
      <div class="gms-field">
        <label class="gms-label">Notes</label>
        <textarea v-model="form.notes" class="gms-input gms-textarea" rows="2" />
      </div>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="newModal = false">Cancel</button>
      <button class="gms-btn gms-btn-primary" :disabled="form.processing" @click="saveNew">Create Request</button>
    </template>
  </GmsModal>
</template>
