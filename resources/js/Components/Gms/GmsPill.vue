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
    confirmed: { class: 'good', label: 'Confirmed' },
    pending:   { class: 'warn', label: 'Pending' },
    invited:   { class: 'info', label: 'Invited' },
    declined:  { class: 'bad', label: 'Declined' },
    cancelled: { class: 'grey', label: 'Cancelled' },
    confirmed_flight: { class: 'good', label: 'Confirmed' },
    arrival:   { class: 'info', label: 'Arrival' },
    departure: { class: 'pink', label: 'Departure' },
    // RSVP rollup statuses
    accepted:  { class: 'good', label: 'Accepted' },
    partial:   { class: 'warn', label: 'Partial' },
    awaiting:  { class: 'info', label: 'Awaiting' },
    sent:      { class: 'purple', label: 'Sent' },
    // Service request statuses
    new:       { class: 'info', label: 'New' },
    change:    { class: 'warn', label: 'Change' },
}

const style = computed(() => {
    if (props.type === 'tier') {
        const tier = props.tiers.find(t => t.id === props.value)
        if (tier) return { bg: tier.bg ?? '#f3f4f6', fg: tier.color ?? '#374151', label: tier.name, useClass: false }
    }
    if (props.type === 'custom' && props.bg) {
        return { bg: props.bg, fg: props.fg ?? '#374151', label: props.value, useClass: false }
    }
    const statusInfo = statusMap[props.value] ?? { class: 'grey', label: props.value }
    return { ...statusInfo, useClass: true }
})
</script>

<template>
  <span 
    class="gms-pill" 
    :class="style.useClass ? style.class : ''"
    :style="!style.useClass ? { background: style.bg, color: style.fg } : {}"
  >
    {{ style.label }}
  </span>
</template>
