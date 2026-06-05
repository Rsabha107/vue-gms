<script setup>
import { ref, computed, inject } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsPill from '@/Components/Gms/GmsPill.vue'
import GmsDrawer from '@/Components/Gms/GmsDrawer.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'
import GmsMiniStat from '@/Components/Gms/GmsMiniStat.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    requests: { type: Array,  default: () => [] },
    guests:   { type: Array,  default: () => [] },
    tiers:    { type: Array,  default: () => [] },
    event:    { type: Object, default: () => ({}) },
})

const toast = inject('toast')

// ── Local data ────────────────────────────────────────────────────
const localReqs = ref(props.requests.map(r => ({ ...r })))

// ── Filters ───────────────────────────────────────────────────────
const search       = ref('')
const statusFilter = ref('all')
const statuses     = ['all', 'confirmed', 'pending', 'cancelled']

function countFor(s) {
    if (s === 'all') return localReqs.value.length
    if (s === 'changes') return localReqs.value.filter(r => r.changeRequest).length
    return localReqs.value.filter(r => r.status === s).length
}

const filtered = computed(() => {
    let list = localReqs.value
    if (statusFilter.value !== 'all') list = list.filter(r => r.status === statusFilter.value)
    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(r =>
            r.guestName.toLowerCase().includes(q) ||
            r.flightNo.toLowerCase().includes(q) ||
            r.route.toLowerCase().includes(q)
        )
    }
    return list
})

// ── Drawer ────────────────────────────────────────────────────────
const drawerOpen  = ref(false)
const activeReq   = ref(null)

function openDrawer(r) { activeReq.value = r; drawerOpen.value = true }

// ── Status change ─────────────────────────────────────────────────
function changeStatus(id, status) {
    const idx = localReqs.value.findIndex(r => r.id === id)
    if (idx !== -1) localReqs.value[idx] = { ...localReqs.value[idx], status }
    router.patch(route('gms.flights.status', id), { status }, {
        onSuccess: () => toast(`Status updated to ${status}.`),
        onError:   () => toast('Failed to update.', 'error'),
        preserveScroll: true,
    })
}

// ── New flight modal ──────────────────────────────────────────────
const newModal = ref(false)
const form = useForm({
    guestId:  '',
    flightNo: '',
    route:    '',
    origin:   '',
    destination: '',
    class:    'Business',
    pax:      1,
    date:     '',
    time:     '',
    notes:    '',
})

function saveNew() {
    const guest = props.guests.find(g => g.id === form.guestId)
    form.post(route('gms.flights.store'), {
        onSuccess: () => {
            const fake = {
                id: 'FLT-' + Date.now(),
                ...form.data(),
                guestName: guest?.name ?? form.guestId,
                status: 'pending',
                arrival: form.date,
                arrivalTime: '',
            }
            localReqs.value.unshift(fake)
            newModal.value = false
            toast('Flight request created.')
            form.reset()
        },
        onError: () => toast('Failed to create.', 'error'),
        preserveScroll: true,
    })
}

// ── Delete ────────────────────────────────────────────────────────
function deleteReq(id) {
    localReqs.value = localReqs.value.filter(r => r.id !== id)
    if (activeReq.value?.id === id) drawerOpen.value = false
    router.delete(route('gms.flights.destroy', id), {
        onSuccess: () => toast('Request deleted.'),
        preserveScroll: true,
    })
}

const statusColors = {
    confirmed: { bg: '#dcfce7', fg: '#15803d' },
    pending:   { bg: '#fef9c3', fg: '#a16207' },
    cancelled: { bg: '#f3f4f6', fg: '#6b7280' },
}
</script>

<template>
  <div class="gms-view">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Flights</h1>
        <p class="gms-view-subtitle">{{ localReqs.length }} flight requests</p>
      </div>
      <div class="gms-view-actions">
        <button class="gms-btn gms-btn-primary" @click="newModal = true">
          <GmsIcon name="plus" :size="14" /> New Request
        </button>
      </div>
    </div>

    <!-- Stats strip -->
    <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
      <GmsMiniStat label="Confirmed" :value="countFor('confirmed')" color="#15803d" style="flex:1;min-width:120px;" />
      <GmsMiniStat label="Pending" :value="countFor('pending')" color="#a16207" style="flex:1;min-width:120px;" />
      <GmsMiniStat label="Cancelled" :value="countFor('cancelled')" color="#6b7280" style="flex:1;min-width:120px;" />
      <GmsMiniStat label="Change requests" :value="countFor('changes')" color="#2563eb" style="flex:1;min-width:120px;" />
    </div>

    <!-- Toolbar -->
    <div class="gms-toolbar">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search guest, flight no…" />
      </div>
      <div class="gms-seg">
        <button v-for="s in statuses" :key="s" :class="{ on: statusFilter===s }" @click="statusFilter=s">
          {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
          <span class="gms-seg-count">{{ countFor(s) }}</span>
        </button>
      </div>
      <span class="mxt-count" style="margin-left: auto;">{{ filtered.length }} of {{ localReqs.length }}</span>
    </div>

    <!-- Table -->
    <div class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-table-wrap">
          <table class="gms-table">
            <thead>
              <tr>
                <th>Guest</th>
                <th>Flight</th>
                <th>Route</th>
                <th>Class</th>
                <th>Pax</th>
                <th>Date</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in filtered" :key="r.id" @click="openDrawer(r)">
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <GmsAvatar :name="r.guestName" size="sm" />
                    <span style="font-weight:600;font-size:13px;">{{ r.guestName }}</span>
                  </div>
                </td>
                <td><span class="gms-mono gms-small" style="font-weight:600;">{{ r.flightNo }}</span></td>
                <td><span class="gms-small">{{ r.route }}</span></td>
                <td><span class="gms-small gms-muted">{{ r.class }}</span></td>
                <td><span class="gms-small">{{ r.pax }}</span></td>
                <td><span class="gms-small gms-mono">{{ r.date }}</span></td>
                <td>
                  <GmsPill
                    type="custom" :value="r.status"
                    :bg="statusColors[r.status]?.bg ?? '#f3f4f6'"
                    :fg="statusColors[r.status]?.fg ?? '#374151'"
                  />
                </td>
                <td @click.stop>
                  <div class="gms-table-actions">
                    <button class="gms-btn gms-btn-danger gms-btn-sm gms-btn-icon" @click="deleteReq(r.id)">
                      <GmsIcon name="trash" :size="13" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!filtered.length">
                <td colspan="8">
                  <div class="gms-empty">
                    <div class="gms-empty-title">No flight requests</div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Detail drawer -->
  <GmsDrawer :open="drawerOpen" :title="activeReq?.flightNo ?? ''" :subtitle="activeReq?.route" @close="drawerOpen = false">
    <template v-if="activeReq">
      <div style="display:flex;gap:8px;margin-bottom:18px;">
        <GmsPill type="custom" :value="activeReq.status" :bg="statusColors[activeReq.status]?.bg" :fg="statusColors[activeReq.status]?.fg" />
      </div>

      <div class="gms-section-title">Guest</div>
      <div class="gms-detail-row"><span class="gms-detail-label">Name</span><span class="gms-detail-value">{{ activeReq.guestName }}</span></div>

      <div class="gms-section-title" style="margin-top:16px;">Flight Details</div>
      <div class="gms-detail-row"><span class="gms-detail-label">Flight No.</span><span class="gms-detail-value gms-mono">{{ activeReq.flightNo }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Route</span><span class="gms-detail-value">{{ activeReq.route }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Class</span><span class="gms-detail-value">{{ activeReq.class }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Passengers</span><span class="gms-detail-value">{{ activeReq.pax }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Departure</span><span class="gms-detail-value gms-mono">{{ activeReq.date }} {{ activeReq.time }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Arrival</span><span class="gms-detail-value gms-mono">{{ activeReq.arrival }} {{ activeReq.arrivalTime }}</span></div>
      <div v-if="activeReq.notes" class="gms-detail-row"><span class="gms-detail-label">Notes</span><span class="gms-detail-value">{{ activeReq.notes }}</span></div>
    </template>

    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="changeStatus(activeReq.id, 'confirmed'); drawerOpen=false">
        <GmsIcon name="check" :size="13" /> Confirm
      </button>
      <button class="gms-btn gms-btn-danger gms-btn-sm" @click="changeStatus(activeReq.id, 'cancelled'); drawerOpen=false">Cancel</button>
    </template>
  </GmsDrawer>

  <!-- New flight modal -->
  <GmsModal :open="newModal" title="New Flight Request" @close="newModal = false">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="gms-field">
        <label class="gms-label">Guest</label>
        <select v-model="form.guestId" class="gms-input gms-select">
          <option value="">— Select —</option>
          <option v-for="g in guests" :key="g.id" :value="g.id">{{ g.name }}</option>
        </select>
        <span v-if="form.errors.guestId" class="gms-error">{{ form.errors.guestId }}</span>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">Flight No.</label>
          <input v-model="form.flightNo" class="gms-input" placeholder="QR 2025" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Route</label>
          <input v-model="form.route" class="gms-input" placeholder="CDG → DOH" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Class</label>
          <select v-model="form.class" class="gms-input gms-select">
            <option>First</option><option>Business</option><option>Economy</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Passengers</label>
          <input v-model.number="form.pax" type="number" min="1" class="gms-input" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Date</label>
          <input v-model="form.date" type="date" class="gms-input" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Time</label>
          <input v-model="form.time" type="time" class="gms-input" />
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
