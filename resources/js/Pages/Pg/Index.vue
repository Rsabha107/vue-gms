<script setup>
import { inject, computed } from 'vue'
import PwaLayout from '@/Layouts/PwaLayout.vue'
import PwaLobby from '@/Components/Pwa/PwaLobby.vue'
import PwaHome from '@/Components/Pwa/PwaHome.vue'
import PwaTrip from '@/Components/Pwa/PwaTrip.vue'
import PwaTickets from '@/Components/Pwa/PwaTickets.vue'
import PwaFlights from '@/Components/Pwa/PwaFlights.vue'
import PwaProfile from '@/Components/Pwa/PwaProfile.vue'

defineOptions({ layout: PwaLayout })

defineProps({
    guest: { type: Object, default: () => ({}) },
    event: { type: Object, default: () => ({}) },
    events: { type: Array, default: () => [] },
    timeline: { type: Array, default: () => [] },
    matches: { type: Array, default: () => [] },
    flights: { type: Array, default: () => [] },
    transports: { type: Object, default: () => ({}) },
    services: { type: Object, default: () => ({}) },
})

const activeTab = inject('activeTab')
const enterPortal = inject('enterPortal')
</script>

<template>
  <div class="pwa-scr-anim">
    <PwaLobby v-if="activeTab === 'lobby'" :guest="guest" :events="events" :current-event="event" @enter="enterPortal" />
    <PwaHome v-else-if="activeTab === 'home'" :guest="guest" :event="event" :timeline="timeline" :matches="matches" :flights="flights" :transports="transports" :services="services" />
    <PwaTrip v-else-if="activeTab === 'trip'" :guest="guest" :event="event" :timeline="timeline" :matches="matches" :flights="flights" :services="services" />
    <PwaTickets v-else-if="activeTab === 'tickets'" :matches="matches" :guest="guest" />
    <PwaFlights v-else-if="activeTab === 'flights'" :flights="flights" :guest="guest" :event="event" />
    <PwaProfile v-else-if="activeTab === 'profile'" :guest="guest" :event="event" :services="services" />
    <div class="pwa-bottom-space" />
  </div>
</template>
