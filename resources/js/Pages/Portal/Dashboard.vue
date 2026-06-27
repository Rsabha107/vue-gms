<script setup>
import { ref, inject, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import PortalModal from '@/Components/Portal/PortalModal.vue'
import PortalInput from '@/Components/Portal/PortalInput.vue'
import PortalSelect from '@/Components/Portal/PortalSelect.vue'
import PortalTextarea from '@/Components/Portal/PortalTextarea.vue'
import GmsDatePicker from '@/Components/Gms/GmsDatePicker.vue'
import GmsTimePicker from '@/Components/Gms/GmsTimePicker.vue'
import GmsBtn from '@/Components/Gms/GmsBtn.vue'
import GmsConfirmModal from '@/Components/Gms/GmsConfirmModal.vue'

defineOptions({ layout: PortalLayout })

const toast = inject('toast')

const props = defineProps({
    guest: { type: Object, required: true },
    event: { type: Object, required: true },
    invitation: { type: Object, required: true },
    matches: { type: Array, default: () => [] },
    companions: { type: Array, default: () => [] },
    services: { type: Object, default: () => ({}) },
})

const statusLabels = {
    not_invited: 'Not Invited',
    invited: 'Invited',
    accepted: 'Accepted',
    declined: 'Declined',
    confirmed: 'Confirmed',
}


function formatDate(dateStr) {
    const date = new Date(dateStr)
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

// Transport detail view
const transportDriver = computed(() => {
    const transports = props.services.transport || []
    if (!transports.length) return null
    const confirmed = transports.find(t => t.status?.name === 'confirmed')
    const first = confirmed || transports[0]
    const driverName = first.driver || 'Driver TBD'
    const initials = driverName.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
    return {
        name: driverName,
        initials,
        vehicle: first.vehicle || 'Vehicle TBD',
        plate: first.notes?.match(/[A-Z]{2}\s?\d{3,4}/)?.[0] || '',
        status: confirmed ? 'confirmed' : (first.status?.name || 'pending'),
    }
})

const transportMovements = computed(() => {
    const transports = props.services.transport || []
    if (!transports.length) return []

    const now = new Date()
    let foundUpNext = false

    const sorted = [...transports]
        .filter(t => t.datetime)
        .sort((a, b) => new Date(a.datetime) - new Date(b.datetime))

    return sorted.map(t => {
        const dt = new Date(t.datetime)
        const isPast = dt < now
        const isUpNext = !isPast && !foundUpNext
        if (isUpNext) foundUpNext = true

        return {
            id: t.id,
            type: t.type || 'Transfer',
            pickup: t.pickup_location || '',
            dropoff: t.dropoff_location || '',
            datetime: dt,
            time: dt.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false }),
            dateKey: dt.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }).toUpperCase(),
            isPast,
            isUpNext,
            status: t.status?.name || 'pending',
            source: t.source,
            fulfilled_by_id: t.fulfilled_by_id,
            notes: t.notes,
            _raw: t,
        }
    })
})

const transportByDate = computed(() => {
    const groups = []
    let currentKey = null
    for (const m of transportMovements.value) {
        if (m.dateKey !== currentKey) {
            currentKey = m.dateKey
            groups.push({ dateLabel: m.dateKey, items: [] })
        }
        groups[groups.length - 1].items.push(m)
    }
    return groups
})

const transportNotes = computed(() => {
    const transports = props.services.transport || []
    return transports
        .filter(t => t.notes)
        .map(t => ({ id: t.id, notes: t.notes, source: t.source, fulfilled_by_id: t.fulfilled_by_id, _raw: t }))
})

function movementIcon(type) {
    const t = (type || '').toLowerCase()
    if (t.includes('airport') || t.includes('arrival')) return 'airport'
    if (t.includes('match')) return 'match'
    if (t.includes('return') || t.includes('hotel')) return 'hotel'
    if (t.includes('departure')) return 'departure'
    return 'transfer'
}

// Flight Request Modal
const flightModalOpen = ref(false)
const flightForm = useForm({
    trip_type: 'round_trip',
    departure_city: '',
    arrival_city: '',
    departure_date: '',
    departure_time: '',
    return_date: '',
    return_time: '',
    class: 'economy',
    special_requests: '',
})

const classOptions = [
    { value: 'economy', label: 'Economy' },
    { value: 'business', label: 'Business' },
    { value: 'first', label: 'First Class' },
]

const editingFlightId = ref(null)

function openFlightModal(flight = null) {
    if (flight) {
        editingFlightId.value = flight.id
        const inbound = flight.legs?.find(l => l.dir.toLowerCase() === 'inbound')
        const outbound = flight.legs?.find(l => l.dir.toLowerCase() === 'outbound')
        flightForm.trip_type = outbound ? 'round_trip' : 'one_way'
        flightForm.departure_city = inbound?.from_city || ''
        flightForm.arrival_city = inbound?.to_city || ''
        flightForm.departure_date = inbound?.date || ''
        flightForm.departure_time = inbound?.dep || ''
        flightForm.return_date = outbound?.date || ''
        flightForm.return_time = outbound?.dep || ''
        flightForm.class = (inbound?.cls || outbound?.cls || 'business').toLowerCase()
        flightForm.special_requests = flight.note || ''
    } else {
        editingFlightId.value = null
    }
    flightModalOpen.value = true
}

function closeFlightModal() {
    flightModalOpen.value = false
    editingFlightId.value = null
    flightForm.reset()
    flightForm.clearErrors()
}

function submitFlightRequest() {
    const routeName = editingFlightId.value
        ? route('portal.services.flights.update', [props.guest.id, editingFlightId.value])
        : route('portal.services.flights.store', props.guest.id)
    const method = editingFlightId.value ? 'put' : 'post'

    flightForm[method](routeName, {
        preserveScroll: true,
        onSuccess: () => {
            closeFlightModal()
            toast(editingFlightId.value ? 'Flight request updated' : 'Flight request submitted successfully')
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Failed. Please try again.', 'error')
        }
    })
}

// Delete confirmation modal
const deleteModal = ref({ open: false, message: '', action: null, loading: false })

function confirmDelete(message, action) {
    deleteModal.value = { open: true, message, action, loading: false }
}
function closeDeleteModal() {
    deleteModal.value = { open: false, message: '', action: null, loading: false }
}
function executeDelete() {
    if (!deleteModal.value.action) return
    deleteModal.value.loading = true
    deleteModal.value.action()
}

function deleteFlight(flight) {
    confirmDelete('Are you sure you want to delete this flight request?', () => {
        router.delete(route('portal.services.flights.destroy', [props.guest.id, flight.id]), {
            preserveScroll: true,
            onSuccess: () => { closeDeleteModal(); toast('Flight request deleted') },
            onError: () => { deleteModal.value.loading = false },
        })
    })
}

// Accommodation Request Modal
const accommodationModalOpen = ref(false)
const accommodationForm = useForm({
    special_requests: '',
})

const editingAccommId = ref(null)

function openAccommodationModal(acc = null) {
    if (acc) {
        editingAccommId.value = acc.id
        accommodationForm.special_requests = acc.notes || ''
    } else {
        editingAccommId.value = null
    }
    accommodationModalOpen.value = true
}

function closeAccommodationModal() {
    accommodationModalOpen.value = false
    editingAccommId.value = null
    accommodationForm.reset()
    accommodationForm.clearErrors()
}

function submitAccommodationRequest() {
    if (!accommodationForm.special_requests?.trim()) {
        accommodationForm.setError('special_requests', 'Please describe your accommodation preferences.')
        return
    }
    accommodationForm.clearErrors()
    const routeName = editingAccommId.value
        ? route('portal.services.accommodation.update', [props.guest.id, editingAccommId.value])
        : route('portal.services.accommodation.store', props.guest.id)
    const method = editingAccommId.value ? 'put' : 'post'

    accommodationForm[method](routeName, {
        preserveScroll: true,
        onSuccess: () => {
            closeAccommodationModal()
            toast(editingAccommId.value ? 'Accommodation request updated' : 'Accommodation request submitted successfully')
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Failed. Please try again.', 'error')
        }
    })
}

function deleteAccommodation(acc) {
    confirmDelete('Are you sure you want to delete this accommodation request?', () => {
        router.delete(route('portal.services.accommodation.destroy', [props.guest.id, acc.id]), {
            preserveScroll: true,
            onSuccess: () => { closeDeleteModal(); toast('Accommodation request deleted') },
            onError: () => { deleteModal.value.loading = false },
        })
    })
}

// Transport Request Modal
const transportModalOpen = ref(false)
const transportForm = useForm({
    special_requests: '',
})

const editingTransportId = ref(null)

function openTransportModal(trans = null) {
    if (trans) {
        editingTransportId.value = trans.id
        transportForm.special_requests = trans.notes || ''
    } else {
        editingTransportId.value = null
    }
    transportModalOpen.value = true
}

function closeTransportModal() {
    transportModalOpen.value = false
    editingTransportId.value = null
    transportForm.reset()
    transportForm.clearErrors()
}

function submitTransportRequest() {
    if (!transportForm.special_requests?.trim()) {
        transportForm.setError('special_requests', 'Please describe your transport preferences.')
        return
    }
    transportForm.clearErrors()
    const routeName = editingTransportId.value
        ? route('portal.services.transport.update', [props.guest.id, editingTransportId.value])
        : route('portal.services.transport.store', props.guest.id)
    const method = editingTransportId.value ? 'put' : 'post'

    transportForm[method](routeName, {
        preserveScroll: true,
        onSuccess: () => {
            closeTransportModal()
            toast(editingTransportId.value ? 'Transport request updated' : 'Transport request submitted successfully')
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Failed. Please try again.', 'error')
        }
    })
}

function deleteTransport(trans) {
    confirmDelete('Are you sure you want to delete this transport request?', () => {
        router.delete(route('portal.services.transport.destroy', [props.guest.id, trans.id]), {
            preserveScroll: true,
            onSuccess: () => { closeDeleteModal(); toast('Transport request deleted') },
            onError: () => { deleteModal.value.loading = false },
        })
    })
}

// Flight detail modal
const flightDetailOpen = ref(false)
const flightDetailData = ref(null)

function openFlightDetail(flight) {
    flightDetailData.value = flight
    flightDetailOpen.value = true
}

function formatLegDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
}

// Guest remarks
const remarksOpen = ref({})
const remarksText = ref({})
const remarksSubmitting = ref({})

function toggleRemarks(type, id) {
    const key = `${type}-${id}`
    remarksOpen.value[key] = !remarksOpen.value[key]
}

function submitRemarks(type, id) {
    const key = `${type}-${id}`
    const text = remarksText.value[key]?.trim()
    if (!text) return

    remarksSubmitting.value[key] = true
    router.post(route('portal.services.remarks', [type, props.guest.id, id]), { remarks: text }, {
        preserveScroll: true,
        onSuccess: () => {
            remarksSubmitting.value[key] = false
            remarksOpen.value[key] = false
            toast('Your remarks have been submitted')
        },
        onError: () => {
            remarksSubmitting.value[key] = false
            toast('Failed to submit remarks', 'error')
        },
    })
}

// Companions
const RELATIONS = ['Spouse', 'Family', 'Aide', 'Security', 'Delegate', 'Interpreter', 'Companion']
const localCompanions = ref(props.companions.map(c => ({
    name: c.name || '', relation: c.relation || 'Companion',
    passport_no: c.passport_no || '', personal_photo: c.personal_photo || '', passport_front: c.passport_front || '',
    editing: false,
})))
const compUploading = ref({})

function addCompanion() {
    localCompanions.value.push({ name: '', relation: 'Companion', passport_no: '', personal_photo: '', passport_front: '', editing: true })
}

function removeCompanion(i) {
    localCompanions.value.splice(i, 1)
    saveCompanions()
}

function toggleEdit(i) {
    localCompanions.value[i].editing = !localCompanions.value[i].editing
}

const compForm = useForm({ companions: [] })

function saveCompanions() {
    compForm.companions = localCompanions.value
        .filter(c => c.name.trim())
        .map(c => ({ name: c.name, relation: c.relation, passport_no: c.passport_no, personal_photo: c.personal_photo, passport_front: c.passport_front }))
    compForm.post(route('portal.companions.save', props.guest.id), {
        preserveScroll: true,
        onSuccess: () => {
            localCompanions.value.forEach(c => c.editing = false)
            toast('Companions saved')
        },
        onError: () => toast('Failed to save companions', 'error'),
    })
}

async function uploadCompFile(file, idx, field) {
    const key = `${idx}-${field}`
    compUploading.value[key] = true
    const fd = new FormData()
    fd.append('file', file)
    try {
        const { data } = await axios.post('/rsvp/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        localCompanions.value[idx][field] = data.path
    } catch { console.error('Upload failed') }
    compUploading.value[key] = false
}
</script>

<template>
  <div>
    <h1 style="font-size: 28px; font-weight: 600; margin-bottom: 8px; color: var(--portal-text);">
      Welcome, {{ guest.firstName || guest.name }}
    </h1>
    <p style="font-size: 15px; color: var(--portal-text-2); margin-bottom: 32px;">
      Your personal itinerary and services dashboard for {{ event.name }}
    </p>

    <!-- Invitation Status Card -->
    <div class="portal-card">
      <div class="portal-card-title">Invitation Status</div>
      <div class="portal-grid">
        <div class="portal-card-section">
          <div class="portal-label">Status</div>
          <div class="portal-value">
            <span class="portal-badge" :class="{
              success: invitation.status?.name === 'confirmed' || invitation.status?.name === 'accepted',
              warning: invitation.status?.name === 'invited',
              info: invitation.status?.name === 'not_invited'
            }">
              {{ statusLabels[invitation.status?.name] || invitation.status?.name }}
            </span>
          </div>
        </div>
        
        <div class="portal-card-section">
          <div class="portal-label">Service Level</div>
          <div class="portal-value">{{ guest.tier_info?.name || 'Standard' }}</div>
        </div>

        <div v-if="guest.group" class="portal-card-section">
          <div class="portal-label">Group</div>
          <div class="portal-value">{{ guest.group.label }}</div>
        </div>
      </div>
    </div>

    <!-- Companions -->
    <div class="portal-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div class="portal-card-title" style="margin-bottom: 0;">Travelling Companions</div>
        <button class="portal-btn" @click="addCompanion">+ Add Companion</button>
      </div>

      <div v-if="localCompanions.length === 0" style="text-align: center; padding: 24px; color: var(--portal-text-2); font-size: 13px;">
        No companions added yet. Click "Add Companion" to add someone travelling with you.
      </div>

      <div v-else style="display: flex; flex-direction: column; gap: 12px;">
        <div v-for="(c, ci) in localCompanions" :key="ci" class="pc-card">
          <!-- View mode -->
          <div v-if="!c.editing" class="pc-view">
            <div style="flex: 1; min-width: 0;">
              <div style="font-weight: 600; font-size: 14px;">{{ c.name }}</div>
              <div style="font-size: 12px; color: var(--portal-text-2); margin-top: 2px;">
                {{ c.relation }}
                <span v-if="c.passport_no"> · Passport: {{ c.passport_no }}</span>
              </div>
              <div v-if="c.personal_photo || c.passport_front" style="display: flex; gap: 6px; margin-top: 6px;">
                <a v-if="c.personal_photo" :href="`/gms/api/document/${c.personal_photo}`" target="_blank" class="pc-doc-badge">📷 Photo</a>
                <a v-if="c.passport_front" :href="`/gms/api/document/${c.passport_front}`" target="_blank" class="pc-doc-badge">🪪 Passport</a>
              </div>
            </div>
            <div style="display: flex; gap: 6px; flex-shrink: 0;">
              <button class="portal-btn portal-btn-sm" @click="toggleEdit(ci)">Edit</button>
              <button class="portal-btn portal-btn-sm portal-btn-danger" @click="removeCompanion(ci)">Remove</button>
            </div>
          </div>

          <!-- Edit mode -->
          <div v-else class="pc-edit">
            <div class="portal-form-row">
              <div class="portal-form-group" style="flex: 1;">
                <label class="portal-form-label">Full Name <span class="required">*</span></label>
                <input v-model="c.name" type="text" class="portal-input" placeholder="Full name" />
              </div>
              <div class="portal-form-group" style="width: 160px; flex: none;">
                <label class="portal-form-label">Relation</label>
                <select v-model="c.relation" class="portal-input">
                  <option v-for="r in RELATIONS" :key="r" :value="r">{{ r }}</option>
                </select>
              </div>
            </div>

            <div class="portal-form-group">
              <label class="portal-form-label">Passport No.</label>
              <input v-model="c.passport_no" type="text" class="portal-input" placeholder="e.g. AB1234567" style="max-width: 280px; font-family: var(--gms-font-mono);" />
            </div>

            <div class="portal-form-row">
              <div class="portal-form-group">
                <label class="portal-form-label">Personal Photo</label>
                <div v-if="!c.personal_photo && !compUploading[ci+'-personal_photo']" class="pc-upload" @click="$refs['compPhoto' + ci]?.[0]?.click()">
                  📷 Upload photo
                </div>
                <div v-else-if="compUploading[ci+'-personal_photo']" class="pc-upload pc-uploading">Uploading…</div>
                <div v-else class="pc-uploaded">
                  <img :src="`/gms/api/document/${c.personal_photo}`" class="pc-thumb" />
                  <span style="color: #16a34a; font-size: 11px; font-weight: 600;">✓ Uploaded</span>
                  <button type="button" class="portal-btn portal-btn-sm" @click="$refs['compPhoto' + ci]?.[0]?.click()">Replace</button>
                  <button type="button" class="portal-btn portal-btn-sm" @click="c.personal_photo = ''">✕</button>
                </div>
                <input :ref="'compPhoto' + ci" type="file" accept="image/jpeg,image/png" style="display:none;" @change="e => { if(e.target.files[0]) uploadCompFile(e.target.files[0], ci, 'personal_photo'); e.target.value='' }" />
              </div>
              <div class="portal-form-group">
                <label class="portal-form-label">Passport Front Copy</label>
                <div v-if="!c.passport_front && !compUploading[ci+'-passport_front']" class="pc-upload" @click="$refs['compPass' + ci]?.[0]?.click()">
                  🪪 Upload passport
                </div>
                <div v-else-if="compUploading[ci+'-passport_front']" class="pc-upload pc-uploading">Uploading…</div>
                <div v-else class="pc-uploaded">
                  <img v-if="c.passport_front.match(/\.(jpg|jpeg|png)$/i)" :src="`/gms/api/document/${c.passport_front}`" class="pc-thumb" />
                  <span v-else>📄 PDF</span>
                  <span style="color: #16a34a; font-size: 11px; font-weight: 600;">✓ Uploaded</span>
                  <button type="button" class="portal-btn portal-btn-sm" @click="$refs['compPass' + ci]?.[0]?.click()">Replace</button>
                  <button type="button" class="portal-btn portal-btn-sm" @click="c.passport_front = ''">✕</button>
                </div>
                <input :ref="'compPass' + ci" type="file" accept="image/jpeg,image/png,application/pdf" style="display:none;" @change="e => { if(e.target.files[0]) uploadCompFile(e.target.files[0], ci, 'passport_front'); e.target.value='' }" />
              </div>
            </div>

            <div style="display: flex; gap: 8px; margin-top: 8px;">
              <GmsBtn variant="primary" :disabled="!c.name.trim()" :processing="compForm.processing" @click="saveCompanions">Save</GmsBtn>
              <GmsBtn v-if="c.name.trim()" variant="ghost" @click="toggleEdit(ci)">Cancel</GmsBtn>
              <GmsBtn v-else variant="ghost" @click="localCompanions.splice(ci, 1)">Cancel</GmsBtn>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Match Schedule -->
    <div v-if="matches.length" class="portal-card">
      <div class="portal-card-title">Match Schedule</div>
      <div style="display: flex; flex-direction: column; gap: 16px;">
        <div v-for="match in matches" :key="match.id" class="match-item">
          <div style="display: flex; align-items: center; gap: 16px;">
            <div class="match-date">
              <div style="font-size: 20px; font-weight: 600; line-height: 1;">
                {{ new Date(match.date).getDate() }}
              </div>
              <div style="font-size: 11px; color: var(--portal-text-3); text-transform: uppercase;">
                {{ new Date(match.date).toLocaleDateString('en-GB', { month: 'short' }) }}
              </div>
            </div>
            <div style="flex: 1;">
              <div style="font-size: 15px; font-weight: 500; color: var(--portal-text); margin-bottom: 4px;">
                {{ match.team_a_name && match.team_b_name ? `${match.team_a_name} vs ${match.team_b_name}` : match.label }}
              </div>
              <div style="font-size: 13px; color: var(--portal-text-2);">
                {{ match.time }} · {{ match.venue?.name || 'Lusail Stadium' }}
              </div>
            </div>
            <span class="portal-badge info">{{ match.stage || 'Match' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Service Requests Overview -->
    <div class="portal-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div class="portal-card-title" style="margin-bottom: 0;">Service Requests</div>
        <div style="display: flex; gap: 8px;">
          <button v-if="!services.flights?.length" class="portal-btn" @click="openFlightModal">
            Request Flight
          </button>
          <button v-if="!services.accommodation?.length" class="portal-btn" @click="openAccommodationModal">
            Request Hotel
          </button>
          <button v-if="!services.transport?.length" class="portal-btn" @click="openTransportModal">
            Request Transport
          </button>
        </div>
      </div>
      
      <!-- Flights -->
      <div v-if="services.flights?.length" class="service-section">
        <div class="portal-label">Flights</div>
        <div v-for="flight in services.flights" :key="flight.id" class="service-item">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <div style="font-size: 14px; font-weight: 500;">{{ flight.code }}</div>
              <span v-if="flight.ref" style="font-size: 12px; color: var(--portal-text-3); font-family: var(--gms-font-mono, monospace);">
                PNR: {{ flight.ref }}
              </span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <span class="portal-badge" :class="flight.status?.name === 'confirmed' ? 'success' : 'warning'">
                {{ flight.status?.label || flight.status?.name || 'Pending' }}
              </span>
              <template v-if="flight.source === 'portal' && !flight.fulfilled_by_id">
                <button class="portal-btn portal-btn-sm" @click="openFlightModal(flight)">Edit</button>
                <button class="portal-btn portal-btn-sm portal-btn-danger" @click="deleteFlight(flight)">Delete</button>
              </template>
            </div>
          </div>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px;">
            <div v-for="leg in flight.legs" :key="leg.id"
                 style="background: var(--portal-bg); border: 1px solid var(--portal-border); border-radius: 6px; padding: 10px;">
              <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                <span style="font-size: 14px; line-height: 1;">{{ leg.dir.toLowerCase() === 'outbound' ? '✈️↗️' : '✈️↘️' }}</span>
                <span style="color: var(--portal-text-2); font-size: 11px; font-weight: 500; text-transform: capitalize;">
                  {{ leg.dir.charAt(0).toUpperCase() + leg.dir.slice(1).toLowerCase() }}
                </span>
              </div>
              <div style="font-size: 13px; font-weight: 500; color: var(--portal-text); margin-bottom: 6px;">
                {{ leg.from_city }} → {{ leg.to_city }}
              </div>
              <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 12px; color: var(--portal-text-2);">
                <span>{{ formatDate(leg.date) }}</span>
                <span v-if="leg.dep">• {{ leg.dep }}</span>
                <span v-if="leg.cls">• {{ leg.cls.charAt(0).toUpperCase() + leg.cls.slice(1) }}</span>
              </div>
            </div>
          </div>
          <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
            <button class="portal-btn portal-btn-sm" @click="openFlightDetail(flight)">View full details</button>
          </div>
          <div v-if="flight.note" style="font-size: 12px; color: var(--portal-text-2); margin-top: 10px; font-style: italic;">
            "{{ flight.note }}"
          </div>
          <!-- Guest remarks for pending flights -->
          <div v-if="flight.status?.name === 'pending' && flight.source !== 'portal'" class="svc-review">
            <div v-if="flight.guest_remarks" class="svc-remarks-sent">
              <span style="font-size:11px;font-weight:600;color:var(--portal-text-3);text-transform:uppercase;letter-spacing:.5px;">Your remarks</span>
              <div style="margin-top:4px;font-style:italic;">"{{ flight.guest_remarks }}"</div>
            </div>
            <div v-else>
              <div class="svc-review-label">Your protocol team has arranged this flight. Please review and let us know if you have any changes.</div>
              <div v-if="remarksOpen[`flight-${flight.id}`]" style="margin-top:8px;">
                <textarea v-model="remarksText[`flight-${flight.id}`]" class="portal-textarea" rows="3" placeholder="e.g., I prefer a window seat, different date needed, etc."></textarea>
                <div style="display:flex;gap:8px;margin-top:8px;">
                  <GmsBtn variant="primary" :processing="remarksSubmitting[`flight-${flight.id}`]" @click="submitRemarks('flight', flight.id)">Submit remarks</GmsBtn>
                  <GmsBtn variant="ghost" @click="toggleRemarks('flight', flight.id)">Cancel</GmsBtn>
                </div>
              </div>
              <div v-else style="margin-top:8px;">
                <GmsBtn variant="ghost" @click="toggleRemarks('flight', flight.id)">Add remarks or change request</GmsBtn>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Accommodation -->
      <div v-if="services.accommodation?.length" class="service-section">
        <div class="portal-label">Accommodation</div>
        <div v-for="acc in services.accommodation" :key="acc.id" class="service-item">
          <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="flex: 1;">
              <div style="font-size: 14px; font-weight: 500;">Accommodation Request</div>
              <div v-if="acc.notes" style="font-size: 13px; color: var(--portal-text-2); margin-top: 4px; font-style: italic;">
                "{{ acc.notes }}"
              </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0; margin-left: 12px;">
              <span class="portal-badge" :class="acc.status?.name === 'confirmed' ? 'success' : 'warning'">
                {{ acc.status?.label || acc.status?.name || 'Pending' }}
              </span>
              <template v-if="acc.source === 'portal' && !acc.fulfilled_by_id">
                <button class="portal-btn portal-btn-sm" @click="openAccommodationModal(acc)">Edit</button>
                <button class="portal-btn portal-btn-sm portal-btn-danger" @click="deleteAccommodation(acc)">Delete</button>
              </template>
            </div>
          </div>
          <!-- Guest remarks for pending accommodation -->
          <div v-if="acc.status?.name === 'pending' && acc.source !== 'portal'" class="svc-review">
            <div v-if="acc.guest_remarks" class="svc-remarks-sent">
              <span style="font-size:11px;font-weight:600;color:var(--portal-text-3);text-transform:uppercase;letter-spacing:.5px;">Your remarks</span>
              <div style="margin-top:4px;font-style:italic;">"{{ acc.guest_remarks }}"</div>
            </div>
            <div v-else>
              <div class="svc-review-label">Your protocol team has arranged this accommodation. Please review and let us know if you have any changes.</div>
              <div v-if="remarksOpen[`accommodation-${acc.id}`]" style="margin-top:8px;">
                <textarea v-model="remarksText[`accommodation-${acc.id}`]" class="portal-textarea" rows="3" placeholder="e.g., I need a higher floor, early check-in, etc."></textarea>
                <div style="display:flex;gap:8px;margin-top:8px;">
                  <GmsBtn variant="primary" :processing="remarksSubmitting[`accommodation-${acc.id}`]" @click="submitRemarks('accommodation', acc.id)">Submit remarks</GmsBtn>
                  <GmsBtn variant="ghost" @click="toggleRemarks('accommodation', acc.id)">Cancel</GmsBtn>
                </div>
              </div>
              <div v-else style="margin-top:8px;">
                <GmsBtn variant="ghost" @click="toggleRemarks('accommodation', acc.id)">Add remarks or change request</GmsBtn>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Transport -->
      <div v-if="services.transport?.length" class="service-section">
        <div class="portal-label">Transport</div>
        <div v-for="trans in services.transport" :key="trans.id" class="service-item">
          <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="flex: 1;">
              <div style="font-size: 14px; font-weight: 500;">{{ trans.type || 'Transport Request' }}</div>
              <div v-if="trans.notes" style="font-size: 13px; color: var(--portal-text-2); margin-top: 4px; font-style: italic;">
                "{{ trans.notes }}"
              </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0; margin-left: 12px;">
              <span class="portal-badge" :class="trans.status?.name === 'confirmed' ? 'success' : 'warning'">
                {{ trans.status?.label || trans.status?.name || 'Pending' }}
              </span>
              <template v-if="trans.source === 'portal' && !trans.fulfilled_by_id">
                <button class="portal-btn portal-btn-sm" @click="openTransportModal(trans)">Edit</button>
                <button class="portal-btn portal-btn-sm portal-btn-danger" @click="deleteTransport(trans)">Delete</button>
              </template>
            </div>
          </div>
          <!-- Guest remarks for pending transport -->
          <div v-if="trans.status?.name === 'pending' && trans.source !== 'portal'" class="svc-review">
            <div v-if="trans.guest_remarks" class="svc-remarks-sent">
              <span style="font-size:11px;font-weight:600;color:var(--portal-text-3);text-transform:uppercase;letter-spacing:.5px;">Your remarks</span>
              <div style="margin-top:4px;font-style:italic;">"{{ trans.guest_remarks }}"</div>
            </div>
            <div v-else>
              <div class="svc-review-label">Your protocol team has arranged this transport. Please review and let us know if you have any changes.</div>
              <div v-if="remarksOpen[`transport-${trans.id}`]" style="margin-top:8px;">
                <textarea v-model="remarksText[`transport-${trans.id}`]" class="portal-textarea" rows="3" placeholder="e.g., I prefer an SUV, different pickup time, etc."></textarea>
                <div style="display:flex;gap:8px;margin-top:8px;">
                  <GmsBtn variant="primary" :processing="remarksSubmitting[`transport-${trans.id}`]" @click="submitRemarks('transport', trans.id)">Submit remarks</GmsBtn>
                  <GmsBtn variant="ghost" @click="toggleRemarks('transport', trans.id)">Cancel</GmsBtn>
                </div>
              </div>
              <div v-else style="margin-top:8px;">
                <GmsBtn variant="ghost" @click="toggleRemarks('transport', trans.id)">Add remarks or change request</GmsBtn>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="!services.flights?.length && !services.accommodation?.length && !services.transport?.length"
           style="text-align: center; padding: 32px; color: var(--portal-text-2);">
        <p style="margin-bottom: 8px;">No service requests yet</p>
        <p style="font-size: 13px;">Your protocol officer will arrange services for your visit</p>
      </div>
    </div>

    <!-- Contact Information -->
    <div class="portal-card">
      <div class="portal-card-title">Need Assistance?</div>
      <p style="color: var(--portal-text-2); margin-bottom: 16px;">
        For any questions or changes to your itinerary, please contact your dedicated protocol officer.
      </p>
      <div class="portal-grid">
        <div class="portal-card-section">
          <div class="portal-label">Event Support</div>
          <div class="portal-value">protocol@dohacup26.qa</div>
        </div>
        <div class="portal-card-section">
          <div class="portal-label">Emergency Hotline</div>
          <div class="portal-value">+974 xxxx xxxx</div>
        </div>
      </div>
    </div>

    <!-- Flight Request Modal -->
    <PortalModal :open="flightModalOpen" :title="editingFlightId ? 'Edit Flight Request' : 'Request Flight'" :subtitle="editingFlightId ? 'Update your flight preferences' : 'Submit your flight requirements'" @close="closeFlightModal">
      <form @submit.prevent="submitFlightRequest">
        <!-- Trip Type Selector -->
        <div style="margin-bottom: 20px;">
          <label class="portal-form-label">Trip Type</label>
          <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button
              type="button"
              class="portal-btn"
              :class="{ 'portal-btn-primary': flightForm.trip_type === 'round_trip' }"
              @click="flightForm.trip_type = 'round_trip'"
              style="flex: 1;"
            >
              Round Trip
            </button>
            <button
              type="button"
              class="portal-btn"
              :class="{ 'portal-btn-primary': flightForm.trip_type === 'one_way' }"
              @click="flightForm.trip_type = 'one_way'"
              style="flex: 1;"
            >
              One-Way
            </button>
          </div>
        </div>

        <!-- Outbound Flight -->
        <div style="margin-bottom: 24px;">
          <div class="portal-label" style="margin-bottom: 16px; font-size: 14px; font-weight: 600; color: var(--portal-text);">Outbound Flight</div>
          <div class="portal-form-row">
          <PortalInput
            v-model="flightForm.departure_city"
            label="Departure City"
            placeholder="e.g., London"
            required
            :error="flightForm.errors.departure_city"
          />
          <PortalInput
            v-model="flightForm.arrival_city"
            label="Arrival City"
            placeholder="e.g., Doha"
            required
            :error="flightForm.errors.arrival_city"
          />
        </div>

          <div class="portal-form-row">
            <div class="portal-form-group">
              <label class="portal-form-label">
                Departure Date
                <span class="required">*</span>
              </label>
              <GmsDatePicker
                v-model="flightForm.departure_date"
                placeholder="Select departure date"
                dateFormat="Y-m-d"
                :minDate="new Date().toISOString().split('T')[0]"
                :error="!!flightForm.errors.departure_date"
              />
              <p v-if="flightForm.errors.departure_date" class="portal-form-error">{{ flightForm.errors.departure_date }}</p>
            </div>
            <div class="portal-form-group">
              <label class="portal-form-label">Preferred Time</label>
              <GmsTimePicker
                v-model="flightForm.departure_time"
                placeholder="Select departure time"
                :error="!!flightForm.errors.departure_time"
              />
              <p v-if="flightForm.errors.departure_time" class="portal-form-error">{{ flightForm.errors.departure_time }}</p>
            </div>
          </div>
        </div>

        <!-- Return Flight (only if round trip) -->
        <div v-if="flightForm.trip_type === 'round_trip'" style="margin-bottom: 24px;">
          <div class="portal-label" style="margin-bottom: 16px; font-size: 14px; font-weight: 600; color: var(--portal-text);">Return Flight</div>
          <div class="portal-form-row">
            <div class="portal-form-group">
              <label class="portal-form-label">
                Return Date
                <span v-if="flightForm.trip_type === 'round_trip'" class="required">*</span>
              </label>
              <GmsDatePicker
                v-model="flightForm.return_date"
                placeholder="Select return date"
                dateFormat="Y-m-d"
                :minDate="flightForm.departure_date || new Date().toISOString().split('T')[0]"
                :error="!!flightForm.errors.return_date"
              />
              <p v-if="flightForm.errors.return_date" class="portal-form-error">{{ flightForm.errors.return_date }}</p>
            </div>
            <div class="portal-form-group">
              <label class="portal-form-label">Preferred Time</label>
              <GmsTimePicker
                v-model="flightForm.return_time"
                placeholder="Select return time"
                :error="!!flightForm.errors.return_time"
              />
              <p v-if="flightForm.errors.return_time" class="portal-form-error">{{ flightForm.errors.return_time }}</p>
            </div>
          </div>
        </div>

        <!-- Travel Details -->
        <div style="margin-bottom: 16px;">
          <div class="portal-label" style="margin-bottom: 16px; font-size: 14px; font-weight: 600; color: var(--portal-text);">Travel Details</div>
          <div class="portal-form-row">
          <PortalSelect
            v-model="flightForm.class"
            label="Cabin Class"
            :options="classOptions"
            value-key="value"
            label-key="label"
            required
            :error="flightForm.errors.class"
          />
          </div>
        </div>

        <PortalTextarea
          v-model="flightForm.special_requests"
          label="Special Requests"
          placeholder="Any dietary requirements, accessibility needs, or other preferences..."
          :rows="4"
          help="Optional: Let us know if you have any special requirements"
          :error="flightForm.errors.special_requests"
        />
      </form>

      <template #footer>
        <GmsBtn variant="ghost" :disabled="flightForm.processing" @click="closeFlightModal">Cancel</GmsBtn>
        <GmsBtn variant="primary" :processing="flightForm.processing" @click="submitFlightRequest">
          {{ editingFlightId ? 'Update Request' : 'Submit Request' }}
        </GmsBtn>
      </template>
    </PortalModal>

    <!-- Accommodation Request Modal -->
    <PortalModal :open="accommodationModalOpen" :title="editingAccommId ? 'Edit Accommodation Request' : 'Request Accommodation'" :subtitle="editingAccommId ? 'Update your accommodation preferences' : 'Let your protocol officer know your hotel needs'" @close="closeAccommodationModal">
      <form @submit.prevent="submitAccommodationRequest">
        <p style="font-size: 14px; color: var(--portal-text-2); line-height: 1.6; margin-bottom: 20px;">
          Your protocol team will arrange the best hotel and room based on your itinerary. Use the field below to share any preferences or requirements.
        </p>

        <PortalTextarea
          v-model="accommodationForm.special_requests"
          label="Special Requests & Preferences"
          placeholder="e.g., I prefer a suite, I need a room near the elevator, early check-in required, etc."
          :rows="5"
          help="Our team will arrange hotel, room type, and dates based on your event schedule."
          :error="accommodationForm.errors.special_requests"
        />
      </form>

      <template #footer>
        <GmsBtn variant="ghost" :disabled="accommodationForm.processing" @click="closeAccommodationModal">Cancel</GmsBtn>
        <GmsBtn variant="primary" :processing="accommodationForm.processing" @click="submitAccommodationRequest">
          {{ editingAccommId ? 'Update Request' : 'Submit Request' }}
        </GmsBtn>
      </template>
    </PortalModal>

    <!-- Transport Request Modal -->
    <PortalModal :open="transportModalOpen" :title="editingTransportId ? 'Edit Transport Request' : 'Request Transport'" :subtitle="editingTransportId ? 'Update your transport preferences' : 'Let your protocol officer know you need ground transport'" @close="closeTransportModal">
      <form @submit.prevent="submitTransportRequest">
        <p style="font-size: 14px; color: var(--portal-text-2); line-height: 1.6; margin-bottom: 20px;">
          Your protocol team will arrange the best vehicle and schedule based on your itinerary. Use the field below to share any preferences or requirements.
        </p>

        <PortalTextarea
          v-model="transportForm.special_requests"
          label="Special Requests & Preferences"
          placeholder="e.g., I prefer an SUV, I need wheelchair-accessible transport, I'd like airport pickup on arrival day, etc."
          :rows="5"
          help="Our team will arrange vehicle type, routes, and timing based on your event schedule."
          :error="transportForm.errors.special_requests"
        />
      </form>

      <template #footer>
        <GmsBtn variant="ghost" :disabled="transportForm.processing" @click="closeTransportModal">Cancel</GmsBtn>
        <GmsBtn variant="primary" :processing="transportForm.processing" @click="submitTransportRequest">
          {{ editingTransportId ? 'Update Request' : 'Submit Request' }}
        </GmsBtn>
      </template>
    </PortalModal>

    <!-- Flight Detail Modal -->
    <PortalModal
      :open="flightDetailOpen"
      :title="flightDetailData ? `${flightDetailData.code} — Flight Itinerary` : ''"
      subtitle="Review your flight details arranged by your protocol team"
      @close="flightDetailOpen = false; flightDetailData = null"
    >
      <template v-if="flightDetailData">
        <!-- Status & ref header -->
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
          <span class="portal-badge" :class="flightDetailData.status?.name === 'confirmed' ? 'success' : 'warning'" style="font-size: 13px; padding: 5px 12px;">
            {{ flightDetailData.status?.label || flightDetailData.status?.name || 'Pending' }}
          </span>
          <span v-if="flightDetailData.ref" style="font-size: 13px; color: var(--portal-text-3); font-family: var(--gms-font-mono, monospace);">
            PNR: {{ flightDetailData.ref }}
          </span>
        </div>

        <!-- Leg cards -->
        <div style="display: flex; flex-direction: column; gap: 14px;">
          <div v-for="leg in flightDetailData.legs" :key="leg.id" class="fd-leg">
            <div class="fd-leg-header">
              <div class="fd-leg-dir" :class="leg.dir.toLowerCase() === 'inbound' ? 'fd-dir-in' : 'fd-dir-out'">
                {{ leg.dir.toLowerCase() === 'inbound' ? '↘' : '↗' }}
                {{ leg.dir }}
              </div>
              <div v-if="leg.flight_no && leg.flight_no !== 'TBD'" class="fd-leg-flight">{{ leg.airline || '' }} {{ leg.flight_no }}</div>
            </div>

            <!-- Route visual -->
            <div class="fd-route">
              <div class="fd-endpoint">
                <div class="fd-code">{{ leg.from_code !== 'XXX' ? leg.from_code : '' }}</div>
                <div class="fd-city">{{ leg.from_city }}</div>
                <div v-if="leg.dep" class="fd-time">{{ leg.dep }}</div>
              </div>
              <div class="fd-connector">
                <div class="fd-line"></div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--portal-maroon)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
                <div class="fd-line"></div>
              </div>
              <div class="fd-endpoint" style="text-align: right;">
                <div class="fd-code">{{ leg.to_code !== 'XXX' ? leg.to_code : '' }}</div>
                <div class="fd-city">{{ leg.to_city }}</div>
                <div v-if="leg.arr" class="fd-time">{{ leg.arr }}</div>
              </div>
            </div>

            <!-- Details grid -->
            <div class="fd-details">
              <div class="fd-detail">
                <div class="fd-detail-k">Date</div>
                <div class="fd-detail-v">{{ formatLegDate(leg.date) }}</div>
              </div>
              <div class="fd-detail">
                <div class="fd-detail-k">Cabin</div>
                <div class="fd-detail-v">{{ leg.cls || '—' }}</div>
              </div>
              <div v-if="leg.dur" class="fd-detail">
                <div class="fd-detail-k">Duration</div>
                <div class="fd-detail-v">{{ leg.dur }}</div>
              </div>
              <div v-if="leg.airline" class="fd-detail">
                <div class="fd-detail-k">Airline</div>
                <div class="fd-detail-v">{{ leg.airline }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Passengers -->
        <div v-if="flightDetailData.pax" style="margin-top: 16px; padding: 12px 14px; background: var(--portal-bg); border: 1px solid var(--portal-border); border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
          <span style="font-size: 13px; color: var(--portal-text-2);">Passengers</span>
          <span style="font-size: 14px; font-weight: 600;">{{ flightDetailData.pax }}</span>
        </div>

        <!-- Notes -->
        <div v-if="flightDetailData.note" style="margin-top: 12px; padding: 12px 14px; background: var(--portal-bg); border: 1px solid var(--portal-border); border-radius: 8px;">
          <div style="font-size: 11px; font-weight: 600; color: var(--portal-text-3); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Special Requests</div>
          <div style="font-size: 13px; color: var(--portal-text-2); font-style: italic; line-height: 1.5;">"{{ flightDetailData.note }}"</div>
        </div>

        <!-- Guest remarks already submitted -->
        <div v-if="flightDetailData.guest_remarks" style="margin-top: 12px; padding: 12px 14px; background: #fef3c7; border: 1px solid #fef08a; border-radius: 8px;">
          <div style="font-size: 11px; font-weight: 600; color: #a16207; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Your Remarks</div>
          <div style="font-size: 13px; color: #854d0e; font-style: italic; line-height: 1.5;">"{{ flightDetailData.guest_remarks }}"</div>
        </div>

        <!-- Inline remarks form for pending -->
        <div v-if="flightDetailData.status?.name === 'pending' && flightDetailData.source !== 'portal' && !flightDetailData.guest_remarks" style="margin-top: 16px;">
          <div class="svc-review-label" style="margin-bottom: 8px;">Everything look correct? If you need any changes, let your protocol team know below.</div>
          <textarea v-model="remarksText[`flight-${flightDetailData.id}`]" class="portal-textarea" rows="3" placeholder="e.g., I prefer a window seat, different date needed, etc."></textarea>
          <div style="display:flex;gap:8px;margin-top:8px;">
            <GmsBtn variant="primary" :processing="remarksSubmitting[`flight-${flightDetailData.id}`]" @click="submitRemarks('flight', flightDetailData.id)">Submit remarks</GmsBtn>
          </div>
        </div>
      </template>

      <template #footer>
        <GmsBtn variant="ghost" @click="flightDetailOpen = false; flightDetailData = null">Close</GmsBtn>
      </template>
    </PortalModal>

    <!-- Delete Confirmation Modal -->
    <GmsConfirmModal
      :open="deleteModal.open"
      title="Delete Request"
      :message="deleteModal.message"
      confirmText="Delete"
      variant="danger"
      :loading="deleteModal.loading"
      @close="closeDeleteModal"
      @confirm="executeDelete"
    />
  </div>
</template>

<style scoped>
.match-item {
    padding: 16px;
    border: 1px solid var(--portal-border);
    border-radius: 8px;
}

.match-date {
    width: 56px;
    text-align: center;
    padding: 8px;
    border-radius: 6px;
    background: var(--portal-bg);
    color: var(--portal-text);
}

.service-section {
    margin-bottom: 20px;
}

.service-section:last-child {
    margin-bottom: 0;
}

.service-item {
    padding: 12px;
    border: 1px solid var(--portal-border);
    border-radius: 6px;
    margin-top: 8px;
}

/* Companion cards */
.pc-card { border: 1px solid var(--portal-border, var(--gms-border)); border-radius: 10px; overflow: hidden; background: var(--portal-surface, var(--gms-surface)); }
.pc-view { display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; }
.pc-edit { padding: 16px; display: flex; flex-direction: column; gap: 12px; background: var(--portal-bg, var(--gms-bg)); }
.pc-doc-badge { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 5px; background: var(--portal-bg, var(--gms-bg)); border: 1px solid var(--portal-border, var(--gms-border)); text-decoration: none; color: var(--portal-text-2, var(--gms-text-2)); transition: .1s; }
.pc-doc-badge:hover { border-color: var(--gms-maroon); color: var(--gms-maroon); }
.pc-upload { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 14px; border: 2px dashed var(--portal-border, var(--gms-border)); border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 500; color: var(--portal-text-2, var(--gms-text-3)); transition: .12s; }
.pc-upload:hover { border-color: var(--gms-maroon); color: var(--gms-maroon); }
.pc-uploading { cursor: default; border-style: solid; }
.pc-uploaded { display: flex; align-items: center; gap: 8px; padding: 6px 10px; border: 1px solid var(--portal-border, var(--gms-border)); border-radius: 8px; background: var(--portal-bg, var(--gms-bg)); }
.pc-thumb { width: 32px; height: 32px; border-radius: 5px; object-fit: cover; }
.portal-btn-sm { padding: 5px 10px; font-size: 12px; }
.portal-btn-danger { color: #dc2626; border-color: rgba(220,38,38,.2); }
.portal-btn-danger:hover { background: rgba(220,38,38,.06); border-color: #dc2626; }
.portal-input { padding: 10px 12px; border: 1px solid var(--portal-border, var(--gms-border)); border-radius: 8px; font-size: 14px; background: var(--portal-surface, var(--gms-surface)); outline: none; width: 100%; transition: .12s; }

/* Service review/remarks */
.svc-review {
    margin-top: 12px; padding: 12px 14px;
    background: #fefce8; border: 1px solid #fef08a;
    border-radius: 8px;
}
.svc-review-label { font-size: 13px; color: #854d0e; line-height: 1.5; }
.svc-remarks-sent {
    font-size: 13px; color: var(--portal-text-2); line-height: 1.5;
}
.portal-textarea {
    width: 100%; padding: 10px 12px; border: 1px solid var(--portal-border);
    border-radius: 8px; font-size: 14px; font-family: inherit;
    background: var(--portal-surface); resize: vertical; min-height: 60px;
    outline: none; transition: .12s;
}
.portal-textarea:focus { border-color: var(--gms-maroon); box-shadow: 0 0 0 3px rgba(138,31,61,.06); }

/* Flight detail modal */
.fd-leg {
    border: 1px solid var(--portal-border); border-radius: 12px;
    overflow: hidden; background: var(--portal-surface);
}
.fd-leg-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; background: var(--portal-bg);
    border-bottom: 1px solid var(--portal-border);
}
.fd-leg-dir {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; display: flex; align-items: center; gap: 6px;
}
.fd-dir-in { color: #166534; }
.fd-dir-out { color: #9333ea; }
.fd-leg-flight {
    font-size: 12px; font-weight: 600; color: var(--portal-text-2);
    font-family: var(--gms-font-mono, monospace);
}
.fd-route {
    display: flex; align-items: center; gap: 12px;
    padding: 20px 16px;
}
.fd-endpoint { flex: 1; min-width: 0; }
.fd-code {
    font-size: 28px; font-weight: 700; color: var(--portal-text);
    font-family: var(--gms-font-mono, monospace); line-height: 1;
}
.fd-city { font-size: 12px; color: var(--portal-text-2); margin-top: 2px; }
.fd-time {
    font-size: 14px; font-weight: 600; color: var(--portal-text);
    font-family: var(--gms-font-mono, monospace); margin-top: 6px;
}
.fd-connector {
    display: flex; align-items: center; gap: 6px; flex-shrink: 0;
    padding: 0 4px;
}
.fd-line { width: 24px; height: 1px; background: var(--portal-border); }
.fd-details {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1px; background: var(--portal-border);
    border-top: 1px solid var(--portal-border);
}
.fd-detail { padding: 10px 16px; background: var(--portal-surface); }
.fd-detail-k {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: var(--portal-text-3); margin-bottom: 2px;
}
.fd-detail-v { font-size: 13px; font-weight: 600; color: var(--portal-text); }
.portal-input:focus { border-color: var(--gms-maroon); box-shadow: 0 0 0 3px rgba(138,31,61,.06); }

/* ── Transport detail card ── */
.tp-card { padding: 0; overflow: hidden; }
.tp-header { padding: 20px 24px 0; }
.tp-title { font-size: 22px; font-weight: 600; color: var(--portal-text); line-height: 1.2; }
.tp-subtitle { font-size: 13px; color: var(--portal-text-2); margin-top: 2px; }

.tp-driver {
    display: flex; align-items: center; gap: 14px;
    margin: 16px 20px 0; padding: 14px 16px;
    background: #241d1b; border-radius: 12px;
}
.tp-driver-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: #166534; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 15px; flex-shrink: 0;
}
.tp-driver-info { flex: 1; min-width: 0; }
.tp-driver-name { font-size: 15px; font-weight: 600; color: #fff; }
.tp-driver-vehicle { font-size: 12px; color: rgba(255,255,255,.55); margin-top: 1px; }
.tp-phone-btn {
    width: 40px; height: 40px; border-radius: 50%;
    background: #166534; color: #fff; border: none;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; flex-shrink: 0; transition: background .15s;
}
.tp-phone-btn:hover { background: #15803d; }

.tp-status-row {
    display: flex; align-items: center; gap: 8px;
    padding: 14px 24px 0;
}
.tp-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 20px;
    font-size: 13px; font-weight: 500;
}
.tp-pill-confirmed { background: #dcfce7; color: #166534; }
.tp-pill-pending { background: #fef3c7; color: #92400e; }
.tp-pill-count { background: var(--portal-bg); color: var(--portal-text-2); }
.tp-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.tp-dot-green { background: #16a34a; }
.tp-dot-amber { background: #f59e0b; }

/* Timeline */
.tp-timeline { padding: 20px 24px 8px; }
.tp-date-group { margin-bottom: 4px; }
.tp-date-label {
    font-size: 11px; font-weight: 600; color: var(--portal-text-3);
    letter-spacing: .06em; text-transform: uppercase;
    padding: 12px 0 8px;
}
.tp-items { display: flex; flex-direction: column; }

.tp-item {
    display: flex; gap: 14px; min-height: 0;
}
.tp-item-rail {
    display: flex; flex-direction: column; align-items: center;
    width: 32px; flex-shrink: 0; padding-top: 2px;
}
.tp-icon {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.tp-icon-done { background: #dcfce7; color: #16a34a; }
.tp-icon-upcoming { background: #f5eef0; color: #8a1f3d; }
.tp-line {
    width: 2px; flex: 1; min-height: 12px;
    background: var(--portal-border);
    margin: 4px 0;
}

.tp-item-body {
    flex: 1; min-width: 0;
    padding: 4px 0 20px;
    border: 1px solid var(--portal-border);
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 8px;
    background: var(--portal-surface);
}
.tp-item-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; margin-bottom: 4px;
}
.tp-item-title-row { display: flex; align-items: center; gap: 8px; }
.tp-item-type { font-size: 14px; font-weight: 600; color: var(--portal-text); }
.tp-up-next {
    display: inline-block; padding: 2px 8px;
    border-radius: 4px; font-size: 11px; font-weight: 600;
    background: #fce4ec; color: #8a1f3d;
}
.tp-item-time {
    font-size: 15px; font-weight: 600; color: var(--portal-text);
    font-variant-numeric: tabular-nums; white-space: nowrap;
}
.tp-item-route {
    font-size: 13px; color: var(--portal-text-2);
}
.tp-item-actions {
    display: flex; gap: 6px; margin-top: 8px;
}

.tp-notes { padding: 0 24px 4px; }
.tp-note {
    padding: 12px 14px; border-radius: 10px;
    background: var(--portal-bg); border: 1px solid var(--portal-border);
    margin-bottom: 10px;
}
.tp-note-text {
    font-size: 14px; color: var(--portal-text-2);
    font-style: italic; line-height: 1.5;
}

.tp-actions {
    display: flex; gap: 12px; padding: 16px 24px 20px;
    border-top: 1px solid var(--portal-border);
}
.tp-btn-call { flex: 1; justify-content: center; }
.tp-btn-change { flex: 1; justify-content: center; }
</style>
