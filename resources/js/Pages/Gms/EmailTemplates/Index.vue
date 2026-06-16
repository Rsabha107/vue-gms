<script setup>
import { ref, inject } from 'vue'
import { useForm } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    emailTemplates: { type: Array, default: () => [] },
    event:          { type: Object, default: () => ({}) },
})

const toast = inject('toast')

// ── Template list ─────────────────────────────────────────────────
const localTemplates = ref(props.emailTemplates.map(t => ({ ...t })))

// ── Template modal ────────────────────────────────────────────────
const tplModal = ref(false)
const editingTpl = ref(null)
const tplForm = useForm({ name: '', subject: '', body: '' })

function openNew() {
    editingTpl.value = null
    tplForm.reset()
    tplForm.name = ''
    tplForm.subject = ''
    tplForm.body = ''
    tplModal.value = true
}

function openEdit(tpl) {
    editingTpl.value = tpl
    tplForm.name = tpl.name
    tplForm.subject = tpl.subject
    tplForm.body = tpl.body
    tplModal.value = true
}

function saveTpl() {
    if (!tplForm.name.trim()) { toast('Name required.', 'info'); return }
    if (!tplForm.subject.trim()) { toast('Subject required.', 'info'); return }
    if (!tplForm.body.trim()) { toast('Body required.', 'info'); return }

    if (editingTpl.value) {
        tplForm.put(route('gms.email-templates.update', editingTpl.value.id), {
            onSuccess: () => {
                tplModal.value = false
                toast('Template updated.')
                localTemplates.value = localTemplates.value.map(t =>
                    t.id === editingTpl.value.id ? { ...t, ...tplForm.data() } : t
                )
            },
            onError: () => toast('Failed to save.', 'error'),
        })
    } else {
        tplForm.post(route('gms.email-templates.store'), {
            onSuccess: () => {
                tplModal.value = false
                toast('Template created.')
                localTemplates.value.push({ id: 'new-' + Date.now(), ...tplForm.data() })
            },
            onError: () => toast('Failed to create.', 'error'),
        })
    }
}

function deleteTpl(id) {
    if (window.confirm('Delete this template?')) {
        tplForm.delete(route('gms.email-templates.destroy', id), {
            onSuccess: () => {
                toast('Template deleted.')
                localTemplates.value = localTemplates.value.filter(t => t.id !== id)
            },
            onError: () => toast('Failed to delete.', 'error'),
        })
    }
}
</script>

<template>
<div class="gms-view">
  <div class="gms-view-header">
    <div class="gms-view-title">Email Templates</div>
    <button class="gms-btn gms-btn-primary gms-btn-sm" @click="openNew">
      <GmsIcon name="plus" :size="14" /> New Template
    </button>
  </div>

  <!-- ── Template list ────────────────────────────────────────── -->
  <div class="gms-cards-grid">
    <div 
      v-for="tpl in localTemplates" 
      :key="tpl.id" 
      class="gms-card"
    >
      <div class="gms-card-header">
        <div class="gms-card-title">{{ tpl.name }}</div>
        <div class="gms-card-menu">
          <button class="gms-btn gms-btn-icon gms-btn-ghost gms-btn-xs" @click="openEdit(tpl)">
            <GmsIcon name="edit" :size="14" />
          </button>
          <button class="gms-btn gms-btn-icon gms-btn-ghost gms-btn-xs" @click="deleteTpl(tpl.id)">
            <GmsIcon name="trash" :size="14" />
          </button>
        </div>
      </div>
      <div class="gms-card-body">
        <div class="gms-text-2">Subject: {{ tpl.subject }}</div>
        <div class="gms-text-preview">{{ tpl.body.substring(0, 100) }}…</div>
      </div>
    </div>

    <!-- Add new card -->
    <button 
      class="gms-card gms-card-add" 
      @click="openNew"
      style="border: 2px dashed var(--gms-border); background: transparent;"
    >
      <div style="text-align: center; color: var(--gms-text-3);">
        <GmsIcon name="plus" :size="24" style="opacity: 0.5; margin-bottom: 8px; display: block;" />
        <strong>Add Template</strong>
      </div>
    </button>
  </div>

  <!-- ── Template modal ───────────────────────────────────────── -->
  <GmsModal :open="tplModal" :title="editingTpl ? 'Edit Template' : 'New Template'" @close="tplModal = false" size="lg">
    <div class="gms-form-grid">
      <div class="gms-field">
        <label class="gms-label">Name</label>
        <input v-model="tplForm.name" type="text" class="gms-input" placeholder="e.g., VIP Invitation">
      </div>

      <div class="gms-field" style="grid-column: 1/-1;">
        <label class="gms-label">Subject</label>
        <input v-model="tplForm.subject" type="text" class="gms-input" placeholder="Email subject line">
      </div>

      <div class="gms-field" style="grid-column: 1/-1;">
        <label class="gms-label">Body</label>
        <textarea v-model="tplForm.body" class="gms-input" placeholder="Email body. Use {{guest_name}}, {{event}}, {{venue}}, etc." rows="8"></textarea>
      </div>
    </div>

    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="tplModal = false">Cancel</button>
      <button class="gms-btn gms-btn-primary" @click="saveTpl">{{ editingTpl ? 'Update' : 'Create' }}</button>
    </template>
  </GmsModal>
</div>
</template>

<style scoped>
.gms-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 16px;
  margin-top: 20px;
}

.gms-card {
  border: 1px solid var(--gms-border);
  border-radius: 8px;
  background: var(--gms-surface);
  padding: 16px;
  cursor: default;
  transition: box-shadow 0.2s;
}

.gms-card:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.gms-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.gms-card-title {
  font-weight: 600;
  color: var(--gms-text);
}

.gms-card-menu {
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.2s;
}

.gms-card:hover .gms-card-menu {
  opacity: 1;
}

.gms-card-body {
  font-size: 13px;
}

.gms-text-preview {
  color: var(--gms-text-3);
  margin-top: 8px;
  font-size: 12px;
}

.gms-card-add {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 140px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.gms-card-add:hover {
  background-color: var(--gms-bg);
}
</style>
