<script setup>
import { computed } from 'vue'

const props = defineProps({
    name:  { type: String,  default: '' },
    size:  { type: String,  default: 'md' },
    color: { type: String,  default: null },
})

const palettes = [
    ['#fde68a','#92400e'], ['#c7d2fe','#312e81'], ['#fecaca','#991b1b'],
    ['#bbf7d0','#14532d'], ['#e9d5ff','#581c87'], ['#fed7aa','#7c2d12'],
    ['#cffafe','#164e63'], ['#fce7f3','#831843'], ['#d1fae5','#064e3b'],
    ['#dbeafe','#1e3a8a'],
]

const initials = computed(() => {
    const parts = (props.name || '').trim().split(/\s+/)
    if (parts.length === 0 || parts[0] === '') return '?'
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const palette = computed(() => {
    if (props.color) return [props.color, '#fff']
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
    :style="{ background: palette[0], color: palette[1] }"
  >{{ initials }}</div>
</template>
