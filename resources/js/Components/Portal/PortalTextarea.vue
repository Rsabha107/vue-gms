<script setup>
const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    rows: { type: Number, default: 4 },
    required: { type: Boolean, default: false },
    error: { type: String, default: '' },
    help: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

function handleInput(event) {
    emit('update:modelValue', event.target.value)
}
</script>

<template>
  <div class="portal-form-group">
    <label v-if="label" class="portal-form-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    <textarea
      :value="modelValue"
      :placeholder="placeholder"
      :rows="rows"
      class="portal-textarea"
      :class="{ 'has-error': error }"
      @input="handleInput"
    ></textarea>
    <p v-if="help && !error" class="portal-form-help">{{ help }}</p>
    <p v-if="error" class="portal-form-error">{{ error }}</p>
  </div>
</template>

<style scoped>
.portal-textarea.has-error {
    border-color: #dc2626;
}

.portal-textarea.has-error:focus {
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}
</style>
