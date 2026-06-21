<script setup>
import GmsIcon from './GmsIcon.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    variant:    { type: String,  default: 'ghost' },  // ghost | primary | danger
    icon:       { type: String,  default: ''       },
    iconOnly:   { type: Boolean, default: false    },
    iconSize:   { type: Number,  default: 12       },
    processing: { type: Boolean, default: false    },
    disabled:   { type: Boolean, default: false    },
})
</script>

<template>
  <button
    class="gms-btn gms-btn-sm"
    :class="[`gms-btn-${variant}`, iconOnly ? 'gms-btn-icon' : '']"
    :disabled="processing || disabled"
    v-bind="$attrs"
  >
    <GmsIcon v-if="processing" name="loader" :size="iconSize" />
    <GmsIcon v-else-if="icon" :name="icon" :size="iconSize" />
    <slot />
  </button>
</template>
