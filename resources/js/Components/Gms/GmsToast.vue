<script setup>
import GmsIcon from './GmsIcon.vue'

defineProps({ toasts: { type: Array, default: () => [] } })

const iconMap = { success: 'check-circle', error: 'alert-circle', info: 'info' }
</script>

<template>
  <Teleport to="body">
    <div class="gms-toast-host">
      <TransitionGroup name="gms-toast" tag="div" style="display:flex;flex-direction:column;gap:8px;">
        <div v-for="t in toasts" :key="t.id" class="gms-toast" :class="`gms-toast-${t.type}`">
          <GmsIcon :name="iconMap[t.type] ?? 'info'" :size="15" style="flex-shrink:0;" />
          <span>{{ t.msg }}</span>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
