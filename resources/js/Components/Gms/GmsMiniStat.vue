<script setup>
defineOptions({ inheritAttrs: false })
defineProps({
    label:     { type: String,                  required: true },
    value:     { type: [String, Number],         required: true },
    color:     { type: String,                  default: 'var(--gms-maroon)' },
    clickable: { type: Boolean,                 default: false },
})
defineEmits(['click'])
</script>

<template>
  <div
    class="gms-stat"
    :class="{ clickable }"
    v-bind="$attrs"
    @click="clickable ? $emit('click') : undefined"
  >
    <div class="gms-stat-strip" :style="{ background: color }" />
    <div class="gms-stat-number">{{ value }}</div>
    <div class="gms-stat-label">{{ label }}</div>
  </div>
</template>
