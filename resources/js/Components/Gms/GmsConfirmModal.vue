<script setup>
import { computed } from 'vue'
import GmsModal from './GmsModal.vue'
import GmsIcon from './GmsIcon.vue'

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Confirm Action' },
    message: { type: String, required: true },
    description: { type: String, default: '' },
    details: { type: Array, default: () => [] },
    confirmText: { type: String, default: 'Confirm' },
    cancelText: { type: String, default: 'Cancel' },
    confirmIcon: { type: String, default: '' },
    variant: { type: String, default: 'primary' }, // 'primary' | 'danger'
    loading: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'confirm'])

const buttonClass = computed(() => {
    return props.variant === 'danger' ? 'gms-btn gms-btn-danger' : 'gms-btn gms-btn-primary'
})

function handleConfirm() {
    emit('confirm')
}

function handleClose() {
    emit('close')
}
</script>

<template>
  <GmsModal 
    :open="open" 
    :title="title"
    @close="handleClose"
  >
    <div style="padding: 4px 0;">
      <!-- Main message with HTML support -->
      <p 
        v-if="message" 
        style="font-size: 14px; line-height: 1.6; color: var(--gms-text-2); margin-bottom: 16px;"
        v-html="message"
      />

      <!-- Details box with bullet points -->
      <div 
        v-if="details.length > 0" 
        style="background: var(--gms-surface); border: 1px solid var(--gms-border); border-radius: var(--gms-r); padding: 14px; margin-bottom: 16px;"
      >
        <div 
          v-if="description" 
          style="font-size: 12px; font-weight: 600; color: var(--gms-text-3); margin-bottom: 8px;"
        >
          {{ description }}
        </div>
        <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: var(--gms-text-2); line-height: 1.8;">
          <li v-for="(detail, index) in details" :key="index" v-html="detail"></li>
        </ul>
      </div>

      <!-- Additional slot for custom content -->
      <slot></slot>
    </div>

    <template #footer>
      <button 
        class="gms-btn gms-btn-ghost" 
        @click="handleClose"
        :disabled="loading"
      >
        {{ cancelText }}
      </button>
      <button 
        :class="buttonClass"
        @click="handleConfirm"
        :disabled="loading"
      >
        <GmsIcon v-if="confirmIcon" :name="confirmIcon" :size="14" />
        {{ confirmText }}
      </button>
    </template>
  </GmsModal>
</template>
