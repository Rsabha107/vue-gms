<script setup>
const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    options: { type: Array, required: true },
    valueKey: { type: String, default: 'value' },
    labelKey: { type: String, default: 'label' },
    placeholder: { type: String, default: 'Select an option' },
    required: { type: Boolean, default: false },
    error: { type: String, default: '' },
    help: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

function handleChange(event) {
    emit('update:modelValue', event.target.value)
}
</script>

<template>
  <div class="portal-form-group">
    <label v-if="label" class="portal-form-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    <select
      :value="modelValue"
      class="portal-select"
      :class="{ 'has-error': error }"
      @change="handleChange"
    >
      <option value="" disabled>{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="typeof option === 'string' ? option : option[valueKey]"
        :value="typeof option === 'string' ? option : option[valueKey]"
      >
        {{ typeof option === 'string' ? option : option[labelKey] }}
      </option>
    </select>
    <p v-if="help && !error" class="portal-form-help">{{ help }}</p>
    <p v-if="error" class="portal-form-error">{{ error }}</p>
  </div>
</template>

<style scoped>
.portal-select.has-error {
    border-color: #dc2626;
}

.portal-select.has-error:focus {
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}
</style>
