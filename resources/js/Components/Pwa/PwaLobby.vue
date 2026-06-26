<script setup>
import { computed } from 'vue'

const props = defineProps({
    guest: Object,
    events: Array,
    currentEvent: Object,
})

const firstName = computed(() => props.guest?.firstName || props.guest?.name?.split(' ')[0] || 'Guest')
const initials = computed(() => {
    const n = props.guest?.name || ''
    return n.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
})
const avColors = ['#8a1f3d', '#a9844a', '#3a6a8a', '#3f7d52', '#7a5a8a']
const avColor = computed(() => avColors[(props.guest?.name?.charCodeAt(0) || 0) % avColors.length])

const featuredEvent = computed(() => props.events?.[0] || props.currentEvent || null)
const otherEvents = computed(() => {
    return (props.events || []).slice(1).map(e => ({
        ...e,
        monogram: (e.logo || e.name?.[0] || '?'),
        color: ['#8a1f3d', '#3a6a8a', '#3f7d52', '#a9844a', '#7a5a8a'][(e.name?.charCodeAt(0) || 0) % 5],
    }))
})

function daysUntilEvent(e) {
    if (!e?.dateStart && !e?.date_start) return null
    const d = e.dateStart || e.date_start
    const diff = Math.ceil((new Date(d) - new Date()) / 86400000)
    return Math.max(0, diff)
}

function fmtDateRange(e) {
    if (e?.dates || e?.formatted_dates) return e.dates || e.formatted_dates
    if (!e?.dateStart && !e?.date_start) return ''
    const s = new Date(e.dateStart || e.date_start)
    const end = e.dateEnd || e.date_end ? new Date(e.dateEnd || e.date_end) : null
    const fmt = (d) => d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
    return end ? `${fmt(s)} – ${fmt(end)}` : fmt(s)
}

const emit = defineEmits(['enter'])
</script>

<template>
  <div class="pwa-lobby">
    <div style="height: 54px;" />

    <div class="pwa-lobby-pad">
      <!-- Greeting row -->
      <div class="pwa-lob-head">
        <div class="pwa-lob-greet">
          <div class="pwa-greet-av" :style="{ background: avColor }">{{ initials }}</div>
          <div class="pwa-lob-greet-tx">
            <div class="pwa-lob-hi">Welcome,</div>
            <div class="pwa-lob-nm">{{ firstName }}</div>
          </div>
          <div class="pwa-lob-flag">🇶🇦</div>
        </div>

        <div class="pwa-lob-eyebrow">Your Hospitality Concierge</div>
        <h1 class="pwa-lob-title">Guest Portal</h1>
        <p class="pwa-lob-sub">Your personal gateway to VIP hospitality — matches, flights, accommodation and ground transport, all in one place.</p>
      </div>

      <!-- Featured Event Card -->
      <button v-if="featuredEvent" class="pwa-lob-live" @click="$emit('enter', featuredEvent)">
        <div class="pwa-lob-live-bg" />
        <div class="pwa-lob-live-in">
          <div class="pwa-lob-live-top">
            <span class="live"><i></i> ACTIVE EVENT</span>
            <span class="pwa-lob-day">{{ fmtDateRange(featuredEvent) }}</span>
          </div>
          <div class="pwa-lob-live-name">{{ featuredEvent.name }}</div>
          <div class="pwa-lob-live-meta">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            {{ featuredEvent.location || '' }}
          </div>

          <!-- Next event preview -->
          <div v-if="otherEvents.length > 0" class="pwa-lob-next">
            <div class="pwa-lob-next-ic">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="pwa-lob-next-tx">
              <div class="pwa-lob-next-k">ALSO INVITED TO</div>
              <div class="pwa-lob-next-t">{{ otherEvents[0].name }}</div>
              <div class="pwa-lob-next-s">{{ otherEvents[0].location || '' }} · {{ fmtDateRange(otherEvents[0]) }}</div>
            </div>
          </div>

          <div class="pwa-lob-live-foot">
            <span class="pwa-lob-tier">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              {{ guest?.tier_info?.name || 'VIP' }}
            </span>
            <span class="pwa-lob-enter">
              Enter
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </span>
          </div>
        </div>
      </button>

      <!-- Countdown -->
      <div v-if="daysUntilEvent(featuredEvent) !== null" style="display: flex; justify-content: center; margin-top: 18px;">
        <div class="pwa-hero-countdown">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <b>{{ daysUntilEvent(featuredEvent) }}</b> days until event
        </div>
      </div>

      <!-- Other Events list -->
      <template v-if="otherEvents.length > 0">
        <div class="pwa-sec" style="padding-left: 0; padding-right: 0;">
          <span class="pwa-sec-t">Your Events</span>
          <span class="pwa-sec-line" />
        </div>
        <div class="pwa-lob-list">
          <button v-for="ev in otherEvents" :key="ev.id" class="pwa-lob-card" @click="$emit('enter', ev)">
            <div class="pwa-lob-mono" :style="{ background: `linear-gradient(135deg, ${ev.color}, ${ev.color}cc)` }">
              {{ ev.monogram }}
            </div>
            <div class="pwa-lob-card-tx">
              <div class="pwa-lob-card-top">
                <span class="pwa-lob-card-name">{{ ev.name }}</span>
                <span v-if="daysUntilEvent(ev) !== null" class="pwa-lob-cd">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  {{ daysUntilEvent(ev) }}d
                </span>
              </div>
              <div class="pwa-lob-card-meta">{{ ev.location || '' }}</div>
              <div class="pwa-lob-card-meta dates">{{ fmtDateRange(ev) }}</div>
            </div>
            <div class="pwa-lob-chev">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </div>
          </button>
        </div>
      </template>

      <!-- Footer -->
      <div class="pwa-lob-foot">
        <button class="pwa-lob-enter-btn" @click="$emit('enter', featuredEvent)">
          Enter your portal
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
        <div class="pwa-lob-sig">Guest Protocol</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pwa-lobby { min-height: 100%; }
.pwa-lobby-pad { padding: 0 18px; }
.pwa-lob-head { padding-top: 10px; padding-bottom: 6px; }
.pwa-lob-greet { display: flex; align-items: center; gap: 12px; }
.pwa-lob-greet-tx { flex: 1; min-width: 0; }
.pwa-lob-hi { font-size: 12px; color: var(--pwa-ink-2); }
.pwa-lob-nm { font-weight: 700; font-size: 15px; letter-spacing: .2px; margin-top: 1px; }
.pwa-lob-flag { font-size: 22px; line-height: 1; }
.pwa-lob-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; color: var(--pwa-maroon); margin-top: 22px; }
.pwa-lob-title { font-family: 'Instrument Serif', Georgia, serif; font-weight: 400; font-size: 40px; line-height: 1.02; letter-spacing: .3px; margin: 6px 0 0; }
.pwa-lob-sub { font-size: 13px; color: var(--pwa-ink-2); line-height: 1.5; margin-top: 10px; max-width: 330px; }
.pwa-lob-live { position: relative; display: block; width: 100%; text-align: left; margin-top: 18px; border-radius: var(--pwa-r-xl); overflow: hidden; color: #fff; box-shadow: 0 20px 48px -18px rgba(64,16,30,.62); transition: transform .16s, box-shadow .16s; border: none; cursor: pointer; }
.pwa-lob-live:hover { transform: translateY(-2px); box-shadow: 0 26px 56px -18px rgba(64,16,30,.7); }
.pwa-lob-live-bg { position: absolute; inset: 0; background: radial-gradient(120% 90% at 85% -10%, rgba(201,160,92,.55), transparent 55%), radial-gradient(110% 120% at 8% 120%, rgba(138,31,61,.5), transparent 60%), linear-gradient(150deg, var(--pwa-maroon-700) 0%, #401226 48%, var(--pwa-espresso) 100%); }
.pwa-lob-live-in { position: relative; padding: 18px 18px 17px; }
.pwa-lob-live-top { display: flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; }
.pwa-lob-live-top .live { display: inline-flex; align-items: center; gap: 6px; color: #5fd08a; }
.pwa-lob-live-top .live i { width: 7px; height: 7px; border-radius: 50%; background: #5fd08a; animation: pwa-pulse 1.8s infinite; }
.pwa-lob-day { margin-left: auto; color: var(--pwa-gold-2); letter-spacing: .6px; font-size: 10px; }
.pwa-lob-live-name { font-family: 'Instrument Serif', Georgia, serif; font-size: 32px; line-height: 1.03; margin-top: 14px; letter-spacing: .3px; }
.pwa-lob-live-meta { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: rgba(255,255,255,.78); margin-top: 7px; }
.pwa-lob-live-meta svg { width: 14px; height: 14px; color: var(--pwa-gold-2); }
.pwa-lob-next { display: flex; align-items: center; gap: 12px; margin-top: 16px; padding: 12px 13px; border-radius: 15px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18); backdrop-filter: blur(6px); }
.pwa-lob-next-ic { width: 40px; height: 40px; border-radius: 12px; flex: none; display: grid; place-items: center; background: rgba(255,255,255,.14); color: var(--pwa-gold-2); }
.pwa-lob-next-ic svg { width: 20px; height: 20px; }
.pwa-lob-next-tx { flex: 1; min-width: 0; }
.pwa-lob-next-k { font-size: 9.5px; font-weight: 700; letter-spacing: .9px; text-transform: uppercase; color: rgba(255,255,255,.6); }
.pwa-lob-next-t { font-weight: 700; font-size: 14px; margin-top: 2px; }
.pwa-lob-next-s { font-size: 11.5px; color: rgba(255,255,255,.72); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pwa-lob-live-foot { display: flex; align-items: center; gap: 10px; margin-top: 16px; }
.pwa-lob-tier { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; color: rgba(255,255,255,.86); }
.pwa-lob-tier svg { width: 14px; height: 14px; color: var(--pwa-gold-2); }
.pwa-lob-enter { margin-left: auto; display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border-radius: 12px; background: #fff; color: var(--pwa-espresso); font-size: 13px; font-weight: 700; }
.pwa-lob-enter svg { width: 15px; height: 15px; }
.pwa-lob-list { display: flex; flex-direction: column; gap: 11px; }
.pwa-lob-card { display: flex; align-items: center; gap: 13px; width: 100%; text-align: left; padding: 14px; border-radius: var(--pwa-r-lg); background: var(--pwa-surface); border: 1px solid var(--pwa-line); box-shadow: var(--pwa-sh-sm); transition: .14s; cursor: pointer; }
.pwa-lob-card:hover { box-shadow: var(--pwa-sh); transform: translateY(-2px); border-color: var(--pwa-line-2); }
.pwa-lob-mono { width: 50px; height: 50px; border-radius: 14px; flex: none; display: grid; place-items: center; color: #fff; font-family: 'Instrument Serif', Georgia, serif; font-size: 23px; box-shadow: var(--pwa-sh-sm); }
.pwa-lob-card-tx { flex: 1; min-width: 0; }
.pwa-lob-card-top { display: flex; align-items: baseline; gap: 9px; }
.pwa-lob-card-name { font-family: 'Instrument Serif', Georgia, serif; font-size: 19px; line-height: 1.05; letter-spacing: .2px; flex: 1; min-width: 0; }
.pwa-lob-cd { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 700; color: var(--pwa-maroon); background: var(--pwa-maroon-soft); padding: 3px 8px; border-radius: 8px; white-space: nowrap; flex: none; }
.pwa-lob-card-meta { font-size: 12px; color: var(--pwa-ink-2); margin-top: 4px; }
.pwa-lob-card-meta.dates { font-size: 11px; color: var(--pwa-ink-3); margin-top: 1px; }
.pwa-lob-chev { color: var(--pwa-ink-3); flex: none; }
.pwa-lob-chev svg { width: 18px; height: 18px; }
.pwa-lob-foot { padding: 24px 0 32px; text-align: center; }
.pwa-lob-enter-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px; border-radius: 15px; font-size: 15px; font-weight: 700; background: var(--pwa-espresso); color: #fff; border: none; cursor: pointer; transition: .14s; }
.pwa-lob-enter-btn:hover { background: #3a302c; }
.pwa-lob-enter-btn svg { width: 16px; height: 16px; }
.pwa-lob-sig { font-size: 11px; font-weight: 600; letter-spacing: .6px; color: var(--pwa-ink-3); margin-top: 10px; }
</style>
