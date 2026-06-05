<script setup>
import { onMounted, onUnmounted } from 'vue'
import GmsIcon from './GmsIcon.vue'

defineProps({
    open:  { type: Boolean, default: false },
    title: { type: String,  default: '' },
    size:  { type: String,  default: '' }, // '' | 'sm' | 'lg'
})
const emit = defineEmits(['close'])

function handleKey(e) { if (e.key === 'Escape') emit('close') }
onMounted(() => window.addEventListener('keydown', handleKey))
onUnmounted(() => window.removeEventListener('keydown', handleKey))
</script>

<template>
  <Teleport to="body">
    <Transition name="gms-modal-overlay">
      <div v-if="open" class="gms-modal-overlay" @click.self="$emit('close')">
        <Transition name="gms-modal" appear>
          <div v-if="open" class="gms-modal" :class="size ? `gms-modal-${size}` : ''">
            <div class="gms-modal-header">
              <span class="gms-modal-title">{{ title }}</span>
              <button class="gms-drawer-close" @click="$emit('close')">
                <GmsIcon name="x" :size="14" />
              </button>
            </div>
            <div class="gms-modal-body">
              <slot />
            </div>
            <div v-if="$slots.footer" class="gms-modal-footer">
              <slot name="footer" />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
