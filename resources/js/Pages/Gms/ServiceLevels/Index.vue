<script setup>
import { ref, computed, inject, onMounted, onUnmounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'
import GmsBtn from '@/Components/Gms/GmsBtn.vue'
import GmsMiniStat from '@/Components/Gms/GmsMiniStat.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    tiers:  { type: Array,  default: () => [] },
    guests: { type: Array,  default: () => [] },
    event:  { type: Object, default: () => ({}) },
})

const toast = inject('toast')

// ── Constants ─────────────────────────────────────────────────────
const CORE_MODULES = [
    { id: 'flights',   name: 'Flights',            icon: 'plane',    slug: 'flight',    desc: 'Inbound & outbound air travel.'   },
    { id: 'accomm',    name: 'Accommodation',       icon: 'building', slug: 'hotel',     desc: 'Hotel booking for the stay.'      },
    { id: 'seating',   name: 'Seating',             icon: 'ticket',   slug: 'seat',      desc: 'Match-day VIP tribune seat.'      },
    { id: 'transport', name: 'Transport',           icon: 'car',      slug: 'transport', desc: 'Chauffeured ground transport.'    },
    { id: 'arrival',   name: 'Arrival & Departure', icon: 'arrivals', slug: 'ad',        desc: 'Airport protocol & lounge.'       },
]

const BADGE_COLORS = [
    '#7c3aed', '#d97706', '#6b7280', '#dc2626', '#2563eb',
    '#16a34a', '#ea580c', '#1e40af',
]

const ICON_OPTIONS = [
    'plane','building','ticket','car','arrivals','map',
    'users','mail','bell','calendar','clock','trophy','star','tag',
]

const DEFAULT_MODULES = {
    T1: ['flights','accomm','seating','transport','arrival'],
    T2: ['flights','accomm','seating','transport','arrival'],
    T3: ['accomm','seating','transport','arrival'],
    T4: ['seating','transport'],
    T5: ['seating'],
}

const DEFAULT_DESCS = {
    T1: 'Full state protocol with every facility included.',
    T2: 'Full VVIP programme — every facility included.',
    T3: 'Premium hospitality with travel & ground transport.',
    T4: 'Match-day seating only — no travel arrangements.',
    T5: 'Press access with match-day seating.',
}

function bgFor(color) {
    const map = {
        '#7c3aed':'#ede9fe','#d97706':'#fef3c7','#6b7280':'#f3f4f6',
        '#dc2626':'#fce7f3','#2563eb':'#dbeafe','#16a34a':'#dcfce7',
        '#ea580c':'#ffedd5','#1e40af':'#dbeafe','#0ea5e9':'#e0f2fe',
        '#8a1f3d':'#fce7f3','#c4973a':'#fef3c7','#8b5cf6':'#ede9fe',
    }
    return map[color] ?? (color + '1a')
}

// ── Local state ───────────────────────────────────────────────────
const localTiers = ref(props.tiers.map(t => ({
    ...t,
    description: t.description ?? DEFAULT_DESCS[t.id] ?? '',
    modules: t.modules ?? DEFAULT_MODULES[t.id] ?? [],
})))

const localFacilities = ref(CORE_MODULES.map(m => ({ ...m, type: 'core' })))

const activeTab        = ref('levels')
const actionsMenuOpen  = ref(null)

// ── Stats ─────────────────────────────────────────────────────────
const assignedGuests   = computed(() => props.guests.filter(g => localTiers.value.some(t => t.id === g.tier)).length)
const unassignedGuests = computed(() => props.guests.length - assignedGuests.value)
const coreFacCount     = computed(() => localFacilities.value.filter(f => f.type === 'core').length)
const customFacCount   = computed(() => localFacilities.value.filter(f => f.type === 'custom').length)
const levelsUsing      = computed(() => {
    const used = new Set(localTiers.value.flatMap(t => t.modules ?? []))
    return localFacilities.value.filter(f => used.has(f.id)).length
})

function guestCountFor(id) {
    return props.guests.filter(g => g.tier === id).length
}
function registryPct(id) {
    return props.guests.length ? Math.round(guestCountFor(id) / props.guests.length * 100) : 0
}
function levelsForFac(id) {
    return localTiers.value.filter(t => (t.modules ?? []).includes(id)).length
}
function guestsForFac(id) {
    const tids = new Set(localTiers.value.filter(t => (t.modules ?? []).includes(id)).map(t => t.id))
    return props.guests.filter(g => tids.has(g.tier)).length
}

// ── Tier CRUD ─────────────────────────────────────────────────────
const tierModal   = ref(false)
const editingTier = ref(null)
const deleteModal = ref(false)
const deletingId  = ref(null)

const form = useForm({ name: '', color: '#dc2626', description: '', modules: [], facilities: [] })
const newFacility = ref('')

function toggleModule(id) {
    const i = form.modules.indexOf(id)
    if (i === -1) form.modules.push(id)
    else form.modules.splice(i, 1)
}

function addFacility() {
    if (newFacility.value.trim()) {
        form.facilities.push(newFacility.value.trim())
        newFacility.value = ''
    }
}

function removeFacility(index) {
    form.facilities.splice(index, 1)
}

function openNew() {
    editingTier.value = null
    form.name = ''; form.color = '#dc2626'; form.description = ''; form.modules = []; form.facilities = []
    newFacility.value = ''
    tierModal.value = true
}

function openEdit(tier) {
    editingTier.value = tier
    form.name = tier.name
    form.color = tier.color
    form.description = tier.description ?? ''
    form.modules = [...(tier.modules ?? [])]
    form.facilities = [...(tier.facilities ?? [])]
    newFacility.value = ''
    tierModal.value = true
    actionsMenuOpen.value = null
}

function openDelete(id) {
    deletingId.value = id
    deleteModal.value = true
    actionsMenuOpen.value = null
}

function saveTier() {
    const bg = bgFor(form.color)
    if (editingTier.value) {
        const idx = localTiers.value.findIndex(t => t.id === editingTier.value.id)
        if (idx !== -1) Object.assign(localTiers.value[idx], {
            name: form.name, color: form.color, bg,
            description: form.description, modules: [...form.modules],
            facilities: [...form.facilities],
        })
        form.put(route('gms.service-levels.update', editingTier.value.id), {
            onSuccess: () => { tierModal.value = false; toast('Service level updated.') },
            onError:   () => toast('Failed to save.', 'error'), preserveScroll: true,
        })
    } else {
        const id = 'T' + Date.now()
        localTiers.value.push({ id, name: form.name, color: form.color, bg, description: form.description, modules: [...form.modules], facilities: [...form.facilities], rank: localTiers.value.length + 1 })
        form.post(route('gms.service-levels.store'), {
            onSuccess: () => { tierModal.value = false; toast('Service level created.') },
            onError:   () => toast('Failed to save.', 'error'), preserveScroll: true,
        })
    }
}

function confirmDelete() {
    localTiers.value = localTiers.value.filter(t => t.id !== deletingId.value)
    router.delete(route('gms.service-levels.destroy', deletingId.value), {
        onSuccess: () => { deleteModal.value = false; toast('Service level deleted.') },
        onError:   () => toast('Failed to delete.', 'error'), preserveScroll: true,
    })
}

function duplicateTier(tier) {
    localTiers.value.push({
        ...tier, id: 'T' + Date.now(), name: tier.name + ' (copy)',
        modules: [...(tier.modules ?? [])], 
        facilities: [...(tier.facilities ?? [])],
        rank: localTiers.value.length + 1,
    })
    actionsMenuOpen.value = null
    toast('Service level duplicated.')
}

// ── Facility CRUD ─────────────────────────────────────────────────
const facilityModal   = ref(false)
const editingFacility = ref(null)
const facForm = useForm({ name: '', description: '', icon: 'tag' })

function openNewFacility() {
    editingFacility.value = null
    facForm.name = ''; facForm.description = ''; facForm.icon = 'tag'
    facilityModal.value = true
}

function openEditFacility(fac) {
    editingFacility.value = fac
    facForm.name = fac.name; facForm.description = fac.desc ?? ''; facForm.icon = fac.icon
    facilityModal.value = true
}

function saveFacility() {
    if (editingFacility.value) {
        const idx = localFacilities.value.findIndex(f => f.id === editingFacility.value.id)
        if (idx !== -1) Object.assign(localFacilities.value[idx], { name: facForm.name, desc: facForm.description, icon: facForm.icon })
        toast('Facility updated.')
    } else {
        localFacilities.value.push({
            id: 'fac-' + Date.now(), name: facForm.name, desc: facForm.description,
            icon: facForm.icon, slug: facForm.name.toLowerCase().replace(/\s+/g, '-'), type: 'custom',
        })
        toast('Facility created.')
    }
    facilityModal.value = false
}

// ── Click-outside ─────────────────────────────────────────────────
function handleClickOutside(e) {
    if (!e.target.closest('.sl-actions-wrap')) actionsMenuOpen.value = null
}
onMounted(()  => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <div class="gms-view">

    <!-- Header -->
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Service Levels</h1>
        <p class="gms-view-subtitle">Each level bundles a set of facilities. Assign one to every guest — extras can still be added per guest.</p>
      </div>
      <div class="gms-view-actions">
        <GmsBtn variant="primary" icon="plus" :iconSize="14" @click="activeTab === 'levels' ? openNew() : openNewFacility()">
          {{ activeTab === 'levels' ? 'New service level' : 'New facility' }}
        </GmsBtn>
      </div>
    </div>

    <!-- Tabs -->
    <div class="gms-seg" style="width:fit-content;margin-bottom:22px;">
      <button :class="{ on: activeTab === 'levels' }" @click="activeTab = 'levels'">Service levels</button>
      <button :class="{ on: activeTab === 'facilities' }" @click="activeTab = 'facilities'">Facilities · {{ localFacilities.length }}</button>
    </div>

    <!-- Stats — levels tab -->
    <div v-if="activeTab === 'levels'" class="gms-stats">
      <GmsMiniStat :value="localTiers.length"       label="Service levels" />
      <GmsMiniStat :value="assignedGuests"           label="Guests assigned"        color="#15803d" />
      <GmsMiniStat :value="unassignedGuests"         label="Unassigned"             color="#d97706" />
      <GmsMiniStat :value="localFacilities.length"   label="Facilities tracked"     color="#2563eb" />
    </div>

    <!-- Stats — facilities tab -->
    <div v-else class="gms-stats">
      <GmsMiniStat :value="localFacilities.length"   label="Facilities" />
      <GmsMiniStat :value="coreFacCount"             label="Core (module-linked)" />
      <GmsMiniStat :value="customFacCount"           label="Custom"               color="#6b7280" />
      <GmsMiniStat :value="levelsUsing"              label="Service levels using them" color="#15803d" />
    </div>

    <!-- ── Service Levels grid ── -->
    <div v-if="activeTab === 'levels'" class="sl-grid">

      <div v-for="tier in localTiers" :key="tier.id" class="sl-tier-card">

        <!-- Header: pill + guests count + actions -->
        <div class="sl-tier-head">
          <div class="sl-tier-head-left">
            <span class="sl-tier-pill" :style="{ background: tier.bg ?? bgFor(tier.color), color: tier.color }">
              {{ tier.name }}
            </span>
            <span class="sl-tier-guests">{{ guestCountFor(tier.id) }} guests</span>
          </div>
          <div class="sl-actions-wrap">
            <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" @click.stop="actionsMenuOpen = actionsMenuOpen === tier.id ? null : tier.id">
              <GmsIcon name="more-vertical" :size="14" />
            </button>
            <div v-if="actionsMenuOpen === tier.id" class="sl-actions-menu">
              <button @click="duplicateTier(tier)"><GmsIcon name="copy" :size="13" /> Duplicate</button>
              <button @click="openEdit(tier)"><GmsIcon name="edit" :size="13" /> Edit</button>
              <button class="danger" @click="openDelete(tier.id)"><GmsIcon name="trash" :size="13" /> Delete</button>
            </div>
          </div>
        </div>

        <!-- Description -->
        <p class="sl-desc">{{ tier.description || '—' }}</p>

        <!-- Amenities preview -->
        <div v-if="(tier.facilities ?? []).length > 0" style="margin-top:12px;margin-bottom:16px;">
          <div style="font-size:11px;font-weight:600;color:var(--gms-text-3);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Amenities</div>
          <div style="display:flex;flex-wrap:wrap;gap:4px;">
            <span
              v-for="(fac, idx) in (tier.facilities ?? [])" :key="idx"
              class="gms-pill"
              :style="{ background: tier.bg ?? bgFor(tier.color), color: tier.color, fontSize: '11px' }"
            >{{ fac }}</span>
          </div>
        </div>

        <!-- Service module chips: all 5, colored if included, grey if not -->
        <div class="sl-chips">
          <span
            v-for="m in CORE_MODULES" :key="m.id"
            class="sl-chip"
            :class="(tier.modules ?? []).includes(m.id) ? 'on' : 'off'"
            :style="(tier.modules ?? []).includes(m.id) ? { background: tier.bg ?? bgFor(tier.color), color: tier.color } : {}"
          >
            <GmsIcon :name="m.icon" :size="11" />{{ m.name }}
          </span>
        </div>

        <!-- Progress bar -->
        <div class="sl-bar-section">
          <div class="sl-bar-labels">
            <span>{{ registryPct(tier.id) }}% of registry</span>
            <span>{{ (tier.modules ?? []).length }} services · {{ (tier.facilities ?? []).length }} amenities</span>
          </div>
          <div class="sl-bar">
            <div class="sl-bar-fill" :style="{ width: registryPct(tier.id) + '%', background: tier.color }" />
          </div>
        </div>
      </div>

      <!-- Add new card -->
      <div class="sl-add-card" @click="openNew">
        <GmsIcon name="plus" :size="20" style="color:var(--gms-text-3);" />
        <span>Add service level</span>
      </div>
    </div>

    <!-- ── Facilities grid ── -->
    <div v-else class="sl-grid">

      <div v-for="fac in localFacilities" :key="fac.id" class="sl-fac-card">
        <div class="sl-fac-top">
          <div class="sl-fac-icon-wrap">
            <div class="sl-fac-icon">
              <GmsIcon :name="fac.icon" :size="18" />
            </div>
            <div class="sl-fac-info">
              <div class="sl-fac-name">{{ fac.name }}</div>
              <span class="sl-fac-slug">{{ fac.slug }}</span>
            </div>
          </div>
          <span v-if="fac.type === 'core'" class="sl-fac-badge">Core</span>
          <div v-else class="sl-actions-wrap">
            <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" @click.stop="actionsMenuOpen = actionsMenuOpen === 'f'+fac.id ? null : 'f'+fac.id">
              <GmsIcon name="more-vertical" :size="14" />
            </button>
            <div v-if="actionsMenuOpen === 'f'+fac.id" class="sl-actions-menu">
              <button @click="openEditFacility(fac)"><GmsIcon name="edit" :size="13" /> Edit</button>
            </div>
          </div>
        </div>
        <p class="sl-fac-desc">{{ fac.desc }}</p>
        <div class="sl-fac-divider" />
        <div class="sl-fac-footer">
          <span class="sl-fac-stat"><GmsIcon name="star" :size="12" /> {{ levelsForFac(fac.id) }} {{ levelsForFac(fac.id) === 1 ? 'level' : 'levels' }}</span>
          <span class="sl-fac-stat"><GmsIcon name="users" :size="12" /> {{ guestsForFac(fac.id) }} guests</span>
        </div>
      </div>

      <!-- Add facility card -->
      <div class="sl-add-card" @click="openNewFacility">
        <GmsIcon name="plus" :size="20" style="color:var(--gms-text-3);" />
        <span>Add facility</span>
      </div>
    </div>

  </div>

  <!-- ── New / Edit Service Level Modal ── -->
  <GmsModal :open="tierModal" :title="editingTier ? 'Edit service level' : 'New service level'" @close="tierModal = false">

    <!-- Live preview -->
    <div class="sl-modal-preview">
      <span class="sl-preview-pill" :style="{ background: bgFor(form.color), color: form.color }">
        {{ form.name || 'UNTITLED' }}
      </span>
      <div style="display:flex;gap:12px;font-size:12px;color:var(--gms-text-3);">
        <span class="sl-preview-count">{{ form.modules.length }} {{ form.modules.length === 1 ? 'service' : 'services' }}</span>
        <span class="sl-preview-count">{{ form.facilities.length }} {{ form.facilities.length === 1 ? 'amenity' : 'amenities' }}</span>
      </div>
    </div>

    <!-- Name + Badge colour (side by side) -->
    <div class="sl-name-color-row">
      <div class="gms-field" style="flex:1;min-width:0;">
        <label class="gms-label">Name *</label>
        <input v-model="form.name" class="gms-input" placeholder="e.g. Diamond" />
      </div>
      <div class="gms-field">
        <label class="gms-label">Badge colour</label>
        <div class="sl-color-grid">
          <button
            v-for="c in BADGE_COLORS" :key="c"
            class="sl-color-dot"
            :style="{ background: c, boxShadow: form.color === c ? `0 0 0 2.5px white, 0 0 0 4.5px ${c}` : 'none' }"
            @click="form.color = c"
          />
        </div>
      </div>
    </div>

    <!-- Description -->
    <div class="gms-field" style="margin-top:14px;">
      <label class="gms-label">Description</label>
      <input v-model="form.description" class="gms-input" placeholder="Short summary shown on the card" />
    </div>

    <!-- Included modules -->
    <div style="margin-top:20px;">
      <div class="sl-section-head">Service modules <span class="sl-section-hint">· tap to toggle</span></div>
      <div class="sl-fac-toggles">
        <button
          v-for="m in CORE_MODULES" :key="m.id"
          class="sl-fac-toggle"
          :class="{ on: form.modules.includes(m.id) }"
          type="button"
          @click="toggleModule(m.id)"
        >
          <div class="sl-toggle-icon">
            <GmsIcon :name="m.icon" :size="15" />
          </div>
          <div class="sl-toggle-info">
            <div class="sl-toggle-name">{{ m.name }}</div>
            <div class="sl-toggle-status">{{ form.modules.includes(m.id) ? 'Included' : 'Excluded' }}</div>
          </div>
          <div class="sl-toggle-check">
            <GmsIcon v-if="form.modules.includes(m.id)" name="check" :size="11" />
          </div>
        </button>
      </div>
    </div>

    <!-- Tier facilities/amenities -->
    <div style="margin-top:20px;">
      <div class="sl-section-head">Amenities / Perks <span class="sl-section-hint">· shown on guest profile</span></div>
      <p style="font-size:12px;color:var(--gms-text-3);margin-bottom:10px;">Add facility names that describe what's included (e.g., "VIP Lounge", "Private Driver")</p>
      
      <!-- Facility chips -->
      <div v-if="form.facilities.length > 0" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;">
        <span
          v-for="(fac, idx) in form.facilities" :key="idx"
          class="gms-pill"
          :style="{ background: bgFor(form.color), color: form.color, paddingRight: '6px', display: 'flex', alignItems: 'center', gap: '6px' }"
        >
          {{ fac }}
          <button
            type="button"
            @click="removeFacility(idx)"
            style="background:none;border:none;padding:2px;cursor:pointer;display:flex;align-items:center;opacity:0.7;"
            :style="{ color: form.color }"
          >
            <GmsIcon name="x" :size="12" />
          </button>
        </span>
      </div>
      
      <!-- Add facility input -->
      <div style="display:flex;gap:8px;">
        <input
          v-model="newFacility"
          class="gms-input"
          placeholder="Type facility name and press Enter"
          @keyup.enter="addFacility"
          style="flex:1;"
        />
        <button
          type="button"
          class="gms-btn gms-btn-ghost gms-btn-sm"
          @click="addFacility"
          :disabled="!newFacility.trim()"
        >
          <GmsIcon name="plus" :size="14" />
          Add
        </button>
      </div>
    </div>

    <template #footer>
      <GmsBtn variant="ghost" @click="tierModal = false">Cancel</GmsBtn>
      <GmsBtn variant="primary" :disabled="form.processing || !form.name" @click="saveTier">
        {{ editingTier ? 'Save changes' : 'Create service level' }}
      </GmsBtn>
    </template>
  </GmsModal>

  <!-- ── New / Edit Facility Modal ── -->
  <GmsModal :open="facilityModal" :title="editingFacility ? 'Edit facility' : 'New facility'" @close="facilityModal = false">

    <!-- Live preview -->
    <div class="sl-fac-preview">
      <div class="sl-fac-prev-icon">
        <GmsIcon :name="facForm.icon || 'tag'" :size="20" />
      </div>
      <div>
        <div class="sl-fac-prev-name">{{ facForm.name || 'Untitled facility' }}</div>
        <div class="sl-fac-prev-sub">Shown on service-level cards &amp; the guest profile</div>
      </div>
    </div>

    <div class="gms-field" style="margin-top:18px;">
      <label class="gms-label">Name *</label>
      <input v-model="facForm.name" class="gms-input" placeholder="e.g. Gala dinner" />
    </div>
    <div class="gms-field" style="margin-top:14px;">
      <label class="gms-label">Description</label>
      <input v-model="facForm.description" class="gms-input" placeholder="Short summary" />
    </div>

    <!-- Icon picker -->
    <div style="margin-top:20px;">
      <div class="sl-section-head">Icon <span class="sl-section-hint">· tap to choose</span></div>
      <div class="sl-icon-grid">
        <button
          v-for="ico in ICON_OPTIONS" :key="ico"
          class="sl-icon-btn"
          :class="{ on: facForm.icon === ico }"
          type="button"
          @click="facForm.icon = ico"
        >
          <GmsIcon :name="ico" :size="16" />
        </button>
      </div>
    </div>

    <template #footer>
      <GmsBtn variant="ghost" @click="facilityModal = false">Cancel</GmsBtn>
      <GmsBtn variant="primary" :disabled="!facForm.name" @click="saveFacility">
        {{ editingFacility ? 'Save changes' : 'Create facility' }}
      </GmsBtn>
    </template>
  </GmsModal>

  <!-- ── Delete Confirm ── -->
  <GmsModal :open="deleteModal" title="Delete service level" size="sm" @close="deleteModal = false">
    <p style="font-size:13.5px;color:var(--gms-text-2);">
      This service level will be removed. Guests assigned to it will need to be reassigned.
    </p>
    <template #footer>
      <GmsBtn variant="ghost" @click="deleteModal = false">Cancel</GmsBtn>
      <GmsBtn variant="danger" @click="confirmDelete">Delete</GmsBtn>
    </template>
  </GmsModal>
</template>
