<script setup>
import { ref, computed, inject } from 'vue'
import PwaSheet from './PwaSheet.vue'

const navigateTo = inject('navigateTo')

const props = defineProps({
    guest: Object,
    event: Object,
    timeline: Array,
    matches: Array,
    flights: Array,
    transports: Object,
    services: Object,
})

const sheetOpen = ref(false)
const sheetItem = ref(null)
const transportSheetOpen = ref(false)

const MONTHS = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']
const DAYS = ['SUN','MON','TUE','WED','THU','FRI','SAT']
function fmtBpDate(d) {
    if (!d) return '—'
    const p = d.split('-')
    return `${parseInt(p[2])} ${MONTHS[parseInt(p[1])]}`
}

function openDetail(item) {
    sheetItem.value = item
    sheetOpen.value = true
}

function closeDetail() {
    sheetOpen.value = false
    sheetItem.value = null
}

function openTransportSheet() {
    transportSheetOpen.value = true
}

const transportByDate = computed(() => {
    const movements = props.transports?.movements || []
    if (!movements.length) return []
    const groups = []
    let currentKey = null
    let foundUpNext = false
    for (const m of movements) {
        const dt = new Date(m.date)
        const dateKey = `${DAYS[dt.getUTCDay()]} · ${dt.getUTCDate()} ${MONTHS[dt.getUTCMonth() + 1]}`
        if (dateKey !== currentKey) {
            currentKey = dateKey
            groups.push({ label: dateKey, items: [] })
        }
        const isUpNext = !m.done && !foundUpNext
        if (isUpNext) foundUpNext = true
        groups[groups.length - 1].items.push({ ...m, dateKey, isUpNext })
    }
    return groups
})

const transportDriverInitials = computed(() => {
    const name = props.transports?.driver?.name || ''
    return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
})

function transportIcon(type) {
    const t = (type || '').toLowerCase()
    if (t.includes('airport') || t.includes('arrival') || t.includes('departure')) return 'airport'
    if (t.includes('match') || t.includes('stadium')) return 'match'
    if (t.includes('return') || t.includes('hotel')) return 'hotel'
    return 'transfer'
}

const firstName = computed(() => props.guest?.firstName || props.guest?.name?.split(' ')[0] || 'Guest')
const initials = computed(() => {
    const n = props.guest?.name || ''
    return n.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
})

const daysUntil = computed(() => {
    if (!props.event?.dateStart) return null
    const diff = Math.ceil((new Date(props.event.dateStart) - new Date()) / 86400000)
    return Math.max(0, diff)
})

const nextEvent = computed(() => props.timeline?.[0] || null)

const svcCards = computed(() => [
    { key: 'flights', icon: 'plane', name: 'FLIGHTS', tab: 'flights', line: props.services?.flightCount ? `${props.services.flightCount} booking${props.services.flightCount > 1 ? 's' : ''}` : 'No bookings', sub: props.services?.flightStatus || 'Request via portal', status: props.services?.flightStatus },
    { key: 'hotel', icon: 'building', name: 'HOTEL', tab: 'flights', line: props.services?.hotelName || 'No booking', sub: props.services?.hotelDates || '', status: props.services?.hotelStatus },
    { key: 'transport', icon: 'car', name: 'TRANSPORT', tab: 'flights', line: props.services?.transportCount ? `${props.services.transportCount} transfer${props.services.transportCount > 1 ? 's' : ''}` : 'Pending', sub: props.services?.transportStatus || '', status: props.services?.transportStatus },
    { key: 'matches', icon: 'ticket', name: 'MATCHES', tab: 'tickets', line: props.services?.matchCount ? `${props.services.matchCount} match${props.services.matchCount > 1 ? 'es' : ''}` : 'None assigned', sub: props.services?.nextMatch || '', status: null },
])

const avColors = ['#8a1f3d', '#a9844a', '#3a6a8a', '#3f7d52', '#7a5a8a']
const avColor = computed(() => avColors[(props.guest?.name?.charCodeAt(0) || 0) % avColors.length])

const icons = {
    plane: '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
    building: '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>',
    car: '<path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/>',
    ticket: '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 17v2M13 11v2"/>',
    bell: '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    check: '<polyline points="20 6 9 17 4 12"/>',
    'chevron-right': '<polyline points="9 18 15 12 9 6"/>',
    'map-pin': '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
    calendar: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
    clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
}
</script>

<template>
  <div>
    <!-- Safe top -->
    <div style="height: 54px;" />

    <!-- Greeting -->
    <div class="pwa-greet">
      <div class="pwa-greet-av" :style="{ background: avColor }">{{ initials }}</div>
      <div class="pwa-greet-tx">
        <div class="pwa-greet-hi">Welcome back,</div>
        <div class="pwa-greet-nm">{{ firstName }}</div>
      </div>
      <button class="pwa-greet-bell">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons.bell" />
      </button>
    </div>

    <!-- Hero — Up Next -->
    <div class="pwa-hero" v-if="nextEvent || event">
      <div class="pwa-hero-bg" />
      <div class="pwa-hero-in">
        <div class="pwa-hero-top">
          <span class="pwa-hero-live"><i></i> UP NEXT</span>
          <span class="pwa-hero-kind">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="nextEvent?.icon ? icons[nextEvent.icon] : icons.calendar" />
            {{ nextEvent?.type || 'Event' }}
          </span>
        </div>
        <div class="pwa-hero-title">{{ nextEvent?.title || event?.name || 'Event' }}</div>
        <div class="pwa-hero-sub">{{ nextEvent?.subtitle || event?.location || '' }}</div>
        <div class="pwa-hero-foot">
          <div class="pwa-hero-when" v-if="nextEvent?.time">
            <span class="t">{{ nextEvent.time }}</span>
            <span class="d">{{ nextEvent.date }}</span>
          </div>
          <div class="pwa-hero-countdown" v-if="daysUntil !== null && !nextEvent">
            <b>{{ daysUntil }}</b> days to go
          </div>
          <button class="pwa-hero-cta" v-if="nextEvent" @click="timelineNav(nextEvent)">
            View details
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons['chevron-right']" />
          </button>
        </div>
      </div>
    </div>

    <!-- Today's Timeline -->
    <div class="pwa-sec">
      <span class="pwa-sec-t">Today</span>
      <span class="pwa-sec-line" />
    </div>
    <div class="pwa-tl" v-if="timeline.length > 0">
      <div v-for="(item, i) in timeline" :key="i" class="pwa-tl-item" :class="{ done: item.done, next: item.next }">
        <div class="pwa-tl-time">
          <div class="t">{{ item.time }}</div>
          <div class="d">{{ item.date }}</div>
        </div>
        <div class="pwa-tl-spine">
          <div class="pwa-tl-node">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="item.done ? icons.check : (icons[item.icon] || icons.clock)" />
          </div>
        </div>
        <button class="pwa-tl-card" @click="openDetail(item)">
          <div class="pwa-tl-tx">
            <div class="pwa-tl-t">{{ item.title }}</div>
            <div class="pwa-tl-s">{{ item.subtitle }}</div>
          </div>
          <div class="pwa-tl-chev">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons['chevron-right']" />
          </div>
        </button>
      </div>
      <!-- Now marker -->
      <div class="pwa-tl-now">
        <span class="ln" />
        <span>Now</span>
        <span class="ln" />
      </div>
    </div>
    <div v-else style="padding: 20px 18px; text-align: center; color: var(--pwa-ink-3); font-size: 13px;">
      No activities scheduled for today.
    </div>

    <!-- Services -->
    <div class="pwa-sec">
      <span class="pwa-sec-t">Your Services</span>
      <span class="pwa-sec-line" />
    </div>
    <div class="pwa-svc-grid">
      <div v-for="svc in svcCards" :key="svc.key" class="pwa-svc" @click="svc.key === 'transport' ? openTransportSheet() : openDetail({ title: svc.name, subtitle: svc.line, icon: svc.icon, type: svc.key, sub: svc.sub, status: svc.status, tab: svc.tab })">
        <div class="pwa-svc-ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons[svc.icon]" />
        </div>
        <div class="pwa-svc-name">{{ svc.name }}</div>
        <div class="pwa-svc-line">{{ svc.line }}</div>
        <div class="pwa-svc-sub" v-if="svc.sub">{{ svc.sub }}</div>
        <div class="pwa-svc-foot" v-if="svc.status">
          <span class="pwa-pill" :class="svc.status === 'confirmed' ? 'good' : 'warn'">
            <span class="dot" /> {{ svc.status }}
          </span>
        </div>
      </div>
    </div>

    <!-- Flight Detail Sheet -->
    <PwaSheet
      :open="sheetOpen && (sheetItem?.icon === 'plane' || sheetItem?.type === 'flights')"
      title="Your flights"
      :subtitle="`${flights?.[0]?.airline || 'Qatar Airways'} · ${flights?.[0]?.class || 'Business'} · ref ${flights?.[0]?.code || '—'}`"
      @close="closeDetail"
    >
      <!-- Status pills -->
      <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
        <span class="pwa-pill good"><span class="dot" /> Confirmed</span>
        <span v-if="flights?.length" class="pwa-pill">✈ {{ flights.length }} passenger{{ flights[0]?.pax > 1 ? 's' : '' }}</span>
        <span v-if="flights?.[0]?.code" class="pwa-pill" style="font-family: 'IBM Plex Mono', monospace;">{{ flights[0].code }}</span>
      </div>

      <!-- Boarding pass cards for each flight -->
      <div v-for="f in (flights || [])" :key="f.id" class="pwa-bp" style="margin: 0 0 14px;">
        <div class="pwa-bp-top">
          <span class="pwa-bp-air">{{ f.airline || 'Qatar Airways' }}</span>
          <span class="pwa-bp-no">{{ f.flightNo || f.code }}</span>
        </div>
        <div class="pwa-bp-route">
          <div class="pwa-bp-end">
            <div class="code">{{ f.origin }}</div>
            <div class="city">{{ f.originCity }}</div>
          </div>
          <div class="pwa-bp-mid">
            <div class="dur">{{ f.duration || '' }}</div>
            <div class="pwa-bp-dots">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons.plane" />
            </div>
          </div>
          <div class="pwa-bp-end" style="text-align: right;">
            <div class="code">{{ f.destination }}</div>
            <div class="city">{{ f.destCity }}</div>
          </div>
        </div>
        <!-- Time row -->
        <div style="display: flex; justify-content: space-between; padding: 0 17px 14px; font-family: 'IBM Plex Mono', monospace; font-size: 16px; font-weight: 600;">
          <span>{{ f.time || '—' }}</span>
          <span>{{ f.arrivalTime || '' }}</span>
        </div>
        <!-- Detail grid -->
        <div class="pwa-bp-grid" style="grid-template-columns: 1fr 1fr 1fr;">
          <div class="pwa-bp-cell"><div class="k">DATE</div><div class="v">{{ fmtBpDate(f.date) }}</div></div>
          <div class="pwa-bp-cell"><div class="k">CABIN</div><div class="v">{{ f.class || '—' }}</div></div>
          <div class="pwa-bp-cell"><div class="k">SEAT</div><div class="v">{{ f.seat || '—' }}</div></div>
        </div>
        <div class="pwa-bp-grid" style="grid-template-columns: 1fr 1fr 1fr;">
          <div class="pwa-bp-cell"><div class="k">TERMINAL</div><div class="v">{{ f.terminal || '—' }}</div></div>
          <div class="pwa-bp-cell"><div class="k">PASSENGERS</div><div class="v">{{ f.pax || 1 }}</div></div>
          <div class="pwa-bp-cell"><div class="k">BOARDING</div><div class="v">{{ f.boarding || '45 min prior' }}</div></div>
        </div>
      </div>

      <template #footer>
        <button class="pwa-sheet-btn" @click="closeDetail">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          Add to Wallet
        </button>
        <button class="pwa-sheet-btn pri" @click="closeDetail">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Request a change
        </button>
      </template>
    </PwaSheet>

    <!-- Non-flight Detail Sheet -->
    <PwaSheet
      :open="sheetOpen && sheetItem?.icon !== 'plane' && sheetItem?.type !== 'flights'"
      :title="sheetItem?.title || ''"
      :subtitle="sheetItem?.subtitle || sheetItem?.sub || ''"
      @close="closeDetail"
    >
      <template v-if="sheetItem">
        <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
          <span v-if="sheetItem.status" class="pwa-pill" :class="sheetItem.status === 'confirmed' ? 'good' : 'warn'">
            <span class="dot" /> {{ sheetItem.status }}
          </span>
          <span v-if="sheetItem.time" class="pwa-pill">{{ sheetItem.time }}</span>
        </div>

        <div class="pwa-card" style="margin-bottom: 14px;">
          <div class="pwa-card-pad">
            <div class="pwa-sheet-kv" v-if="sheetItem.subtitle"><span class="k">Details</span><span class="v" style="font-family:inherit;">{{ sheetItem.subtitle }}</span></div>
            <div class="pwa-sheet-kv" v-if="sheetItem.time"><span class="k">Time</span><span class="v">{{ sheetItem.time }}</span></div>
            <div class="pwa-sheet-kv" v-if="sheetItem.date && sheetItem.date !== 'Today'"><span class="k">Date</span><span class="v">{{ sheetItem.date }}</span></div>
            <div class="pwa-sheet-kv" v-if="sheetItem.sub && sheetItem.sub !== sheetItem.subtitle"><span class="k">Info</span><span class="v" style="font-family:inherit;">{{ sheetItem.sub }}</span></div>
          </div>
        </div>
      </template>
      <template #footer>
        <button class="pwa-sheet-btn pri" style="flex:1;" @click="closeDetail">Close</button>
      </template>
    </PwaSheet>

    <!-- Transport Detail Sheet -->
    <PwaSheet
      :open="transportSheetOpen"
      title="Your transport"
      :subtitle="`${transports?.vehicle || ''} · ${transports?.plate || ''}`"
      @close="transportSheetOpen = false"
    >
      <!-- Driver card -->
      <div class="tp-driver" v-if="transports?.driver">
        <div class="tp-driver-av">{{ transportDriverInitials }}</div>
        <div class="tp-driver-tx">
          <div class="tp-driver-name">{{ transports.driver.name }}</div>
          <div class="tp-driver-sub">{{ transports.vehicle }} · {{ transports.plate }}</div>
        </div>
        <a v-if="transports.driver.phone" :href="'tel:' + transports.driver.phone" class="tp-phone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </a>
      </div>

      <!-- Status pills -->
      <div class="tp-pills">
        <span class="pwa-pill good" v-if="transports?.status === 'confirmed'"><span class="dot"></span> Confirmed</span>
        <span class="pwa-pill warn" v-else><span class="dot"></span> Pending</span>
        <span class="pwa-pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;" v-html="icons.car" />
          {{ transports?.movements?.length || 0 }} movements
        </span>
      </div>

      <!-- Date-grouped timeline -->
      <div class="tp-timeline">
        <div v-for="group in transportByDate" :key="group.label" class="tp-date-group">
          <div class="tp-date-label">{{ group.label }}</div>
          <div class="tp-items">
            <div v-for="(m, mi) in group.items" :key="m.id" class="tp-row">
              <div class="tp-rail">
                <div class="tp-node" :class="m.done ? 'tp-node-done' : 'tp-node-upcoming'">
                  <svg v-if="m.done" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  <svg v-else-if="transportIcon(m.type) === 'match'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5a2 2 0 0 1-2 2h-1"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                  <svg v-else-if="transportIcon(m.type) === 'hotel'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="1"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/></svg>
                  <svg v-else-if="transportIcon(m.type) === 'airport'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div v-if="mi < group.items.length - 1" class="tp-stem"></div>
              </div>
              <div class="tp-card">
                <div class="tp-card-top">
                  <div class="tp-card-title-row">
                    <span class="tp-card-type">{{ m.type }}</span>
                    <span v-if="m.isUpNext" class="tp-up-next">Up next</span>
                  </div>
                  <span class="tp-card-time">{{ m.time }}</span>
                </div>
                <div class="tp-card-route">{{ m.pickup }}  →  {{ m.dropoff }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <a v-if="transports?.driver?.phone" :href="'tel:' + transports.driver.phone" class="pwa-sheet-btn" style="flex:1;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          Call driver
        </a>
        <button class="pwa-sheet-btn pri" style="flex:1;" @click="transportSheetOpen = false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Request a change
        </button>
      </template>
    </PwaSheet>
  </div>
</template>

<style>
/* ── Transport Detail Sheet ── */
.tp-driver {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 16px; border-radius: 14px;
  background: #241d1b; margin-bottom: 14px;
}
.tp-driver-av {
  width: 44px; height: 44px; border-radius: 50%;
  background: #166534; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 15px; flex-shrink: 0;
}
.tp-driver-tx { flex: 1; min-width: 0; }
.tp-driver-name { font-size: 15px; font-weight: 600; color: #fff; }
.tp-driver-sub { font-size: 12px; color: rgba(255,255,255,.5); margin-top: 1px; }
.tp-phone {
  width: 40px; height: 40px; border-radius: 50%;
  background: #166534; color: #fff; border: none;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0; transition: background .15s;
  text-decoration: none;
}
.tp-phone svg { width: 18px; height: 18px; }
.tp-phone:hover { background: #15803d; }

.tp-pills { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }

.tp-timeline { padding-top: 4px; }
.tp-date-group { margin-bottom: 0; }
.tp-date-label {
  font-size: 11px; font-weight: 700; color: var(--pwa-ink-3);
  letter-spacing: .08em; text-transform: uppercase;
  padding: 14px 0 10px;
}

.tp-items { display: flex; flex-direction: column; }

.tp-row { display: flex; gap: 12px; }
.tp-rail {
  display: flex; flex-direction: column; align-items: center;
  width: 32px; flex-shrink: 0; padding-top: 2px;
}
.tp-node {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.tp-node svg { width: 14px; height: 14px; }
.tp-node-done { background: #dcfce7; color: #16a34a; }
.tp-node-upcoming { background: #f5eef0; color: #8a1f3d; }
.tp-stem {
  width: 2px; flex: 1; min-height: 10px;
  background: var(--pwa-line-2, #ddd4c2);
  margin: 3px 0;
}

.tp-card {
  flex: 1; min-width: 0;
  border: 1px solid var(--pwa-line, #ebe4d6);
  border-radius: 12px; padding: 12px 14px;
  margin-bottom: 8px; background: var(--pwa-surface, #fff);
  box-shadow: var(--pwa-sh-sm);
}
.tp-card-top {
  display: flex; align-items: center; justify-content: space-between;
  gap: 8px; margin-bottom: 3px;
}
.tp-card-title-row { display: flex; align-items: center; gap: 8px; }
.tp-card-type { font-size: 14px; font-weight: 600; color: var(--pwa-ink, #26221e); }
.tp-up-next {
  display: inline-block; padding: 2px 8px;
  border-radius: 4px; font-size: 11px; font-weight: 700;
  background: #fce4ec; color: #8a1f3d; letter-spacing: .2px;
}
.tp-card-time {
  font-size: 15px; font-weight: 600; color: var(--pwa-ink, #26221e);
  font-family: 'IBM Plex Mono', monospace; white-space: nowrap;
}
.tp-card-route {
  font-size: 13px; color: var(--pwa-ink-2, #6c665c);
}
</style>
