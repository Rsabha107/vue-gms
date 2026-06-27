<script setup>
import GmsIcon from './GmsIcon.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    variant:    { type: String,  default: 'ghost' },  // ghost | primary | danger
    size:       { type: String,  default: 'sm'    },  // sm | md | lg
    icon:       { type: String,  default: ''       },
    iconOnly:   { type: Boolean, default: false    },
    iconSize:   { type: Number,  default: 12       },
    processing: { type: Boolean, default: false    },
    disabled:   { type: Boolean, default: false    },
})
</script>

<template>
  <button
    class="gms-btn"
    :class="[`gms-btn-${variant}`, size !== 'md' ? `gms-btn-${size}` : '', iconOnly ? 'gms-btn-icon' : '']"
    :disabled="processing || disabled"
    v-bind="$attrs"
  >
    <GmsIcon v-if="processing" name="loader" :size="iconSize" />
    <GmsIcon v-else-if="icon" :name="icon" :size="iconSize" />
    <slot />
  </button>
</template>
