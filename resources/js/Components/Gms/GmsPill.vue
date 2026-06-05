<script setup>
import { computed } from 'vue'

const props = defineProps({
    type:  { type: String, default: 'status' }, // 'status' | 'tier' | 'custom'
    value: { type: String, default: '' },
    tiers: { type: Array,  default: () => [] },
    bg:    { type: String, default: null },
    fg:    { type: String, default: null },
})

const statusMap = {
    confirmed: { bg: '#dcfce7', fg: '#15803d', dot: '#22c55e', label: 'Confirmed' },
    pending:   { bg: '#fef9c3', fg: '#a16207', dot: '#ca8a04', label: 'Pending' },
    invited:   { bg: '#dbeafe', fg: '#1d4ed8', dot: '#3b82f6', label: 'Invited' },
    declined:  { bg: '#fee2e2', fg: '#dc2626', dot: '#ef4444', label: 'Declined' },
    cancelled: { bg: '#f3f4f6', fg: '#6b7280', dot: '#9ca3af', label: 'Cancelled' },
    confirmed_flight: { bg: '#dcfce7', fg: '#15803d', dot: '#22c55e', label: 'Confirmed' },
    arrival:   { bg: '#dbeafe', fg: '#1d4ed8', dot: '#3b82f6', label: 'Arrival' },
    departure: { bg: '#fce7f3', fg: '#9d174d', dot: '#ec4899', label: 'Departure' },
}

const style = computed(() => {
    if (props.type === 'tier') {
        const tier = props.tiers.find(t => t.id === props.value)
        if (tier) return { bg: tier.bg ?? '#f3f4f6', fg: tier.color ?? '#374151', dot: tier.color, label: tier.name }
    }
    if (props.type === 'custom' && props.bg) {
        return { bg: props.bg, fg: props.fg ?? '#374151', dot: props.fg, label: props.value }
    }
    return statusMap[props.value] ?? { bg: '#f3f4f6', fg: '#6b7280', dot: '#9ca3af', label: props.value }
})
</script>

<template>
  <span class="gms-pill" :style="{ background: style.bg, color: style.fg }">
    <span class="gms-pill-dot" :style="{ background: style.dot }" />
    {{ style.label }}
  </span>
</template>
