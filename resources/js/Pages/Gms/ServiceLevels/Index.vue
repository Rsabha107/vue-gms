<script setup>
import { ref, computed, inject } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    tiers:  { type: Array, default: () => [] },
    guests: { type: Array, default: () => [] },
    event:  { type: Object, default: () => ({}) },
})

const toast = inject('toast')

// ── Local reactive tiers (mutations are optimistic until DB is wired) ─
const localTiers = ref(props.tiers.map(t => ({ ...t })))

// ── Modal state ───────────────────────────────────────────────────
const modalOpen   = ref(false)
const deleteModal = ref(false)
const editingTier = ref(null)
const deletingId  = ref(null)

const form = useForm({
    name:       '',
    color:      '#8a1f3d',
    bg:         '#fce7f3',
    facilities: '',
})

function openNew() {
    editingTier.value = null
    form.name       = ''
    form.color      = '#8a1f3d'
    form.bg         = '#fce7f3'
    form.facilities = ''
    modalOpen.value = true
}

function openEdit(tier) {
    editingTier.value = tier
    form.name       = tier.name
    form.color      = tier.color
    form.bg         = tier.bg ?? '#f3f4f6'
    form.facilities = (tier.facilities ?? []).join('\n')
    modalOpen.value = true
}

function openDelete(id) {
    deletingId.value  = id
    deleteModal.value = true
}

function saveTier() {
    const facilities = form.facilities.split('\n').map(f => f.trim()).filter(Boolean)
    if (editingTier.value) {
        const idx = localTiers.value.findIndex(t => t.id === editingTier.value.id)
        if (idx !== -1) {
            localTiers.value[idx] = { ...localTiers.value[idx], name: form.name, color: form.color, bg: form.bg, facilities }
        }
        form.put(route('gms.service-levels.update', editingTier.value.id), {
            onSuccess: () => { modalOpen.value = false; toast('Service level updated.') },
            onError: () => toast('Failed to save.', 'error'),
            preserveScroll: true,
        })
    } else {
        const id = 'T' + Date.now()
        localTiers.value.push({ id, name: form.name, color: form.color, bg: form.bg, facilities, rank: localTiers.value.length + 1 })
        form.post(route('gms.service-levels.store'), {
            onSuccess: () => { modalOpen.value = false; toast('Service level created.') },
            onError: () => toast('Failed to save.', 'error'),
            preserveScroll: true,
        })
    }
}

function confirmDelete() {
    localTiers.value = localTiers.value.filter(t => t.id !== deletingId.value)
    router.delete(route('gms.service-levels.destroy', deletingId.value), {
        onSuccess: () => { deleteModal.value = false; toast('Service level deleted.') },
        onError: () => toast('Failed to delete.', 'error'),
        preserveScroll: true,
    })
}

function guestCountFor(tierId) {
    return props.guests.filter(g => g.tier === tierId).length
}

function duplicateTier(tier) {
    const copy = { ...tier, id: 'T' + Date.now(), name: tier.name + ' (copy)', rank: localTiers.value.length + 1 }
    localTiers.value.push(copy)
    toast('Tier duplicated.')
}
</script>

<template>
  <div class="gms-view">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Service Levels</h1>
        <p class="gms-view-subtitle">Define VIP tiers and their facilities</p>
      </div>
      <div class="gms-view-actions">
        <button class="gms-btn gms-btn-primary" @click="openNew">
          <GmsIcon name="plus" :size="14" />
          New Tier
        </button>
      </div>
    </div>

    <div class="gms-grid-2" style="gap:16px;">
      <div
        v-for="tier in localTiers"
        :key="tier.id"
        class="gms-card"
        style="overflow:hidden;"
      >
        <!-- Tier colour bar -->
        <div :style="{ height: '5px', background: tier.color }" />

        <div class="gms-card-body">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div
                :style="{ width:'36px', height:'36px', background: tier.bg, borderRadius:'8px', display:'flex', alignItems:'center', justifyContent:'center', color: tier.color, fontWeight:'700', fontSize:'15px' }"
              >{{ tier.name[0] }}</div>
              <div>
                <div style="font-weight:700;font-size:15px;">{{ tier.name }}</div>
                <div style="font-size:12px;color:var(--gms-text-3);">Rank {{ tier.rank ?? '—' }}</div>
              </div>
            </div>
            <div style="display:flex;gap:4px;">
              <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" @click="duplicateTier(tier)" title="Duplicate">
                <GmsIcon name="copy" :size="13" />
              </button>
              <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" @click="openEdit(tier)" title="Edit">
                <GmsIcon name="edit" :size="13" />
              </button>
              <button class="gms-btn gms-btn-danger gms-btn-sm gms-btn-icon" @click="openDelete(tier.id)" title="Delete">
                <GmsIcon name="trash" :size="13" />
              </button>
            </div>
          </div>

          <!-- Guest count -->
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:14px;padding:8px 10px;background:var(--gms-surface-2);border-radius:6px;">
            <GmsIcon name="users" :size="13" style="color:var(--gms-text-3);" />
            <span style="font-size:12.5px;color:var(--gms-text-2);">
              <strong>{{ guestCountFor(tier.id) }}</strong> guests assigned
            </span>
          </div>

          <!-- Facilities -->
          <div>
            <div class="gms-section-title" style="margin-bottom:8px;">Facilities</div>
            <div v-if="tier.facilities?.length" style="display:flex;flex-wrap:wrap;gap:5px;">
              <span
                v-for="f in tier.facilities"
                :key="f"
                class="gms-pill"
                :style="{ background: tier.bg, color: tier.color }"
              >{{ f }}</span>
            </div>
            <div v-else style="font-size:12px;color:var(--gms-text-3);">No facilities defined</div>
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div v-if="!localTiers.length" class="gms-empty" style="grid-column:1/-1;">
        <div class="gms-empty-icon"><GmsIcon name="star" :size="38" /></div>
        <div class="gms-empty-title">No service levels defined</div>
        <div class="gms-empty-sub">Create tiers to assign to guests.</div>
      </div>
    </div>
  </div>

  <!-- Create / Edit modal -->
  <GmsModal :open="modalOpen" :title="editingTier ? 'Edit Service Level' : 'New Service Level'" @close="modalOpen = false">
    <div class="gms-form-grid">
      <div class="gms-field">
        <label class="gms-label">Name</label>
        <input v-model="form.name" class="gms-input" placeholder="e.g. Diamond" />
        <span v-if="form.errors.name" class="gms-error">{{ form.errors.name }}</span>
      </div>
      <div class="gms-field">
        <label class="gms-label">Primary Color</label>
        <div style="display:flex;gap:8px;align-items:center;">
          <input type="color" v-model="form.color" style="width:36px;height:36px;border:1px solid var(--gms-border);border-radius:6px;padding:2px;cursor:pointer;" />
          <input v-model="form.color" class="gms-input" style="font-family:var(--gms-font-mono);font-size:12px;" />
        </div>
      </div>
      <div class="gms-field">
        <label class="gms-label">Background Color</label>
        <div style="display:flex;gap:8px;align-items:center;">
          <input type="color" v-model="form.bg" style="width:36px;height:36px;border:1px solid var(--gms-border);border-radius:6px;padding:2px;cursor:pointer;" />
          <input v-model="form.bg" class="gms-input" style="font-family:var(--gms-font-mono);font-size:12px;" />
        </div>
      </div>
      <div class="gms-field gms-form-full">
        <label class="gms-label">Facilities (one per line)</label>
        <textarea v-model="form.facilities" class="gms-input gms-textarea" placeholder="VIP Lounge&#10;Chauffeur&#10;Hotel upgrade" rows="4" />
      </div>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="modalOpen = false">Cancel</button>
      <button class="gms-btn gms-btn-primary" :disabled="form.processing" @click="saveTier">
        {{ editingTier ? 'Save Changes' : 'Create Tier' }}
      </button>
    </template>
  </GmsModal>

  <!-- Delete confirm -->
  <GmsModal :open="deleteModal" title="Delete Service Level" size="sm" @close="deleteModal = false">
    <p style="font-size:13.5px;color:var(--gms-text-2);">
      This tier will be removed. Guests with this tier will need to be reassigned.
    </p>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="deleteModal = false">Cancel</button>
      <button class="gms-btn gms-btn-danger" @click="confirmDelete">Delete</button>
    </template>
  </GmsModal>
</template>
