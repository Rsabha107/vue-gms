<script setup>
import { ref, inject } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import PortalModal from '@/Components/Portal/PortalModal.vue'
import PortalInput from '@/Components/Portal/PortalInput.vue'
import PortalSelect from '@/Components/Portal/PortalSelect.vue'
import PortalTextarea from '@/Components/Portal/PortalTextarea.vue'
import GmsDatePicker from '@/Components/Gms/GmsDatePicker.vue'
import GmsTimePicker from '@/Components/Gms/GmsTimePicker.vue'

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
        const outbound = flight.legs?.find(l => l.dir.toLowerCase() === 'outbound')
        const inbound = flight.legs?.find(l => l.dir.toLowerCase() === 'inbound')
        flightForm.trip_type = inbound ? 'round_trip' : 'one_way'
        flightForm.departure_city = outbound?.from_city || ''
        flightForm.arrival_city = outbound?.to_city || ''
        flightForm.departure_date = outbound?.date || ''
        flightForm.departure_time = outbound?.dep || ''
        flightForm.return_date = inbound?.date || ''
        flightForm.return_time = inbound?.dep || ''
        flightForm.class = (outbound?.cls || 'business').toLowerCase()
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

function deleteFlight(flight) {
    if (!confirm('Delete this flight request?')) return
    router.delete(route('portal.services.flights.destroy', [props.guest.id, flight.id]), {
        preserveScroll: true,
        onSuccess: () => toast('Flight request deleted'),
    })
}

// Accommodation Request Modal
const accommodationModalOpen = ref(false)
const accommodationForm = useForm({
    hotel_preferences: '',
    check_in: '',
    check_out: '',
    room_type: 'double',
    rooms: 1,
    special_requests: '',
})

const roomTypeOptions = [
    { value: 'single', label: 'Single' },
    { value: 'double', label: 'Double' },
    { value: 'suite', label: 'Suite' },
]

const editingAccommId = ref(null)

function openAccommodationModal(acc = null) {
    if (acc) {
        editingAccommId.value = acc.id
        accommodationForm.hotel_preferences = acc.hotel_name || ''
        accommodationForm.check_in = acc.check_in ? acc.check_in.split('T')[0] : ''
        accommodationForm.check_out = acc.check_out ? acc.check_out.split('T')[0] : ''
        accommodationForm.room_type = (acc.room_type || 'double').toLowerCase()
        accommodationForm.rooms = 1
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
}

function submitAccommodationRequest() {
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
    if (!confirm('Delete this accommodation request?')) return
    router.delete(route('portal.services.accommodation.destroy', [props.guest.id, acc.id]), {
        preserveScroll: true,
        onSuccess: () => toast('Accommodation request deleted'),
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
}

function submitTransportRequest() {
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
    if (!confirm('Delete this transport request?')) return
    router.delete(route('portal.services.transport.destroy', [props.guest.id, trans.id]), {
        preserveScroll: true,
        onSuccess: () => toast('Transport request deleted'),
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
              <button class="portal-btn portal-btn-primary portal-btn-sm" :disabled="!c.name.trim() || compForm.processing" @click="saveCompanions">
                {{ compForm.processing ? 'Saving…' : 'Save' }}
              </button>
              <button v-if="c.name.trim()" class="portal-btn portal-btn-sm" @click="toggleEdit(ci)">Cancel</button>
              <button v-else class="portal-btn portal-btn-sm" @click="localCompanions.splice(ci, 1)">Cancel</button>
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
          <button class="portal-btn" @click="openFlightModal">
            Request Flight
          </button>
          <button class="portal-btn" @click="openAccommodationModal">
            Request Hotel
          </button>
          <button class="portal-btn" @click="openTransportModal">
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
              <span class="portal-badge" :class="flight.status?.name === 'confirmed' ? 'success' : 'warning'">
                {{ flight.status?.label || flight.status?.name || 'Pending' }}
              </span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
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
                <span style="color: var(--portal-text-2); font-size: 11px; font-weight: 500; text-transform: capitalize;">
                  {{ leg.dir === 'outbound' ? '→' : '←' }} {{ leg.dir.charAt(0).toUpperCase() + leg.dir.slice(1) }}
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
          <div v-if="flight.note" style="font-size: 12px; color: var(--portal-text-2); margin-top: 10px; font-style: italic;">
            "{{ flight.note }}"
          </div>
        </div>
      </div>

      <!-- Accommodation -->
      <div v-if="services.accommodation?.length" class="service-section">
        <div class="portal-label">Accommodation</div>
        <div v-for="acc in services.accommodation" :key="acc.id" class="service-item">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-size: 14px; font-weight: 500;">{{ acc.hotel_name }}</div>
              <div style="display: flex; flex-wrap: wrap; gap: 8px; font-size: 12px; color: var(--portal-text-2); margin-top: 4px;">
                <span>{{ formatDate(acc.check_in) }} – {{ formatDate(acc.check_out) }}</span>
                <span>· {{ acc.nights }} {{ acc.nights === 1 ? 'night' : 'nights' }}</span>
                <span v-if="acc.room_type">· {{ acc.room_type }}</span>
              </div>
              <div v-if="acc.notes" style="font-size: 12px; color: var(--portal-text-2); margin-top: 4px; font-style: italic;">
                "{{ acc.notes }}"
              </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <span class="portal-badge" :class="acc.status?.name === 'confirmed' ? 'success' : 'warning'">
                {{ acc.status?.label || acc.status?.name || 'Pending' }}
              </span>
              <template v-if="acc.source === 'portal' && !acc.fulfilled_by_id">
                <button class="portal-btn portal-btn-sm" @click="openAccommodationModal(acc)">Edit</button>
                <button class="portal-btn portal-btn-sm portal-btn-danger" @click="deleteAccommodation(acc)">Delete</button>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Transport -->
      <div v-if="services.transport?.length" class="service-section">
        <div class="portal-label">Transport</div>
        <div v-for="trans in services.transport" :key="trans.id" class="service-item">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-size: 14px; font-weight: 500;">{{ trans.type || 'Transport Request' }}</div>
              <div v-if="trans.pickup_location || trans.dropoff_location" style="font-size: 12px; color: var(--portal-text-2); margin-top: 2px;">
                {{ trans.pickup_location }} → {{ trans.dropoff_location }}
              </div>
              <div v-if="trans.notes && !trans.type" style="font-size: 12px; color: var(--portal-text-2); margin-top: 2px; font-style: italic;">
                "{{ trans.notes }}"
              </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <span class="portal-badge" :class="{
                success: trans.status?.name === 'confirmed',
                warning: trans.status?.name !== 'confirmed'
              }">
                {{ trans.status?.label || trans.status?.name || 'Pending' }}
              </span>
              <template v-if="trans.source === 'portal' && !trans.fulfilled_by_id">
                <button class="portal-btn portal-btn-sm" @click="openTransportModal(trans)">Edit</button>
                <button class="portal-btn portal-btn-sm portal-btn-danger" @click="deleteTransport(trans)">Delete</button>
              </template>
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
        <button type="button" class="portal-btn" :disabled="flightForm.processing" @click="closeFlightModal">Cancel</button>
        <button type="button" class="portal-btn portal-btn-primary" :disabled="flightForm.processing" @click="submitFlightRequest">
          <span v-if="flightForm.processing">{{ editingFlightId ? 'Updating...' : 'Submitting...' }}</span>
          <span v-else>{{ editingFlightId ? 'Update Request' : 'Submit Request' }}</span>
        </button>
      </template>
    </PortalModal>

    <!-- Accommodation Request Modal -->
    <PortalModal :open="accommodationModalOpen" :title="editingAccommId ? 'Edit Accommodation Request' : 'Request Accommodation'" :subtitle="editingAccommId ? 'Update your hotel preferences' : 'Submit your hotel requirements'" @close="closeAccommodationModal">
      <form @submit.prevent="submitAccommodationRequest">
        <PortalInput
          v-model="accommodationForm.hotel_preferences"
          label="Hotel Preferences"
          placeholder="Preferred hotel name or area..."
          help="Let us know your preferred hotel or area"
          :error="accommodationForm.errors.hotel_preferences"
        />

        <div class="portal-form-row">
          <div class="portal-form-group">
            <label class="portal-form-label">
              Check-In Date
              <span class="required">*</span>
            </label>
            <GmsDatePicker
              v-model="accommodationForm.check_in"
              placeholder="Select check-in date"
              dateFormat="Y-m-d"
              :minDate="new Date().toISOString().split('T')[0]"
              :error="!!accommodationForm.errors.check_in"
            />
            <p v-if="accommodationForm.errors.check_in" class="portal-form-error">{{ accommodationForm.errors.check_in }}</p>
          </div>
          <div class="portal-form-group">
            <label class="portal-form-label">
              Check-Out Date
              <span class="required">*</span>
            </label>
            <GmsDatePicker
              v-model="accommodationForm.check_out"
              placeholder="Select check-out date"
              dateFormat="Y-m-d"
              :minDate="accommodationForm.check_in || new Date().toISOString().split('T')[0]"
              :error="!!accommodationForm.errors.check_out"
            />
            <p v-if="accommodationForm.errors.check_out" class="portal-form-error">{{ accommodationForm.errors.check_out }}</p>
          </div>
        </div>

        <div class="portal-form-row">
          <PortalSelect
            v-model="accommodationForm.room_type"
            label="Room Type"
            :options="roomTypeOptions"
            value-key="value"
            label-key="label"
            required
            :error="accommodationForm.errors.room_type"
          />
          <PortalInput
            v-model.number="accommodationForm.rooms"
            type="number"
            label="Number of Rooms"
            :min="1"
            :max="10"
            required
            :error="accommodationForm.errors.rooms"
          />
        </div>

        <PortalTextarea
          v-model="accommodationForm.special_requests"
          label="Special Requests"
          placeholder="Any special requirements, accessibility needs, or preferences..."
          :rows="4"
          help="Optional: Let us know if you have any special requirements"
          :error="accommodationForm.errors.special_requests"
        />
      </form>

      <template #footer>
        <button type="button" class="portal-btn" :disabled="accommodationForm.processing" @click="closeAccommodationModal">Cancel</button>
        <button type="button" class="portal-btn portal-btn-primary" :disabled="accommodationForm.processing" @click="submitAccommodationRequest">
          <span v-if="accommodationForm.processing">{{ editingAccommId ? 'Updating...' : 'Submitting...' }}</span>
          <span v-else>{{ editingAccommId ? 'Update Request' : 'Submit Request' }}</span>
        </button>
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
        <button type="button" class="portal-btn" :disabled="transportForm.processing" @click="closeTransportModal">Cancel</button>
        <button type="button" class="portal-btn portal-btn-primary" :disabled="transportForm.processing" @click="submitTransportRequest">
          <span v-if="transportForm.processing">{{ editingTransportId ? 'Updating...' : 'Submitting...' }}</span>
          <span v-else>{{ editingTransportId ? 'Update Request' : 'Submit Request' }}</span>
        </button>
      </template>
    </PortalModal>
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
.portal-input:focus { border-color: var(--gms-maroon); box-shadow: 0 0 0 3px rgba(138,31,61,.06); }
</style>
