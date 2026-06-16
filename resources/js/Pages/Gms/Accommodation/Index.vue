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
import GmsBtn from '@/Components/Gms/GmsBtn.vue'

defineOptions({ layout: GmsLayout })

const MONTHS = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

const props = defineProps({
    requests: { type: Array,  default: () => [] },
    guests:   { type: Array,  default: () => [] },
    hotels:   { type: Array,  default: () => [] },
    tiers:    { type: Array,  default: () => [] },
    event:    { type: Object, default: () => ({}) },
})

const toast = inject('toast')
const localReqs = ref(props.requests.map(r => ({ ...r })))
const search       = ref('')
const statusFilter = ref('all')

const statuses = ['all', 'new', 'change', 'confirmed', 'cancelled']
const statusColors = {
    new:       { bg: '#fef9c3', fg: '#a16207' },
    change:    { bg: '#e2edf3', fg: '#3a6a8a' },
    confirmed: { bg: '#e6f0e7', fg: '#3f7d52' },
    cancelled: { bg: '#f3f4f6', fg: '#6b7280' },
}

const filtered = computed(() => {
    let list = localReqs.value
    if (statusFilter.value !== 'all') list = list.filter(r => r.status === statusFilter.value)
    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(r => r.guestName.toLowerCase().includes(q) || r.hotelName.toLowerCase().includes(q))
    }
    return list
})

function countFor(s) {
    if (s === 'all') return localReqs.value.length
    return localReqs.value.filter(r => r.status === s).length
}

function fmtDateShort(d) {
    if (!d) return '—'
    const parts = d.split('-')
    const year = parts[0].slice(-2)  // Last 2 digits
    return `${parseInt(parts[2])} ${MONTHS[parseInt(parts[1])]} ${year}`
}

const drawerOpen = ref(false)
const activeReq  = ref(null)
const confirmDelete = ref(false)

function openDrawer(r) { 
    activeReq.value = r
    drawerOpen.value = true
    confirmDelete.value = false
}

function changeStatus(id, status) {
    const idx = localReqs.value.findIndex(r => r.id === id)
    if (idx !== -1) localReqs.value[idx] = { ...localReqs.value[idx], status }
    router.patch(route('gms.accommodation.status', id), { status }, {
        onSuccess: () => toast(`Status updated to ${status}.`),
        preserveScroll: true,
    })
}

function deleteReq(r) {
    if (!confirmDelete.value) {
        confirmDelete.value = true
        setTimeout(() => { confirmDelete.value = false }, 3000)
        return
    }
    const id = r.id
    localReqs.value = localReqs.value.filter(req => req.id !== id)
    drawerOpen.value = false
    router.delete(route('gms.accommodation.destroy', id), { preserveScroll: true })
    toast('Request deleted.')
    confirmDelete.value = false
}

const newModal = ref(false)
const editModal = ref(false)
const editingReq = ref(null)
const form = useForm({ guestId: '', hotel: '', roomType: '', checkIn: '', checkOut: '', notes: '' })

function nightsBetween(a, b) {
    if (!a || !b) return 0
    return Math.round((new Date(b) - new Date(a)) / 86400000)
}

function openEdit(r) {
    editingReq.value = r
    form.guestId = r.guestId
    form.hotel = r.hotel
    form.roomType = r.roomType
    form.checkIn = r.checkIn
    form.checkOut = r.checkOut
    form.notes = r.notes || ''
    editModal.value = true
    drawerOpen.value = false
}

function saveNew() {
    const guest = props.guests.find(g => g.id === form.guestId)
    const hotel = props.hotels.find(h => h.id === form.hotel)
    form.post(route('gms.accommodation.store'), {
        onSuccess: () => {
            localReqs.value.unshift({
                id: 'ACC-' + Date.now(),
                ...form.data(),
                guestName: guest?.name ?? '',
                hotelName: hotel?.name ?? '',
                nights: nightsBetween(form.checkIn, form.checkOut),
                status: 'new',
            })
            newModal.value = false
            form.reset()
            toast('Accommodation request created.')
        },
        onError: () => toast('Failed.', 'error'),
        preserveScroll: true,
    })
}

function saveEdit() {
    const guest = props.guests.find(g => g.id === form.guestId)
    const hotel = props.hotels.find(h => h.id === form.hotel)
    form.patch(route('gms.accommodation.update', editingReq.value.id), {
        onSuccess: () => {
            const idx = localReqs.value.findIndex(r => r.id === editingReq.value.id)
            if (idx !== -1) {
                localReqs.value[idx] = {
                    ...localReqs.value[idx],
                    ...form.data(),
                    guestName: guest?.name ?? '',
                    hotelName: hotel?.name ?? '',
                    nights: nightsBetween(form.checkIn, form.checkOut),
                }
            }
            editModal.value = false
            form.reset()
            editingReq.value = null
            toast('Accommodation request updated.')
        },
        onError: () => toast('Failed to update.', 'error'),
        preserveScroll: true,
    })
}
</script>

<template>
  <div class="gms-view">
    <div class="gms-view-pad">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Accommodation</h1>
        <p class="gms-view-subtitle">Hotel bookings &amp; room nights for guests of {{ event?.name ?? 'this event' }}.</p>
      </div>
      <div class="gms-view-actions">
        <button class="gms-btn gms-btn-primary" @click="newModal = true">
          <GmsIcon name="plus" :size="14" /> New Request
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="gms-stats" style="margin-bottom:24px;">
      <GmsMiniStat :value="countFor('new')"        label="New requests"     color="#a9772a" />
      <GmsMiniStat :value="countFor('change')"     label="Change requests"  color="#3a6a8a" />
      <GmsMiniStat :value="countFor('confirmed')"  label="Confirmed"        color="#3f7d52" />
      <GmsMiniStat :value="countFor('cancelled')"  label="Cancelled" />
    </div>

    <div class="gms-toolbar">
      <div class="gms-search-wrap">
        <GmsIcon name="search" :size="14" class="gms-search-icon" />
        <input v-model="search" class="gms-search-input" placeholder="Search guest or hotel…" />
      </div>
      <div class="gms-seg">
        <button v-for="s in statuses" :key="s" :class="{ on: statusFilter===s }" @click="statusFilter=s">
          {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
          <span class="gms-seg-count" v-if="countFor(s) > 0">{{ countFor(s) }}</span>
        </button>
      </div>
      
      <span class="mxt-count" style="margin-left: auto;">{{ filtered.length }} of {{ localReqs.length }}</span>
    </div>

    <div class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-table-wrap">
          <table class="gms-table">
            <thead>
              <tr>
                <th>Guest</th>
                <th>Hotel</th>
                <th>Room Type</th>
                <th>Stay</th>
                <th>Nights</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in filtered" :key="r.id" @click="openDrawer(r)" style="cursor:pointer;">
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <GmsAvatar :name="r.guestName" size="sm" />
                    <span style="font-weight:600;font-size:13px;">{{ r.guestName }}</span>
                  </div>
                </td>
                <td><span class="gms-small">{{ r.hotelName }}</span></td>
                <td><span class="gms-small gms-muted">{{ r.roomType }}</span></td>
                <td><span class="gms-small gms-muted" style="white-space:nowrap;">{{ fmtDateShort(r.checkIn) }} → {{ fmtDateShort(r.checkOut) }}</span></td>
                <td><span class="gms-small">{{ r.nights }}</span></td>
                <td>
                  <GmsPill type="custom" :value="r.status" :bg="statusColors[r.status]?.bg ?? '#f3f4f6'" :fg="statusColors[r.status]?.fg ?? '#374151'" />
                </td>
                <td @click.stop>
                  <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                    <button class="flt-chevron-btn" title="View details" @click="openDrawer(r)">
                      <GmsIcon name="chevron-right" :size="15" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!filtered.length"><td colspan="7"><div class="gms-empty"><div class="gms-empty-title">No requests</div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    </div>
  </div>

  <GmsDrawer :open="drawerOpen" :title="activeReq?.hotelName ?? ''" :subtitle="activeReq?.roomType" @close="drawerOpen = false">
    <template v-if="activeReq">
      <div style="display:flex;gap:8px;margin-bottom:16px;">
        <GmsPill type="custom" :value="activeReq.status" :bg="statusColors[activeReq.status]?.bg" :fg="statusColors[activeReq.status]?.fg" />
      </div>
      <div class="gms-section-title">Booking Details</div>
      <div class="gms-detail-row"><span class="gms-detail-label">Guest</span><span class="gms-detail-value">{{ activeReq.guestName }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Hotel</span><span class="gms-detail-value">{{ activeReq.hotelName }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Room</span><span class="gms-detail-value">{{ activeReq.roomType }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Check-in</span><span class="gms-detail-value gms-mono">{{ fmtDateShort(activeReq.checkIn) }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Check-out</span><span class="gms-detail-value gms-mono">{{ fmtDateShort(activeReq.checkOut) }}</span></div>
      <div class="gms-detail-row"><span class="gms-detail-label">Nights</span><span class="gms-detail-value">{{ activeReq.nights }}</span></div>
      <div v-if="activeReq.notes" class="gms-detail-row"><span class="gms-detail-label">Notes</span><span class="gms-detail-value">{{ activeReq.notes }}</span></div>
    </template>
    <template #footer>
      <GmsBtn variant="ghost" icon="user" :iconSize="13">Guest profile</GmsBtn>
      <GmsBtn icon="edit" :iconSize="13" style="background:var(--gms-surface);border-color:var(--gms-border);" @click="openEdit(activeReq)">Edit</GmsBtn>
      <div style="margin-left:auto;display:flex;gap:8px;">
        <GmsBtn 
          variant="danger" 
          icon="trash-2" 
          :iconSize="13" 
          @click="deleteReq(activeReq)"
        >
          {{ confirmDelete ? 'Confirm delete?' : 'Delete' }}
        </GmsBtn>
        <GmsBtn variant="ghost" icon="x" :iconSize="13" @click="changeStatus(activeReq.id,'cancelled'); drawerOpen=false">Cancel</GmsBtn>
        <GmsBtn v-if="activeReq.status !== 'confirmed'" variant="primary" icon="check" :iconSize="13" @click="changeStatus(activeReq.id,'confirmed'); drawerOpen=false">Confirm</GmsBtn>
      </div>
    </template>
  </GmsDrawer>

  <GmsModal :open="newModal" title="New Accommodation Request" @close="newModal = false">
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
          <label class="gms-label">Hotel</label>
          <select v-model="form.hotel" class="gms-input gms-select">
            <option value="">— Select —</option>
            <option v-for="h in hotels" :key="h.id" :value="h.id">{{ h.name }}</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Room Type</label>
          <input v-model="form.roomType" class="gms-input" placeholder="Presidential Suite" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Check-in</label>
          <input v-model="form.checkIn" type="date" class="gms-input" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Check-out</label>
          <input v-model="form.checkOut" type="date" class="gms-input" />
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

  <GmsModal :open="editModal" title="Edit Accommodation Request" @close="editModal = false; form.reset(); editingReq = null">
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
          <label class="gms-label">Hotel</label>
          <select v-model="form.hotel" class="gms-input gms-select">
            <option value="">— Select —</option>
            <option v-for="h in hotels" :key="h.id" :value="h.id">{{ h.name }}</option>
          </select>
        </div>
        <div class="gms-field">
          <label class="gms-label">Room Type</label>
          <input v-model="form.roomType" class="gms-input" placeholder="Presidential Suite" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Check-in</label>
          <input v-model="form.checkIn" type="date" class="gms-input" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Check-out</label>
          <input v-model="form.checkOut" type="date" class="gms-input" />
        </div>
      </div>
      <div class="gms-field">
        <label class="gms-label">Notes</label>
        <textarea v-model="form.notes" class="gms-input gms-textarea" rows="2" />
      </div>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="editModal = false; form.reset(); editingReq = null">Cancel</button>
      <button class="gms-btn gms-btn-primary" :disabled="form.processing" @click="saveEdit">Save Changes</button>
    </template>
  </GmsModal>
</template>
