<script setup>
const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
})

const emit = defineEmits(['close'])

function handleBackdropClick(event) {
    if (event.target === event.currentTarget) {
        emit('close')
    }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="open" class="portal-modal-overlay" @click="handleBackdropClick">
        <div class="portal-modal">
          <div class="portal-modal-header">
            <h2 class="portal-modal-title">{{ title }}</h2>
            <p v-if="subtitle" class="portal-modal-subtitle">{{ subtitle }}</p>
          </div>
          
          <div class="portal-modal-body">
            <slot />
          </div>
          
          <div v-if="$slots.footer" class="portal-modal-footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-fade-enter-active .portal-modal,
.modal-fade-leave-active .portal-modal {
    transition: transform 0.2s ease;
}

.modal-fade-enter-from .portal-modal,
.modal-fade-leave-to .portal-modal {
    transform: scale(0.95) translateY(20px);
}
</style>
