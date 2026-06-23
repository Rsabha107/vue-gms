<script setup>
import GmsDatePicker from './GmsDatePicker.vue'

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Select time...' },
    disabled: { type: Boolean, default: false },
    error: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

function handleUpdate(value) {
    emit('update:modelValue', value)
}
</script>

<template>
  <GmsDatePicker
    :model-value="modelValue"
    :placeholder="placeholder"
    date-format="H:i"
    :enable-time="true"
    :no-calendar="true"
    :time24hr="true"
    :disabled="disabled"
    :error="error"
    @update:model-value="handleUpdate"
  />
</template>
