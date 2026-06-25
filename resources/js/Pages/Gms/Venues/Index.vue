<script setup>
import { ref, inject, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'
import GmsBtn from '@/Components/Gms/GmsBtn.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    venues: { type: Array,  default: () => [] },
    event:  { type: Object, default: () => ({}) },
})

const toast = inject('toast')

const localVenues = ref(props.venues.map(v => ({ ...v })))
const activeTab = ref('all')

// ── Modal ─────────────────────────────────────────────────────────
const venueModal   = ref(false)
const editingVenue = ref(null)
const deleteModal  = ref(false)
const deletingId   = ref(null)

const form = useForm({ name: '', city: '', capacity: '', notes: '' })

function openNew() {
    editingVenue.value = null
    form.name = ''; form.city = ''; form.capacity = ''; form.notes = ''
    venueModal.value = true
}

function openEdit(v) {
    editingVenue.value = v
    form.name = v.name; form.city = v.city ?? ''; form.capacity = v.capacity ?? ''; form.notes = v.notes ?? ''
    venueModal.value = true
}

function save() {
    if (editingVenue.value) {
        const idx = localVenues.value.findIndex(x => x.id === editingVenue.value.id)
        if (idx !== -1) Object.assign(localVenues.value[idx], {
            name: form.name, city: form.city, capacity: Number(form.capacity), notes: form.notes,
        })
        form.put(route('gms.venues.update', editingVenue.value.id), {
            onSuccess: () => { venueModal.value = false; toast('Venue updated.') },
            onError:   () => toast('Failed to save.', 'error'),
            preserveScroll: true,
        })
    } else {
        form.post(route('gms.venues.store'), {
            onSuccess: (page) => {
                venueModal.value = false; toast('Venue created.')
                localVenues.value = page.props.venues ?? localVenues.value
            },
            onError: () => toast('Failed to save.', 'error'),
            preserveScroll: true,
        })
    }
}

function openDelete(id) { deletingId.value = id; deleteModal.value = true }

function confirmDelete() {
    localVenues.value = localVenues.value.filter(v => v.id !== deletingId.value)
    router.delete(route('gms.venues.destroy', deletingId.value), {
        onSuccess: () => { deleteModal.value = false; toast('Venue deleted.') },
        onError:   () => toast('Failed to delete.', 'error'),
        preserveScroll: true,
    })
}

function fmtCap(n) {
    if (!n) return '—'
    return Number(n).toLocaleString()
}

const filteredVenues = computed(() => {
    if (activeTab.value === 'event') {
        return localVenues.value.filter(v => v.in_current_event)
    }
    return localVenues.value
})

const eventVenuesCount = computed(() => {
    return localVenues.value.filter(v => v.in_current_event).length
})
</script>

<template>
  <div class="gms-view">

    <!-- Header -->
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Venues</h1>
        <p class="gms-view-subtitle">Stadiums &amp; sites available across all events. Matches and events reference these.</p>
      </div>
      <div class="gms-view-actions">
        <GmsBtn variant="primary" icon="plus" :iconSize="14" @click="openNew">New venue</GmsBtn>
      </div>
    </div>

    <!-- Segmented Control -->
    <div style="margin-bottom: 20px;">
      <div class="gms-seg">
        <button 
          :class="{ on: activeTab === 'all' }" 
          @click="activeTab = 'all'"
        >
          All Venues
          <span class="gms-seg-count">{{ localVenues.length }}</span>
        </button>
        <button 
          :class="{ on: activeTab === 'event' }" 
          @click="activeTab = 'event'"
        >
          {{ event?.name || 'Current Event' }}
          <span class="gms-seg-count">{{ eventVenuesCount }}</span>
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="gms-card">
      <div class="gms-card-body-0">
        <div class="gms-table-wrap">
          <table class="gms-table">
            <thead>
              <tr>
                <th>Venue</th>
                <th>Location</th>
                <th>Capacity</th>
                <th>Events</th>
                <th>Matches</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in filteredVenues" :key="v.id">
                <!-- Venue name + notes -->
                <td>
                  <div style="display:flex;align-items:center;gap:12px;">
                    <div class="vn-icon">
                      <GmsIcon name="map" :size="16" />
                    </div>
                    <div>
                      <div class="vn-name">{{ v.name }}</div>
                      <div v-if="v.notes" class="vn-notes">{{ v.notes }}</div>
                    </div>
                  </div>
                </td>
                <!-- Location -->
                <td><span class="vn-city">{{ v.city }}</span></td>
                <!-- Capacity -->
                <td><span class="gms-small">{{ fmtCap(v.capacity) }}</span></td>
                <!-- Events count -->
                <td>
                  <span class="vn-count" :class="{ has: v.events_count > 0 }">{{ v.events_count }}</span>
                </td>
                <!-- Matches count -->
                <td>
                  <span class="vn-count" :class="{ has: v.matches_count > 0 }">{{ v.matches_count }}</span>
                </td>
                <!-- Actions -->
                <td>
                  <div class="gms-table-actions">
                    <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" title="Edit" @click="openEdit(v)">
                      <GmsIcon name="edit" :size="13" />
                    </button>
                    <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" title="Delete" @click="openDelete(v.id)">
                      <GmsIcon name="trash" :size="13" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!filteredVenues.length">
                <td colspan="6">
                  <div class="gms-empty">
                    <div class="gms-empty-title">
                      {{ activeTab === 'event' ? 'No venues for this event' : 'No venues yet' }}
                    </div>
                    <div v-if="activeTab === 'event'" class="gms-empty-text">
                      Venues can be associated with events via Events → Edit Event → Select Venues
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Add / Edit modal ── -->
  <GmsModal
    :open="venueModal"
    :title="editingVenue ? 'Edit venue' : 'New venue'"
    @close="venueModal = false"
  >
    <div style="display:flex;flex-direction:column;gap:16px;">
      <div class="gms-field">
        <label class="gms-label">Venue name *</label>
        <input v-model="form.name" class="gms-input" placeholder="e.g. Lusail Stadium" />
        <span v-if="form.errors.name" class="gms-error">{{ form.errors.name }}</span>
      </div>
      <div class="gms-form-grid">
        <div class="gms-field">
          <label class="gms-label">City / location</label>
          <input v-model="form.city" class="gms-input" placeholder="e.g. Lusail" />
        </div>
        <div class="gms-field">
          <label class="gms-label">Capacity</label>
          <input v-model="form.capacity" type="number" min="0" class="gms-input" placeholder="88966" />
        </div>
      </div>
      <div class="gms-field">
        <label class="gms-label">Notes</label>
        <textarea v-model="form.notes" class="gms-input gms-textarea" rows="3" placeholder="Short description shown in the table." />
      </div>
    </div>

    <template #footer>
      <GmsBtn variant="ghost" @click="venueModal = false">Cancel</GmsBtn>
      <GmsBtn variant="primary" :disabled="form.processing || !form.name" @click="save">
        {{ editingVenue ? 'Save changes' : 'Create venue' }}
      </GmsBtn>
    </template>
  </GmsModal>

  <!-- ── Delete confirm ── -->
  <GmsModal :open="deleteModal" title="Delete venue" size="sm" @close="deleteModal = false">
    <p style="font-size:13.5px;color:var(--gms-text-2);">
      This venue will be removed. Events and matches that reference it may be affected.
    </p>
    <template #footer>
      <GmsBtn variant="ghost" @click="deleteModal = false">Cancel</GmsBtn>
      <GmsBtn variant="danger" @click="confirmDelete">Delete</GmsBtn>
    </template>
  </GmsModal>
</template>
