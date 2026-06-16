<script setup>
import { computed } from 'vue'

const props = defineProps({
    name:  { type: String,  default: '' },
    size:  { type: String,  default: 'md' },
    color: { type: String,  default: null },
})

// Color palette matching React prototype - single solid colors with white text
const palettes = [
    '#8a1f3d', '#a9844a', '#3a6a8a', '#3f7d52', 
    '#7a5a8a', '#b06038', '#566b8a', '#8a6a2a'
]

const initials = computed(() => {
    const parts = (props.name || '').trim().split(/\s+/)
    if (parts.length === 0 || parts[0] === '') return '?'
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const backgroundColor = computed(() => {
    if (props.color) return props.color
    let hash = 0
    for (const ch of props.name) hash = (hash * 31 + ch.charCodeAt(0)) & 0x7fffffff
    return palettes[hash % palettes.length]
})

const sizeClass = computed(() => `gms-avatar-${props.size}`)
</script>

<template>
  <div
    class="gms-avatar"
    :class="sizeClass"
    :style="{ background: backgroundColor, color: '#fff' }"
  >{{ initials }}</div>
</template>
