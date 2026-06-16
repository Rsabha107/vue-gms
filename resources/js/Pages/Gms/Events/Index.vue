<script setup>
import { ref, computed, inject, onMounted, onBeforeUnmount } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    events: { type: Array, default: () => [] },
    event:  { type: Object, default: () => ({}) },
    availableVenues: { type: Array, default: () => [] },
})

const toast = inject('toast')

// ── Local reactive events list ────────────────────────────────────
const localEvents = ref(props.events.map(e => ({ ...e })))

// ── Active venues computed ────────────────────────────────────────
const activeVenues = computed(() => {
    return props.availableVenues.filter(v => v.active_flag !== false && v.active_flag !== 0)
})

// ── Modal state ───────────────────────────────────────────────────
const eventModal = ref(false)
const editingEvent = ref(null)
const deleteModal = ref(false)
const deletingId = ref(null)
const actionsMenuOpen = ref(null)

const form = useForm({
    name: '',
    subtitle: '',
    location: '',
    venue_ids: [],
    date_start: '',
    date_end: '',
    logo: '🏆',
    active_flag: true,
})

function openNew() {
    editingEvent.value = null
    form.reset()
    form.logo = '🏆'
    form.active_flag = true
    form.venue_ids = []
    eventModal.value = true
}

function openEdit(evt) {
    editingEvent.value = evt
    form.name = evt.name
    form.subtitle = evt.subtitle ?? ''
    form.location = evt.location
    form.venue_ids = evt.venue_ids ?? []
    form.date_start = evt.date_start
    form.date_end = evt.date_end
    form.logo = evt.logo ?? '🏆'
    form.active_flag = evt.active_flag
    actionsMenuOpen.value = null
    eventModal.value = true
}

function toggleVenue(venueId) {
    const index = form.venue_ids.indexOf(venueId)
    if (index === -1) {
        form.venue_ids.push(venueId)
    } else {
        form.venue_ids.splice(index, 1)
    }
}

function saveEvent() {
    const payload = { ...form }

    if (editingEvent.value) {
        const idx = localEvents.value.findIndex(e => e.id === editingEvent.value.id)
        if (idx !== -1) {
            const selectedVenues = activeVenues.value.filter(v => form.venue_ids.includes(v.id))
            localEvents.value[idx] = { 
                ...localEvents.value[idx], 
                ...payload,
                venue: selectedVenues.map(v => v.name).join(', '),
                venues: selectedVenues.map(v => ({ id: v.id, name: v.name })),
                venue_ids: form.venue_ids
            }
        }

        form.put(route('gms.events.update', editingEvent.value.id), {
            onSuccess: () => { eventModal.value = false; toast('Event updated.') },
            onError: (errors) => {
                const firstError = Object.values(errors)[0]
                toast(firstError || 'Failed to save.', 'error')
            },
            preserveScroll: true,
        })
    } else {
        const selectedVenues = activeVenues.value.filter(v => form.venue_ids.includes(v.id))
        const newEvent = { 
            id: 'E' + Date.now(), 
            ...payload,
            venue: selectedVenues.map(v => v.name).join(', '),
            venues: selectedVenues.map(v => ({ id: v.id, name: v.name })),
            venue_ids: form.venue_ids
        }
        localEvents.value.unshift(newEvent)
        form.post(route('gms.events.store'), {
            onSuccess: () => { eventModal.value = false; toast('Event created.') },
            onError: (errors) => {
                const firstError = Object.values(errors)[0]
                toast(firstError || 'Failed to save.', 'error')
            },
            preserveScroll: true,
        })
    }
}

function openDelete(id) {
    deletingId.value = id
    deleteModal.value = true
    actionsMenuOpen.value = null
}

function confirmDelete() {
    localEvents.value = localEvents.value.filter(e => e.id !== deletingId.value)
    router.delete(route('gms.events.destroy', deletingId.value), {
        onSuccess: () => { deleteModal.value = false; toast('Event deleted.') },
        onError: () => toast('Failed to delete.', 'error'),
        preserveScroll: true,
    })
}

function toggleActionsMenu(eventId) {
    actionsMenuOpen.value = actionsMenuOpen.value === eventId ? null : eventId
}

// Close actions menu when clicking outside
function handleClickOutside(e) {
    if (actionsMenuOpen.value && !e.target.closest('.gms-menu-pop') && !e.target.closest('.gms-btn-icon')) {
        actionsMenuOpen.value = null
    }
}

const currentEvent = computed(() => props.event)

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="gms-view">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Events</h1>
        <p class="gms-view-subtitle">Manage events and their configurations</p>
      </div>
      <div class="gms-view-actions">
        <button class="gms-btn gms-btn-primary" @click="openNew">
          <GmsIcon name="plus" :size="14" />
          Create Event
        </button>
      </div>
    </div>

    <!-- Events grid -->
    <div class="gms-ev-grid">
      <div 
        v-for="evt in localEvents" 
        :key="evt.id" 
        class="gms-ev-card"
        :class="{ managing: evt.id === currentEvent.id }"
      >
        <div class="gms-ev-card-top">
          <div class="gms-ev-mk">{{ evt.logo || '🏆' }}</div>
          <div style="flex: 1; min-width: 0;">
            <div class="gms-ev-name">{{ evt.name }}</div>
            <div class="gms-ev-subtitle" v-if="evt.subtitle">{{ evt.subtitle }}</div>
          </div>
          
          <!-- Actions menu -->
          <div style="position: relative;">
            <button
              class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon"
              @click.stop="toggleActionsMenu(evt.id)"
              title="More actions"
            >
              <GmsIcon name="more-vertical" :size="14" />
            </button>

            <div v-if="actionsMenuOpen === evt.id" class="gms-menu-pop" @click.stop>
              <button class="gms-menu-item" @click="openEdit(evt)">
                <GmsIcon name="edit" :size="16" />
                Edit
              </button>
              <div class="gms-menu-sep"></div>
              <button class="gms-menu-item danger" @click="openDelete(evt.id)">
                <GmsIcon name="trash" :size="16" />
                Delete
              </button>
            </div>
          </div>
        </div>

        <div class="gms-ev-meta">
          <div class="gms-ev-mi">
            <GmsIcon name="map" :size="14" class="gms-ev-mi-icon" />
            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ evt.location }}</span>
          </div>
          <div class="gms-ev-mi">
            <GmsIcon name="building" :size="14" class="gms-ev-mi-icon" />
            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ evt.venue }}</span>
          </div>
          <div class="gms-ev-mi">
            <GmsIcon name="calendar" :size="14" class="gms-ev-mi-icon" />
            <span>{{ evt.dates }}</span>
          </div>
        </div>

        <div class="gms-ev-status">
          <span 
            class="gms-pill" 
            :class="evt.active_flag ? 'good' : ''"
          >
            {{ evt.active_flag ? 'Active' : 'Inactive' }}
          </span>
          <span v-if="evt.id === currentEvent.id" class="gms-pill maroon">Current</span>
        </div>
      </div>

      <!-- Add new card -->
      <button class="gms-ev-card gms-ev-add" @click="openNew">
        <div class="gms-ev-add-icon">
          <GmsIcon name="plus" :size="20" />
        </div>
        <div class="gms-ev-add-text">Create Event</div>
      </button>
    </div>
  </div>

  <!-- ── Event Modal ───────────────────────────────────────────── -->
  <GmsModal
    :open="eventModal"
    :title="editingEvent ? 'Edit Event' : 'Create Event'"
    @close="eventModal = false"
  >
    <div class="gms-form-grid">
      <div class="gms-field">
        <label class="gms-label">Event Name <span style="color: var(--gms-maroon);">*</span></label>
        <input v-model="form.name" class="gms-input" placeholder="e.g., Doha Cup '26" />
        <div v-if="form.errors.name" class="gms-field-error">{{ form.errors.name }}</div>
      </div>

      <div class="gms-field">
        <label class="gms-label">Subtitle</label>
        <input v-model="form.subtitle" class="gms-input" placeholder="e.g., International Football Championship" />
        <div v-if="form.errors.subtitle" class="gms-field-error">{{ form.errors.subtitle }}</div>
      </div>

      <div class="gms-field">
        <label class="gms-label">Location <span style="color: var(--gms-maroon);">*</span></label>
        <input v-model="form.location" class="gms-input" placeholder="e.g., Lusail, Qatar" />
        <div v-if="form.errors.location" class="gms-field-error">{{ form.errors.location }}</div>
      </div>

      <div class="gms-field" style="grid-column: 1 / -1;">
        <label class="gms-label">Venues <span style="color: var(--gms-maroon);">*</span></label>
        <div class="chip-pick">
          <button
            v-for="venue in activeVenues"
            :key="venue.id"
            type="button"
            class="pick-chip"
            :class="{ on: form.venue_ids.includes(venue.id) }"
            @click="toggleVenue(venue.id)"
          >
            <span class="pick-chip-check">
              <GmsIcon v-if="form.venue_ids.includes(venue.id)" name="check" :size="12" />
            </span>
            {{ venue.name }}
          </button>
        </div>
        <div v-if="form.errors.venue_ids" class="gms-field-error">{{ form.errors.venue_ids }}</div>
      </div>

      <div class="gms-field">
        <label class="gms-label">Start Date <span style="color: var(--gms-maroon);">*</span></label>
        <input v-model="form.date_start" type="date" class="gms-input" />
        <div v-if="form.errors.date_start" class="gms-field-error">{{ form.errors.date_start }}</div>
      </div>

      <div class="gms-field">
        <label class="gms-label">End Date <span style="color: var(--gms-maroon);">*</span></label>
        <input v-model="form.date_end" type="date" class="gms-input" />
        <div v-if="form.errors.date_end" class="gms-field-error">{{ form.errors.date_end }}</div>
      </div>

      <div class="gms-field">
        <label class="gms-label">Logo (Emoji)</label>
        <input v-model="form.logo" class="gms-input" placeholder="🏆" maxlength="10" />
        <div v-if="form.errors.logo" class="gms-field-error">{{ form.errors.logo }}</div>
      </div>

      <div class="gms-field">
        <label class="gms-label" style="display: flex; align-items: center; gap: 10px;">
          <input type="checkbox" v-model="form.active_flag" style="width: auto;" />
          <span>Active Event</span>
        </label>
      </div>
    </div>

    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="eventModal = false">Cancel</button>
      <button class="gms-btn gms-btn-primary" @click="saveEvent">
        {{ editingEvent ? 'Save Changes' : 'Create Event' }}
      </button>
    </template>
  </GmsModal>

  <!-- ── Delete Confirmation Modal ─────────────────────────────── -->
  <GmsModal
    :open="deleteModal"
    title="Delete Event"
    size="sm"
    @close="deleteModal = false"
  >
    <p style="margin-bottom: 16px;">Are you sure you want to delete this event? This action cannot be undone.</p>

    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="deleteModal = false">Cancel</button>
      <button class="gms-btn gms-btn-danger" @click="confirmDelete">Delete</button>
    </template>
  </GmsModal>
</template>
