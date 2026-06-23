<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.css'

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Select date...' },
    dateFormat: { type: String, default: 'd/m/Y' },
    enableTime: { type: Boolean, default: false },
    noCalendar: { type: Boolean, default: false },
    time24hr: { type: Boolean, default: true },
    minDate: { type: String, default: null },
    maxDate: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    error: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
let picker = null

onMounted(() => {
    if (inputRef.value) {
        picker = flatpickr(inputRef.value, {
            dateFormat: props.dateFormat,
            enableTime: props.enableTime,
            noCalendar: props.noCalendar,
            time_24hr: props.time24hr,
            minDate: props.minDate,
            maxDate: props.maxDate,
            defaultDate: props.modelValue || null,
            onChange: (selectedDates, dateStr) => {
                emit('update:modelValue', dateStr)
            },
            disableMobile: true, // Use flatpickr on mobile too
        })
    }
})

watch(() => props.modelValue, (newValue) => {
    if (picker && newValue !== picker.input.value) {
        picker.setDate(newValue, false)
    }
})

watch(() => props.disabled, (newValue) => {
    if (picker) {
        if (newValue) {
            picker.input.setAttribute('disabled', 'disabled')
        } else {
            picker.input.removeAttribute('disabled')
        }
    }
})

onUnmounted(() => {
    if (picker) {
        picker.destroy()
    }
})
</script>

<template>
  <input
    ref="inputRef"
    type="text"
    :class="['gms-input', { 'gms-input-error': error }]"
    :placeholder="placeholder"
    :disabled="disabled"
    readonly
  />
</template>

<style>
/* Flatpickr theming to match GMS design system */
.flatpickr-calendar {
  background: var(--gms-surface);
  border: 1px solid var(--gms-border);
  border-radius: var(--gms-r);
  box-shadow: var(--gms-shadow-lg);
  font-family: var(--gms-font-ui);
}

.flatpickr-months {
  background: var(--gms-surface);
  border-bottom: 1px solid var(--gms-border);
}

.flatpickr-month {
  color: var(--gms-text);
}

.flatpickr-current-month {
  color: var(--gms-text);
}

.flatpickr-current-month .flatpickr-monthDropdown-months {
  background: var(--gms-surface);
  color: var(--gms-text);
}

.flatpickr-weekday {
  color: var(--gms-text-3);
  font-weight: 600;
}

.flatpickr-day {
  color: var(--gms-text);
}

.flatpickr-day:hover {
  background: var(--gms-surface-2);
  border-color: var(--gms-border);
}

.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange {
  background: var(--gms-maroon);
  border-color: var(--gms-maroon);
  color: #fff;
}

.flatpickr-day.selected:hover,
.flatpickr-day.startRange:hover,
.flatpickr-day.endRange:hover {
  background: var(--gms-maroon-600);
  border-color: var(--gms-maroon-600);
}

.flatpickr-day.today {
  border-color: var(--gms-maroon);
}

.flatpickr-day.today:hover {
  background: var(--gms-maroon-tint);
  border-color: var(--gms-maroon);
}

.flatpickr-day.disabled,
.flatpickr-day.disabled:hover {
  color: var(--gms-text-3);
  opacity: 0.4;
}

/* Time picker styling */
.flatpickr-time {
  border-top: 1px solid var(--gms-border);
}

.flatpickr-time input {
  color: var(--gms-text);
  font-family: var(--gms-font-mono);
}

.flatpickr-time input:hover,
.flatpickr-time input:focus {
  background: var(--gms-surface-2);
}

.flatpickr-time .flatpickr-time-separator,
.flatpickr-time .flatpickr-am-pm {
  color: var(--gms-text);
}

.flatpickr-time .numInputWrapper span.arrowUp:after {
  border-bottom-color: var(--gms-text);
}

.flatpickr-time .numInputWrapper span.arrowDown:after {
  border-top-color: var(--gms-text);
}
</style>
