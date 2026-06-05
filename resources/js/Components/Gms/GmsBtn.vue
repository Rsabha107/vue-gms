<script setup>
import GmsIcon from './GmsIcon.vue'

defineOptions({ inheritAttrs: false })

defineProps({
    variant:  { type: String,  default: 'ghost' },  // ghost | primary | danger
    icon:     { type: String,  default: ''       },
    iconOnly: { type: Boolean, default: false    },
    iconSize: { type: Number,  default: 12       },
})
</script>

<template>
  <button
    class="gms-btn gms-btn-sm"
    :class="[`gms-btn-${variant}`, iconOnly ? 'gms-btn-icon' : '']"
    v-bind="$attrs"
  >
    <GmsIcon v-if="icon" :name="icon" :size="iconSize" />
    <slot />
  </button>
</template>
