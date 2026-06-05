<script setup>
import { onMounted, onUnmounted } from 'vue'
import GmsIcon from './GmsIcon.vue'

defineProps({
    open:     { type: Boolean, default: false },
    title:    { type: String,  default: '' },
    subtitle: { type: String,  default: '' },
})
const emit = defineEmits(['close'])

function handleKey(e) { if (e.key === 'Escape') emit('close') }
onMounted(() => window.addEventListener('keydown', handleKey))
onUnmounted(() => window.removeEventListener('keydown', handleKey))
</script>

<template>
  <Teleport to="body">
    <Transition name="gms-drawer-overlay">
      <div v-if="open" class="gms-drawer-overlay" @click="$emit('close')" />
    </Transition>
    <Transition name="gms-drawer">
      <div v-if="open" class="gms-drawer">
        <div class="gms-drawer-header">
          <template v-if="$slots['header']">
            <slot name="header" />
          </template>
          <template v-else>
            <slot name="header-prefix" />
            <div class="gms-drawer-header-info">
              <div class="gms-drawer-title">{{ title }}</div>
              <div v-if="subtitle" class="gms-drawer-subtitle">{{ subtitle }}</div>
            </div>
          </template>
          <button class="gms-drawer-close" @click="$emit('close')">
            <GmsIcon name="x" :size="14" />
          </button>
        </div>
        <div class="gms-drawer-body">
          <slot />
        </div>
        <div v-if="$slots.footer" class="gms-drawer-footer">
          <slot name="footer" />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
