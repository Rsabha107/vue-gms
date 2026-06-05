<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import GmsIcon from './GmsIcon.vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: 'all' },
  label: { type: String, required: true },
  allLabel: { type: String, default: 'All' },
  options: { type: Array, default: () => [] },
  valueKey: { type: String, default: 'id' },
  labelKey: { type: String, default: 'name' },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const wrapperRef = ref(null)

const isActive = computed(() => props.modelValue !== 'all')

const displayLabel = computed(() => {
  if (props.modelValue === 'all') return props.label
  
  const option = props.options.find(opt => {
    const value = typeof opt === 'object' ? opt[props.valueKey] : opt
    return value === props.modelValue
  })
  
  if (typeof option === 'object') {
    return option[props.labelKey]
  }
  
  return option
})

function toggle() {
  open.value = !open.value
  if (open.value) {
    emit('open')
  }
}

function select(value) {
  emit('update:modelValue', value)
  open.value = false
}

function clear(e) {
  e.stopPropagation()
  emit('update:modelValue', 'all')
}

function handleClickOutside(e) {
  if (wrapperRef.value && !wrapperRef.value.contains(e.target)) {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

defineExpose({ close: () => open.value = false })
</script>

<template>
  <div ref="wrapperRef" class="gms-chip-wrap" style="position: relative;">
    <button
      class="gms-chip"
      :class="{ on: isActive }"
      @click.stop="toggle"
    >
      <GmsIcon name="filter" :size="11" />
      <span>{{ displayLabel }}</span>
      <GmsIcon v-if="isActive" name="x" :size="11" @click="clear" />
      <GmsIcon v-else name="chevron-down" :size="11" />
    </button>

    <div v-if="open" class="gms-dropdown-menu" @click.stop>
      <!-- All option -->
      <button
        class="gms-dropdown-item"
        :class="{ active: modelValue === 'all' }"
        @click="select('all')"
      >
        {{ allLabel }}
        <GmsIcon v-if="modelValue === 'all'" name="check" :size="14" />
      </button>

      <!-- Options -->
      <button
        v-for="(option, idx) in options"
        :key="typeof option === 'object' ? option[valueKey] : option"
        class="gms-dropdown-item"
        :class="{ active: (typeof option === 'object' ? option[valueKey] : option) === modelValue }"
        @click="select(typeof option === 'object' ? option[valueKey] : option)"
      >
        <slot name="item" :option="option" :index="idx">
          {{ typeof option === 'object' ? option[labelKey] : option }}
        </slot>
        <GmsIcon
          v-if="(typeof option === 'object' ? option[valueKey] : option) === modelValue"
          name="check"
          :size="14"
        />
      </button>
    </div>
  </div>
</template>
