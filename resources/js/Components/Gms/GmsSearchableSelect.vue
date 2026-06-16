<template>
  <div class="gms-searchable-select" ref="containerRef">
    <input
      ref="inputRef"
      type="text"
      class="gms-input"
      :placeholder="placeholder"
      v-model="searchQuery"
      @focus="isOpen = true"
      @input="handleInput"
      @keydown.down.prevent="navigateDown"
      @keydown.up.prevent="navigateUp"
      @keydown.enter.prevent="selectHighlighted"
      @keydown.escape="closeDropdown"
    />
    <div v-if="isOpen && filteredOptions.length > 0" class="gms-searchable-dropdown">
      <div
        v-for="(option, index) in filteredOptions"
        :key="getOptionValue(option)"
        class="gms-searchable-option"
        :class="{ 'highlighted': index === highlightedIndex }"
        @click="selectOption(option)"
        @mouseenter="highlightedIndex = index"
      >
        {{ getOptionLabel(option) }}
      </div>
    </div>
    <div v-if="isOpen && filteredOptions.length === 0 && searchQuery" class="gms-searchable-dropdown">
      <div class="gms-searchable-option disabled">No matches found</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, required: true },
  placeholder: { type: String, default: 'Search...' },
  valueKey: { type: String, default: 'id' },
  labelKey: { type: String, default: 'name' },
})

const emit = defineEmits(['update:modelValue'])

const containerRef = ref(null)
const inputRef = ref(null)
const searchQuery = ref('')
const isOpen = ref(false)
const highlightedIndex = ref(0)

const getOptionValue = (option) => {
  return typeof option === 'object' ? option[props.valueKey] : option
}

const getOptionLabel = (option) => {
  return typeof option === 'object' ? option[props.labelKey] : option
}

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options
  const query = searchQuery.value.toLowerCase()
  return props.options.filter(option => {
    const label = getOptionLabel(option).toLowerCase()
    return label.includes(query)
  })
})

const selectOption = (option) => {
  const value = getOptionValue(option)
  emit('update:modelValue', value)
  searchQuery.value = getOptionLabel(option)
  isOpen.value = false
  highlightedIndex.value = 0
}

const selectHighlighted = () => {
  if (filteredOptions.value.length > 0 && highlightedIndex.value >= 0) {
    selectOption(filteredOptions.value[highlightedIndex.value])
  }
}

const navigateDown = () => {
  if (highlightedIndex.value < filteredOptions.value.length - 1) {
    highlightedIndex.value++
  }
}

const navigateUp = () => {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--
  }
}

const closeDropdown = () => {
  isOpen.value = false
  highlightedIndex.value = 0
}

const handleInput = () => {
  isOpen.value = true
  highlightedIndex.value = 0
}

// Set initial display value
watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    const option = props.options.find(opt => getOptionValue(opt) === newVal)
    if (option) {
      searchQuery.value = getOptionLabel(option)
    }
  } else {
    searchQuery.value = ''
  }
}, { immediate: true })

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (containerRef.value && !containerRef.value.contains(event.target)) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.gms-searchable-select {
  position: relative;
}

.gms-searchable-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  background: var(--gms-surface);
  border: 1px solid var(--gms-border);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  max-height: 240px;
  overflow-y: auto;
  z-index: 100;
}

.gms-searchable-option {
  padding: 10px 14px;
  cursor: pointer;
  font-size: 13.5px;
  color: var(--gms-text);
  transition: background 0.1s;
}

.gms-searchable-option:hover,
.gms-searchable-option.highlighted {
  background: var(--gms-surface-2);
}

.gms-searchable-option.disabled {
  cursor: default;
  color: var(--gms-text-3);
}

.gms-searchable-option.disabled:hover {
  background: transparent;
}
</style>
