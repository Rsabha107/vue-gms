<script setup>
import { ref, computed, inject } from 'vue'
import { useForm } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    groups: { type: Array, default: () => [] },
    event: { type: Object, default: () => ({}) },
})

const toast = inject('toast')

// ── Local state ───────────────────────────────────────────────────
const localGroups = ref(props.groups.map(g => ({ ...g })))

// ── Modals ────────────────────────────────────────────────────────
const groupModal = ref(false)
const editingGroup = ref(null)
const deleteModal = ref(false)
const deletingId = ref(null)

// ── Actions menu ──────────────────────────────────────────────────
const actionsMenuOpen = ref(null)

function toggleActions(groupId) {
    actionsMenuOpen.value = actionsMenuOpen.value === groupId ? null : groupId
}

// ── Create / Edit ─────────────────────────────────────────────────
function openCreate() {
    editingGroup.value = { id: '', name: '', label: '' }
    groupModal.value = true
}

function openEdit(group) {
    editingGroup.value = { ...group }
    groupModal.value = true
}

function closeGroupModal() {
    groupModal.value = false
    editingGroup.value = null
}

const groupForm = useForm({
    id: '',
    name: '',
    label: '',
})

function saveGroup() {
    const isUpdate = editingGroup.value && props.groups.some(g => g.id === editingGroup.value.id)
    
    if (isUpdate) {
        // Update
        groupForm.name = editingGroup.value.name
        groupForm.label = editingGroup.value.label

        groupForm.put(route('gms.groups.update', editingGroup.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                const idx = localGroups.value.findIndex(g => g.id === editingGroup.value.id)
                if (idx !== -1) {
                    localGroups.value[idx] = { ...editingGroup.value }
                }
                closeGroupModal()
                toast('Group updated successfully')
            },
            onError: () => {
                toast('Failed to update group', 'error')
            }
        })
    } else {
        // Create
        groupForm.id = editingGroup.value.id
        groupForm.name = editingGroup.value.name
        groupForm.label = editingGroup.value.label

        groupForm.post(route('gms.groups.store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                localGroups.value = page.props.groups.map(g => ({ ...g }))
                closeGroupModal()
                toast('Group created successfully')
            },
            onError: () => {
                toast('Failed to create group', 'error')
            }
        })
    }
}

// ── Delete ────────────────────────────────────────────────────────
function confirmDelete(id) {
    deletingId.value = id
    deleteModal.value = true
    actionsMenuOpen.value = null
}

function closeDeleteModal() {
    deleteModal.value = false
    deletingId.value = null
}

function deleteGroup() {
    groupForm.delete(route('gms.groups.destroy', deletingId.value), {
        preserveScroll: true,
        onSuccess: () => {
            localGroups.value = localGroups.value.filter(g => g.id !== deletingId.value)
            closeDeleteModal()
            toast('Group deleted successfully')
        },
        onError: (errors) => {
            closeDeleteModal()
            toast(errors.error || 'Failed to delete group', 'error')
        }
    })
}

// ── Stats ─────────────────────────────────────────────────────────
const stats = computed(() => {
    const total = localGroups.value.length
    const totalGuests = localGroups.value.reduce((sum, g) => sum + (g.guestCount || 0), 0)
    const avgGuests = total > 0 ? Math.round(totalGuests / total) : 0

    return [
        { label: 'Total Groups', value: total, subtitle: 'Organizational units', icon: 'users' },
        { label: 'Total Guests', value: totalGuests, subtitle: 'Across all groups', icon: 'user' },
        { label: 'Average Size', value: avgGuests, subtitle: 'Guests per group', icon: 'bar-chart' },
    ]
})
</script>

<template>
<div class="gms-view">
  <div class="gms-view-header">
    <div>
      <h1 class="gms-view-title">Groups</h1>
      <p style="margin: 6px 0 0; font-size: 14px; color: var(--gms-text-3);">
        Manage organizational groups and affiliations
      </p>
    </div>
    <button class="gms-btn gms-btn-primary" @click="openCreate">
      <GmsIcon name="plus" :size="14" />
      Add Group
    </button>
  </div>

  <!-- Stats -->
  <div class="gms-stats-grid" style="margin-bottom: 24px;">
    <div v-for="stat in stats" :key="stat.label" class="gms-stat-card">
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--gms-maroon-light); display: flex; align-items: center; justify-content: center;">
          <GmsIcon :name="stat.icon" :size="16" style="color: var(--gms-maroon);" />
        </div>
        <div style="flex: 1;">
          <div style="font-size: 11px; font-weight: 600; color: var(--gms-text-3); text-transform: uppercase; letter-spacing: 0.5px;">{{ stat.label }}</div>
        </div>
      </div>
      <div style="font-size: 28px; font-weight: 700; color: var(--gms-text); font-family: var(--gms-font-display); margin-bottom: 4px;">
        {{ stat.value }}
      </div>
      <div style="font-size: 12px; color: var(--gms-text-3);">{{ stat.subtitle }}</div>
    </div>
  </div>

  <!-- Groups Grid -->
  <div class="gms-groups-grid">
    <!-- Add new card -->
    <div class="gms-group-card gms-group-add" @click="openCreate">
      <div style="text-align: center;">
        <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--gms-surface-2); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
          <GmsIcon name="plus" :size="24" style="color: var(--gms-text-3);" />
        </div>
        <div style="font-weight: 600; font-size: 14px; color: var(--gms-text-2);">Add New Group</div>
        <div style="font-size: 12px; color: var(--gms-text-3); margin-top: 4px;">Create organizational unit</div>
      </div>
    </div>

    <!-- Group cards -->
    <div v-for="group in localGroups" :key="group.id" class="gms-group-card">
      <!-- Actions menu -->
      <div style="position: absolute; top: 12px; right: 12px;">
        <button class="gms-btn gms-btn-icon" @click.stop="toggleActions(group.id)" style="opacity: 0.7;">
          <GmsIcon name="more-vertical" :size="16" />
        </button>
        <div v-if="actionsMenuOpen === group.id" class="gms-dropdown-menu" style="right: 0; left: auto; min-width: 140px; margin-top: 4px;">
          <button @click="openEdit(group)" class="gms-dropdown-item">
            <GmsIcon name="edit" :size="14" />
            Edit
          </button>
          <div style="border-top: 1px solid var(--gms-border); margin: 4px 0;"></div>
          <button @click="confirmDelete(group.id)" class="gms-dropdown-item" style="color: var(--gms-danger);">
            <GmsIcon name="trash" :size="14" />
            Delete
          </button>
        </div>
      </div>

      <!-- Group icon -->
      <div class="gms-group-icon">
        <GmsIcon name="users" :size="20" />
      </div>

      <!-- Group info -->
      <div class="gms-group-name">{{ group.name }}</div>
      <div class="gms-group-label">{{ group.label }}</div>

      <!-- Stats -->
      <div class="gms-group-meta">
        <div class="gms-group-mi">
          <GmsIcon name="user" :size="12" />
          <span>{{ group.guestCount || 0 }} {{ group.guestCount === 1 ? 'guest' : 'guests' }}</span>
        </div>
        <div class="gms-group-mi">
          <GmsIcon name="tag" :size="12" />
          <span>{{ group.id }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Create/Edit Modal -->
  <GmsModal 
    :open="groupModal" 
    :title="(editingGroup && props.groups.some(g => g.id === editingGroup.id)) ? 'Edit Group' : 'Create Group'"
    @close="closeGroupModal"
  >
    <div class="gms-form-grid">
      <div class="gms-field">
        <label class="gms-label">Group ID</label>
        <input 
          v-model="editingGroup.id" 
          class="gms-input" 
          type="text" 
          placeholder="e.g., GRP-FIFA"
          :disabled="editingGroup && props.groups.some(g => g.id === editingGroup.id)"
          required
        />
        <div style="font-size: 11px; color: var(--gms-text-3); margin-top: 4px;">
          Unique identifier (cannot be changed after creation)
        </div>
      </div>

      <div class="gms-field">
        <label class="gms-label">Short Name</label>
        <input 
          v-model="editingGroup.name" 
          class="gms-input" 
          type="text" 
          placeholder="e.g., FIFA"
          required
        />
      </div>

      <div class="gms-field" style="grid-column: 1 / -1;">
        <label class="gms-label">Full Label</label>
        <input 
          v-model="editingGroup.label" 
          class="gms-input" 
          type="text" 
          placeholder="e.g., FIFA Delegation"
          required
        />
      </div>
    </div>

    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="closeGroupModal" :disabled="groupForm.processing">Cancel</button>
      <button class="gms-btn gms-btn-primary" @click="saveGroup" :disabled="groupForm.processing">
        <GmsIcon v-if="groupForm.processing" name="loader" :size="14" style="margin-right: 6px;" />
        {{ (editingGroup && props.groups.some(g => g.id === editingGroup.id)) ? 'Update' : 'Create' }} Group
      </button>
    </template>
  </GmsModal>

  <!-- Delete Confirmation Modal -->
  <GmsModal 
    :open="deleteModal" 
    title="Delete Group?"
    size="sm"
    @close="closeDeleteModal"
  >
    <p style="color: var(--gms-text-2); line-height: 1.6;">
      Are you sure you want to delete this group? This action cannot be undone.
    </p>
    <p style="color: var(--gms-text-3); font-size: 13px; margin-top: 12px;">
      Note: Groups with assigned guests cannot be deleted.
    </p>

    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="closeDeleteModal" :disabled="groupForm.processing">Cancel</button>
      <button class="gms-btn gms-btn-danger" @click="deleteGroup" :disabled="groupForm.processing">
        <GmsIcon v-if="groupForm.processing" name="loader" :size="14" style="margin-right: 6px;" />
        Delete Group
      </button>
    </template>
  </GmsModal>
</div>
</template>
