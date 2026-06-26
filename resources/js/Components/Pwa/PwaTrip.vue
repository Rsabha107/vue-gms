<script setup>
import { ref, computed, inject } from 'vue'
import PwaSheet from './PwaSheet.vue'

const navigateTo = inject('navigateTo')

const sheetOpen = ref(false)
const sheetItem = ref(null)

function openDetail(item) { sheetItem.value = item; sheetOpen.value = true }
function closeDetail() { sheetOpen.value = false; sheetItem.value = null }

const props = defineProps({
    guest: Object,
    event: Object,
    timeline: Array,
    matches: Array,
    flights: Array,
    services: Object,
})

const MONTHS = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
function fmtDate(d) {
    if (!d) return ''
    const p = typeof d === 'string' ? d.split(/[-T ]/) : []
    if (p.length >= 3) return `${parseInt(p[2])} ${MONTHS[parseInt(p[1])]}`
    return ''
}

const tripDays = computed(() => {
    const days = {}
    const items = []

    // Flights
    ;(props.flights || []).forEach(f => {
        items.push({ date: f.date, time: f.time || '—', title: `${f.origin} → ${f.destination}`, sub: `${f.airline || 'Qatar Airways'} · ${f.flightNo || f.code}`, icon: 'plane', type: 'flight', tab: 'flights' })
        if (f.outboundDate) {
            items.push({ date: f.outboundDate, time: f.outboundTime || '—', title: `${f.destination} → ${f.origin}`, sub: `${f.airline || 'Qatar Airways'} · ${f.outboundFlight || f.code}`, icon: 'plane', type: 'flight', tab: 'flights' })
        }
    })

    // Matches
    ;(props.matches || []).forEach(m => {
        const date = typeof m.date === 'string' ? m.date.split('T')[0] : m.date
        items.push({ date, time: m.time || m.kickoff || '—', title: `${m.homeTeam || m.team_a_name || 'TBD'} vs ${m.awayTeam || m.team_b_name || 'TBD'}`, sub: m.venue || m.venueName || '', icon: 'trophy', type: 'match', tab: 'tickets' })
    })

    // Hotel
    if (props.services?.hotelName) {
        items.push({ date: props.services.checkIn || '', time: '14:00', title: `Check-in · ${props.services.hotelName}`, sub: props.services.hotelDates || '', icon: 'building', type: 'hotel', tab: 'flights' })
        items.push({ date: props.services.checkOut || '', time: '12:00', title: `Check-out · ${props.services.hotelName}`, sub: '', icon: 'building', type: 'hotel', tab: 'flights' })
    }

    // Timeline items
    ;(props.timeline || []).forEach(t => {
        if (!items.some(i => i.title === t.title)) {
            items.push({ date: '', time: t.time || '—', title: t.title, sub: t.subtitle || '', icon: t.icon || 'clock', type: 'timeline', tab: 'home' })
        }
    })

    // Group by date
    items.sort((a, b) => (a.date + a.time).localeCompare(b.date + b.time))
    items.forEach(item => {
        const key = item.date || 'Upcoming'
        if (!days[key]) days[key] = { date: key, display: item.date ? fmtDate(item.date) : 'Upcoming', items: [] }
        days[key].items.push(item)
    })

    return Object.values(days)
})

const icons = {
    plane: '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
    building: '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>',
    trophy: '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
    car: '<path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/>',
    clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    check: '<polyline points="20 6 9 17 4 12"/>',
    'chevron-right': '<polyline points="9 18 15 12 9 6"/>',
}

const typeColors = { flight: '#7a5a8a', match: '#8a1f3d', hotel: '#3f7d52', timeline: '#3a6a8a' }
</script>

<template>
  <div>
    <div style="height: 54px;" />
    <div class="pwa-scr-head">
      <div class="pwa-scr-eyebrow">Itinerary</div>
      <h1 class="pwa-scr-title">My Trip</h1>
    </div>

    <div v-if="tripDays.length === 0" style="padding: 40px 18px; text-align: center; color: var(--pwa-ink-3);">
      <p style="font-size: 15px; font-weight: 500;">No trip details yet</p>
      <p style="font-size: 13px; margin-top: 6px;">Your complete itinerary will appear here.</p>
    </div>

    <div v-for="day in tripDays" :key="day.date" class="pwa-trip-day">
      <div class="pwa-trip-day-h">
        <span class="pwa-trip-day-d">{{ day.display }}</span>
        <span class="pwa-trip-day-cnt">{{ day.items.length }} item{{ day.items.length !== 1 ? 's' : '' }}</span>
      </div>

      <div class="pwa-trip-items">
        <button v-for="(item, j) in day.items" :key="j" class="pwa-trip-item" @click="openDetail(item)">
          <div class="pwa-trip-time">{{ item.time }}</div>
          <div class="pwa-trip-spine">
            <div class="pwa-trip-node" :style="{ background: typeColors[item.type] || 'var(--pwa-ink-3)', borderColor: typeColors[item.type] || 'var(--pwa-ink-3)' }">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons[item.icon] || icons.clock" />
            </div>
          </div>
          <div class="pwa-trip-card">
            <div class="pwa-trip-card-tx">
              <div class="pwa-trip-card-t">{{ item.title }}</div>
              <div class="pwa-trip-card-s" v-if="item.sub">{{ item.sub }}</div>
            </div>
            <div class="pwa-trip-chev">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons['chevron-right']" />
            </div>
          </div>
        </button>
      </div>
    </div>

    <div class="pwa-bottom-space" />

    <!-- Detail Sheet -->
    <PwaSheet :open="sheetOpen" :title="sheetItem?.title || ''" :subtitle="sheetItem?.sub || ''" @close="closeDetail">
      <template v-if="sheetItem">
        <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
          <span class="pwa-pill">{{ sheetItem.time }}</span>
          <span v-if="sheetItem.date" class="pwa-pill">{{ fmtDate(sheetItem.date) }}</span>
          <span class="pwa-pill" :style="{ background: (typeColors[sheetItem.type] || '#3a6a8a') + '1a', color: typeColors[sheetItem.type] || '#3a6a8a', border: 'none' }">
            {{ sheetItem.type }}
          </span>
        </div>
        <div class="pwa-card">
          <div class="pwa-card-pad">
            <div class="pwa-sheet-kv"><span class="k">Activity</span><span class="v" style="font-family: inherit;">{{ sheetItem.title }}</span></div>
            <div class="pwa-sheet-kv" v-if="sheetItem.sub"><span class="k">Details</span><span class="v" style="font-family: inherit;">{{ sheetItem.sub }}</span></div>
            <div class="pwa-sheet-kv"><span class="k">Time</span><span class="v">{{ sheetItem.time }}</span></div>
            <div class="pwa-sheet-kv" v-if="sheetItem.date"><span class="k">Date</span><span class="v">{{ fmtDate(sheetItem.date) }}</span></div>
          </div>
        </div>
      </template>
      <template #footer>
        <button class="pwa-sheet-btn pri" @click="navigateTo(sheetItem?.tab || 'home'); closeDetail()">View full details</button>
      </template>
    </PwaSheet>
  </div>
</template>

<style scoped>
.pwa-trip-day { padding: 0 18px; margin-bottom: 8px; }
.pwa-trip-day-h { display: flex; align-items: center; gap: 10px; padding: 14px 0 8px; }
.pwa-trip-day-d { font-family: 'Instrument Serif', Georgia, serif; font-size: 20px; }
.pwa-trip-day-cnt { font-size: 11px; color: var(--pwa-ink-3); margin-left: auto; }

.pwa-trip-items { position: relative; }
.pwa-trip-item { display: grid; grid-template-columns: 46px 26px 1fr; gap: 10px; align-items: stretch; width: 100%; text-align: left; background: none; border: none; cursor: pointer; padding: 0; }
.pwa-trip-time { text-align: right; padding-top: 14px; font-family: 'IBM Plex Mono', monospace; font-size: 12px; font-weight: 600; color: var(--pwa-ink-2); }
.pwa-trip-spine { position: relative; display: flex; justify-content: center; }
.pwa-trip-spine::before { content: ""; position: absolute; top: 0; bottom: 0; width: 2px; background: var(--pwa-line-2); }
.pwa-trip-item:first-child .pwa-trip-spine::before { top: 16px; }
.pwa-trip-item:last-child .pwa-trip-spine::before { bottom: calc(100% - 30px); }
.pwa-trip-node {
  position: relative; z-index: 1; margin-top: 10px; width: 26px; height: 26px; border-radius: 50%;
  display: grid; place-items: center; color: #fff; border: 2px solid;
}
.pwa-trip-node svg { width: 12px; height: 12px; }
.pwa-trip-card {
  flex: 1; min-width: 0; background: var(--pwa-surface); border: 1px solid var(--pwa-line);
  border-radius: var(--pwa-r); padding: 11px 13px; margin: 6px 0;
  box-shadow: var(--pwa-sh-sm); transition: .13s; display: flex; align-items: center; gap: 10px;
}
.pwa-trip-card:hover { border-color: var(--pwa-line-2); box-shadow: var(--pwa-sh); }
.pwa-trip-card-tx { flex: 1; min-width: 0; }
.pwa-trip-card-t { font-weight: 600; font-size: 13.5px; line-height: 1.2; }
.pwa-trip-card-s { font-size: 11.5px; color: var(--pwa-ink-2); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pwa-trip-chev { color: var(--pwa-ink-3); flex: none; }
.pwa-trip-chev svg { width: 16px; height: 16px; }
</style>
