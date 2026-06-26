<script setup>
import { ref, inject } from 'vue'
import { useForm } from '@inertiajs/vue3'
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
    services: { type: Object, default: () => ({}) },
})

const statusLabels = {
    not_invited: 'Not Invited',
    invited: 'Invited',
    accepted: 'Accepted',
    declined: 'Declined',
    confirmed: 'Confirmed',
}

const serviceStatusLabels = {
    new: 'Pending',
    pending: 'Pending',
    confirmed: 'Confirmed',
    cancelled: 'Cancelled',
    change: 'Change Request',
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
    passengers: 1,
    special_requests: '',
})

const classOptions = [
    { value: 'economy', label: 'Economy' },
    { value: 'business', label: 'Business' },
    { value: 'first', label: 'First Class' },
]

function openFlightModal() {
    flightModalOpen.value = true
}

function closeFlightModal() {
    flightModalOpen.value = false
    flightForm.reset()
}

function submitFlightRequest() {
    flightForm.post(route('portal.services.flights.store', props.guest.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeFlightModal()
            toast('Flight request submitted successfully')
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Failed to submit flight request. Please try again.', 'error')
        }
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

function openAccommodationModal() {
    accommodationModalOpen.value = true
}

function closeAccommodationModal() {
    accommodationModalOpen.value = false
    accommodationForm.reset()
}

function submitAccommodationRequest() {
    accommodationForm.post(route('portal.services.accommodation.store', props.guest.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeAccommodationModal()
            toast('Accommodation request submitted successfully')
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Failed to submit accommodation request. Please try again.', 'error')
        }
    })
}

// Transport Request Modal
const transportModalOpen = ref(false)
const transportForm = useForm({
    transport_type: 'airport_transfer',
    pickup_location: '',
    dropoff_location: '',
    date: '',
    time: '',
    passengers: 1,
    special_requests: '',
})

const transportTypeOptions = [
    { value: 'airport_transfer', label: 'Airport Transfer' },
    { value: 'point_to_point', label: 'Point-to-Point' },
    { value: 'daily_driver', label: 'Daily Driver' },
]

function openTransportModal() {
    transportModalOpen.value = true
}

function closeTransportModal() {
    transportModalOpen.value = false
    transportForm.reset()
}

function submitTransportRequest() {
    transportForm.post(route('portal.services.transport.store', props.guest.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeTransportModal()
            toast('Transport request submitted successfully')
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0]
            toast(firstError || 'Failed to submit transport request. Please try again.', 'error')
        }
    })
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
              success: invitation.status?.id === 'confirmed' || invitation.status?.id === 'accepted',
              warning: invitation.status?.id === 'invited',
              info: invitation.status?.id === 'not_invited'
            }">
              {{ statusLabels[invitation.status?.id] || invitation.status?.name }}
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
          <!-- Header with code, status, and pax -->
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <div style="font-size: 14px; font-weight: 500;">{{ flight.code }}</div>
              <span class="portal-badge" :class="{
                success: flight.status === 'confirmed',
                warning: flight.status === 'new' || flight.status === 'pending'
              }">
                {{ serviceStatusLabels[flight.status] || flight.status }}
              </span>
            </div>
            <div style="font-size: 12px; color: var(--portal-text-2);">
              {{ flight.pax }} {{ flight.pax === 1 ? 'passenger' : 'passengers' }}
            </div>
          </div>
          
          <!-- Flight legs in grid layout -->
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px;">
            <div v-for="leg in flight.legs" :key="leg.id" 
                 style="background: var(--portal-bg); border: 1px solid var(--portal-border); border-radius: 6px; padding: 10px;">
              <!-- Direction label -->
              <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                <span style="color: var(--portal-text-2); font-size: 11px; font-weight: 500; text-transform: capitalize;">
                  {{ leg.dir === 'outbound' ? '→' : '←' }} {{ leg.dir.charAt(0).toUpperCase() + leg.dir.slice(1) }}
                </span>
              </div>
              
              <!-- Route -->
              <div style="font-size: 13px; font-weight: 500; color: var(--portal-text); margin-bottom: 6px;">
                {{ leg.from_city }} → {{ leg.to_city }}
              </div>
              
              <!-- Date, time, class in compact layout -->
              <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 12px; color: var(--portal-text-2);">
                <span>{{ formatDate(leg.date) }}</span>
                <span v-if="leg.dep">• {{ leg.dep }}</span>
                <span v-if="leg.cls">• {{ leg.cls.charAt(0).toUpperCase() + leg.cls.slice(1) }}</span>
              </div>
            </div>
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
              <div style="font-size: 12px; color: var(--portal-text-2); margin-top: 2px;">
                {{ formatDate(acc.check_in) }} – {{ formatDate(acc.check_out) }} · {{ acc.nights }} {{ acc.nights === 1 ? 'night' : 'nights' }}
              </div>
            </div>
            <span class="portal-badge" :class="{
              success: acc.status?.id === 'confirmed',
              warning: acc.status?.id === 'new' || acc.status?.id === 'pending'
            }">
              {{ serviceStatusLabels[acc.status?.id] || acc.status?.name }}
            </span>
          </div>
        </div>
      </div>

      <!-- Transport -->
      <div v-if="services.transport?.length" class="service-section">
        <div class="portal-label">Transport</div>
        <div v-for="trans in services.transport" :key="trans.id" class="service-item">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-size: 14px; font-weight: 500;">{{ trans.type }}</div>
              <div style="font-size: 12px; color: var(--portal-text-2); margin-top: 2px;">
                {{ trans.pickup_location }} → {{ trans.dropoff_location }}
              </div>
            </div>
            <span class="portal-badge" :class="{
              success: trans.status?.id === 'confirmed',
              warning: trans.status?.id === 'new' || trans.status?.id === 'pending'
            }">
              {{ serviceStatusLabels[trans.status?.id] || trans.status?.name }}
            </span>
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
    <PortalModal :open="flightModalOpen" title="Request Flight" subtitle="Submit your flight requirements" @close="closeFlightModal">
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
            <PortalInput
              v-model.number="flightForm.passengers"
              type="number"
              label="Passengers"
              :min="1"
              :max="9"
              required
              :error="flightForm.errors.passengers"
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
          <span v-if="flightForm.processing">Submitting...</span>
          <span v-else>Submit Request</span>
        </button>
      </template>
    </PortalModal>

    <!-- Accommodation Request Modal -->
    <PortalModal :open="accommodationModalOpen" title="Request Accommodation" subtitle="Submit your hotel requirements" @close="closeAccommodationModal">
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
          <span v-if="accommodationForm.processing">Submitting...</span>
          <span v-else>Submit Request</span>
        </button>
      </template>
    </PortalModal>

    <!-- Transport Request Modal -->
    <PortalModal :open="transportModalOpen" title="Request Transport" subtitle="Submit your transport requirements" @close="closeTransportModal">
      <form @submit.prevent="submitTransportRequest">
        <PortalSelect
          v-model="transportForm.transport_type"
          label="Transport Type"
          :options="transportTypeOptions"
          value-key="value"
          label-key="label"
          required
          :error="transportForm.errors.transport_type"
        />

        <div class="portal-form-row">
          <PortalInput
            v-model="transportForm.pickup_location"
            label="Pickup Location"
            placeholder="e.g., Hamad International Airport"
            required
            :error="transportForm.errors.pickup_location"
          />
          <PortalInput
            v-model="transportForm.dropoff_location"
            label="Dropoff Location"
            placeholder="e.g., Hotel name or venue"
            required
            :error="transportForm.errors.dropoff_location"
          />
        </div>

        <div class="portal-form-row">
          <div class="portal-form-group">
            <label class="portal-form-label">
              Date
              <span class="required">*</span>
            </label>
            <GmsDatePicker
              v-model="transportForm.date"
              placeholder="Select transport date"
              dateFormat="Y-m-d"
              :minDate="new Date().toISOString().split('T')[0]"
              :error="!!transportForm.errors.date"
            />
            <p v-if="transportForm.errors.date" class="portal-form-error">{{ transportForm.errors.date }}</p>
          </div>
          <div class="portal-form-group">
            <label class="portal-form-label">
              Time
              <span class="required">*</span>
            </label>
            <GmsTimePicker
              v-model="transportForm.time"
              placeholder="Select transport time"
              :error="!!transportForm.errors.time"
            />
            <p v-if="transportForm.errors.time" class="portal-form-error">{{ transportForm.errors.time }}</p>
          </div>
        </div>

        <PortalInput
          v-model.number="transportForm.passengers"
          type="number"
          label="Number of Passengers"
          :min="1"
          :max="15"
          required
          :error="transportForm.errors.passengers"
        />

        <PortalTextarea
          v-model="transportForm.special_requests"
          label="Special Requests"
          placeholder="Any vehicle preferences, accessibility needs, or other requirements..."
          :rows="4"
          help="Optional: Let us know if you have any special requirements"
          :error="transportForm.errors.special_requests"
        />
      </form>

      <template #footer>
        <button type="button" class="portal-btn" :disabled="transportForm.processing" @click="closeTransportModal">Cancel</button>
        <button type="button" class="portal-btn portal-btn-primary" :disabled="transportForm.processing" @click="submitTransportRequest">
          <span v-if="transportForm.processing">Submitting...</span>
          <span v-else>Submit Request</span>
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
</style>
