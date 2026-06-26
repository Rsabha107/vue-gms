<script setup>
import { ref, provide } from 'vue'

const props = defineProps({
    guest: { type: Object, default: () => ({}) },
    event: { type: Object, default: () => ({}) },
    events: { type: Array, default: () => [] },
})

const activeTab = ref('lobby')
const showTabs = ref(false)
provide('activeTab', activeTab)
provide('showTabs', showTabs)
provide('guest', props.guest)
provide('event', props.event)
provide('events', props.events)

function enterPortal() {
    activeTab.value = 'home'
    showTabs.value = true
}
provide('enterPortal', enterPortal)

function navigateTo(tab) {
    activeTab.value = tab
    if (!showTabs.value) showTabs.value = true
}
provide('navigateTo', navigateTo)

const tabs = [
    { id: 'home', label: 'Home', icon: 'home' },
    { id: 'trip', label: 'My Trip', icon: 'map' },
    { id: 'tickets', label: 'Tickets', icon: 'ticket' },
    { id: 'flights', label: 'Flights', icon: 'plane' },
    { id: 'profile', label: 'Profile', icon: 'user' },
]

const toasts = ref([])
function addToast(msg, type = 'success') {
    const id = Date.now()
    toasts.value.push({ id, msg, type })
    setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id) }, 3000)
}
provide('toast', addToast)

const icons = {
    home: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    map: '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>',
    ticket: '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/>',
    plane: '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
    user: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    bell: '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    calendar: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
    'map-pin': '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
    car: '<path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/>',
    building: '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>',
    check: '<polyline points="20 6 9 17 4 12"/>',
    'chevron-right': '<polyline points="9 18 15 12 9 6"/>',
    clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    qr: '<rect x="2" y="2" width="8" height="8" rx="1"/><rect x="14" y="2" width="8" height="8" rx="1"/><rect x="2" y="14" width="8" height="8" rx="1"/><rect x="14" y="14" width="4" height="4" rx="1"/><path d="M22 14h-2v4h-4v4h6z"/>',
}
</script>

<template>
  <div class="pwa-root">
    <!-- Screen area -->
    <div class="pwa-screen">
      <slot />
    </div>

    <!-- Bottom tab bar -->
    <div v-if="showTabs" class="pwa-tab">
      <button
        v-for="tab in tabs" :key="tab.id"
        class="pwa-tab-btn"
        :class="{ on: activeTab === tab.id }"
        @click="activeTab = tab.id"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons[tab.icon]" />
        <span class="pwa-tab-label">{{ tab.label }}</span>
      </button>
    </div>

    <!-- Toast notifications -->
    <div class="pwa-toasts">
      <div v-for="t in toasts" :key="t.id" class="pwa-toast" :class="t.type">{{ t.msg }}</div>
    </div>
  </div>
</template>

<style>
/* ═══ PWA App Shell ═══ */
.pwa-root {
  height: 100vh; display: flex; flex-direction: column;
  background: var(--pwa-canvas); font-family: 'Hanken Grotesk', system-ui, sans-serif;
  color: var(--pwa-ink); -webkit-font-smoothing: antialiased; overflow: hidden;
}
.pwa-screen { flex: 1; overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch; }
.pwa-screen::-webkit-scrollbar { width: 0; }

/* ═══ Tab Bar ═══ */
.pwa-tab {
  flex: none; height: var(--pwa-tab-h); position: relative; z-index: 10;
  padding: 9px 12px calc(env(safe-area-inset-bottom, 0px) + 22px);
  background: linear-gradient(to top, rgba(246,241,233,.98) 62%, rgba(246,241,233,0));
  display: flex; align-items: flex-start; justify-content: space-around;
}
.pwa-tab::before {
  content: ""; position: absolute; inset: 0; top: 8px; margin: 0 10px; height: 58px;
  border-radius: 20px; background: rgba(255,255,255,.82); backdrop-filter: blur(14px) saturate(160%);
  border: 1px solid rgba(255,255,255,.7); box-shadow: 0 8px 24px -10px rgba(38,34,30,.28);
}
.pwa-tab-btn {
  position: relative; z-index: 1; flex: 1; display: flex; flex-direction: column;
  align-items: center; gap: 3px; padding-top: 13px; color: var(--pwa-ink-3); transition: .14s;
  background: none; border: none; cursor: pointer;
}
.pwa-tab-btn svg { width: 23px; height: 23px; }
.pwa-tab-label { font-size: 9.5px; font-weight: 600; letter-spacing: .2px; }
.pwa-tab-btn.on { color: var(--pwa-maroon); }

/* ═══ Greeting ═══ */
.pwa-greet { display: flex; align-items: center; gap: 12px; padding: 6px 18px 4px; }
.pwa-greet-av {
  width: 44px; height: 44px; border-radius: 14px; flex: none; display: grid; place-items: center;
  color: #fff; font-weight: 700; font-size: 15px; letter-spacing: .3px; box-shadow: var(--pwa-sh-sm);
}
.pwa-greet-tx { flex: 1; min-width: 0; }
.pwa-greet-hi { font-size: 12.5px; color: var(--pwa-ink-2); }
.pwa-greet-nm { font-family: 'Instrument Serif', Georgia, serif; font-size: 22px; line-height: 1; margin-top: 2px; }
.pwa-greet-bell {
  position: relative; width: 42px; height: 42px; border-radius: 13px; background: var(--pwa-surface);
  border: 1px solid var(--pwa-line); display: grid; place-items: center; color: var(--pwa-ink);
  box-shadow: var(--pwa-sh-sm); flex: none; cursor: pointer;
}
.pwa-greet-bell svg { width: 20px; height: 20px; }

/* ═══ Hero (Up Next) ═══ */
.pwa-hero {
  position: relative; border-radius: var(--pwa-r-xl); overflow: hidden; color: #fff;
  box-shadow: 0 18px 44px -18px rgba(64,16,30,.6); margin: 14px 18px;
}
.pwa-hero-bg {
  position: absolute; inset: 0;
  background: radial-gradient(120% 90% at 85% -10%, rgba(201,160,92,.55), transparent 55%),
    radial-gradient(110% 120% at 8% 120%, rgba(138,31,61,.5), transparent 60%),
    linear-gradient(150deg, var(--pwa-maroon-700) 0%, #401226 48%, var(--pwa-espresso) 100%);
}
.pwa-hero-in { position: relative; padding: 18px 19px 19px; }
.pwa-hero-top {
  display: flex; align-items: center; gap: 9px; font-size: 11px; font-weight: 700;
  letter-spacing: 1.2px; text-transform: uppercase; color: var(--pwa-gold-2);
}
.pwa-hero-live { display: inline-flex; align-items: center; gap: 6px; }
.pwa-hero-live i {
  width: 7px; height: 7px; border-radius: 50%; background: #5fd08a;
  box-shadow: 0 0 0 0 rgba(95,208,138,.7); animation: pwa-pulse 1.8s infinite;
}
@keyframes pwa-pulse { 0% { box-shadow: 0 0 0 0 rgba(95,208,138,.6); } 70% { box-shadow: 0 0 0 7px rgba(95,208,138,0); } 100% { box-shadow: 0 0 0 0 rgba(95,208,138,0); } }
.pwa-hero-kind { margin-left: auto; display: inline-flex; align-items: center; gap: 6px; color: rgba(255,255,255,.82); letter-spacing: .6px; }
.pwa-hero-kind svg { width: 15px; height: 15px; }
.pwa-hero-title { font-family: 'Instrument Serif', Georgia, serif; font-size: 30px; line-height: 1.05; margin: 13px 0 0; letter-spacing: .3px; }
.pwa-hero-sub { font-size: 13px; color: rgba(255,255,255,.78); margin-top: 6px; }
.pwa-hero-foot { display: flex; align-items: center; gap: 10px; margin-top: 18px; }
.pwa-hero-when { display: flex; align-items: baseline; gap: 7px; }
.pwa-hero-when .t { font-family: 'IBM Plex Mono', monospace; font-size: 22px; font-weight: 600; }
.pwa-hero-when .d { font-size: 12px; color: rgba(255,255,255,.7); }
.pwa-hero-countdown {
  display: inline-flex; align-items: center; gap: 6px; margin-top: 14px; padding: 5px 11px;
  border-radius: 20px; white-space: nowrap; background: rgba(255,255,255,.14);
  border: 1px solid rgba(255,255,255,.22); font-size: 12px; font-weight: 600; color: #fff;
}
.pwa-hero-countdown b { font-family: 'IBM Plex Mono', monospace; color: var(--pwa-gold-2); }
.pwa-hero-cta {
  margin-left: auto; display: inline-flex; align-items: center; gap: 7px; padding: 10px 15px;
  border-radius: 12px; background: rgba(255,255,255,.16); backdrop-filter: blur(6px);
  border: 1px solid rgba(255,255,255,.28); color: #fff; font-size: 13px; font-weight: 600;
  transition: .14s; cursor: pointer;
}
.pwa-hero-cta:hover { background: rgba(255,255,255,.26); }

/* ═══ Section Labels ═══ */
.pwa-sec { display: flex; align-items: center; gap: 10px; padding: 18px 18px 10px; }
.pwa-sec-t { font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--pwa-ink-3); }
.pwa-sec-line { flex: 1; height: 1px; background: var(--pwa-line); }

/* ═══ Timeline ═══ */
.pwa-tl { position: relative; padding: 0 18px; }
.pwa-tl-item { display: grid; grid-template-columns: 54px 26px 1fr; gap: 11px; align-items: stretch; }
.pwa-tl-time { text-align: right; padding-top: 13px; }
.pwa-tl-time .t { font-family: 'IBM Plex Mono', monospace; font-size: 13px; font-weight: 600; }
.pwa-tl-time .d { font-size: 10.5px; color: var(--pwa-ink-3); }
.pwa-tl-spine { position: relative; display: flex; justify-content: center; }
.pwa-tl-spine::before { content: ""; position: absolute; top: 0; bottom: 0; width: 2px; background: var(--pwa-line-2); }
.pwa-tl-item:first-child .pwa-tl-spine::before { top: 16px; }
.pwa-tl-item:last-child .pwa-tl-spine::before { bottom: calc(100% - 30px); }
.pwa-tl-node {
  position: relative; z-index: 1; margin-top: 9px; width: 26px; height: 26px; border-radius: 50%;
  background: var(--pwa-surface); border: 2px solid var(--pwa-line-2); display: grid; place-items: center;
  color: var(--pwa-ink-2);
}
.pwa-tl-node svg { width: 13px; height: 13px; }
.pwa-tl-item.done .pwa-tl-node { background: var(--pwa-good-soft); border-color: var(--pwa-good); color: var(--pwa-good); }
.pwa-tl-item.next .pwa-tl-node { background: var(--pwa-maroon); border-color: var(--pwa-maroon); color: #fff; box-shadow: 0 0 0 4px var(--pwa-maroon-soft); }
.pwa-tl-card {
  flex: 1; min-width: 0; background: var(--pwa-surface); border: 1px solid var(--pwa-line);
  border-radius: var(--pwa-r); padding: 11px 13px; margin: 6px 0; box-shadow: var(--pwa-sh-sm);
  transition: .13s; display: flex; align-items: center; gap: 10px; cursor: pointer;
}
.pwa-tl-item.next .pwa-tl-card { border-color: var(--pwa-maroon); box-shadow: 0 6px 18px -10px rgba(138,31,61,.4); }
.pwa-tl-tx { flex: 1; min-width: 0; }
.pwa-tl-t { font-weight: 600; font-size: 13.5px; line-height: 1.2; }
.pwa-tl-s { font-size: 11.5px; color: var(--pwa-ink-2); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pwa-tl-item.done .pwa-tl-t { color: var(--pwa-ink-2); }
.pwa-tl-chev { color: var(--pwa-ink-3); flex: none; }
.pwa-tl-chev svg { width: 16px; height: 16px; }
.pwa-tl-now { display: flex; align-items: center; gap: 9px; padding: 2px 0 12px 65px; margin-top: -4px; }
.pwa-tl-now .ln { flex: 1; height: 1px; background: repeating-linear-gradient(90deg, var(--pwa-maroon) 0 4px, transparent 4px 8px); opacity: .5; }
.pwa-tl-now span { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--pwa-maroon); }

/* ═══ Service Cards Grid ═══ */
.pwa-svc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 11px; padding: 0 18px; }
.pwa-svc {
  background: var(--pwa-surface); border: 1px solid var(--pwa-line); border-radius: var(--pwa-r-lg);
  padding: 14px; box-shadow: var(--pwa-sh-sm); text-align: left; transition: .14s;
  position: relative; overflow: hidden; cursor: pointer;
}
.pwa-svc:hover { box-shadow: var(--pwa-sh); transform: translateY(-2px); }
.pwa-svc-ic {
  width: 38px; height: 38px; border-radius: 11px; background: var(--pwa-maroon-soft);
  color: var(--pwa-maroon); display: grid; place-items: center; margin-bottom: 11px;
}
.pwa-svc-ic svg { width: 20px; height: 20px; }
.pwa-svc-name { font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--pwa-ink-3); }
.pwa-svc-line { font-weight: 600; font-size: 13.5px; line-height: 1.25; margin-top: 4px; }
.pwa-svc-sub { font-size: 11.5px; color: var(--pwa-ink-2); margin-top: 3px; line-height: 1.35; }
.pwa-svc-foot { display: flex; align-items: center; gap: 6px; margin-top: 11px; }

/* ═══ Pills ═══ */
.pwa-pill {
  display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700;
  padding: 3px 9px; border-radius: 20px; background: var(--pwa-surface-2); color: var(--pwa-ink-2);
  border: 1px solid var(--pwa-line-2); white-space: nowrap; letter-spacing: .2px;
}
.pwa-pill .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.pwa-pill.good { background: var(--pwa-good-soft); color: var(--pwa-good); border-color: transparent; }
.pwa-pill.warn { background: var(--pwa-warn-soft); color: var(--pwa-warn); border-color: transparent; }
.pwa-pill.maroon { background: var(--pwa-maroon-soft); color: var(--pwa-maroon); border-color: transparent; }
.pwa-pill.gold { background: var(--pwa-gold-soft); color: #8a6a2a; border-color: transparent; }

/* ═══ Tier Badge ═══ */
.pwa-tier { font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; padding: 3px 9px; border-radius: 7px; }
.pwa-tier.gold { background: var(--pwa-gold-soft); color: #8a6a2a; }
.pwa-tier.vvip { background: var(--pwa-maroon-soft); color: var(--pwa-maroon); }

/* ═══ Screen Header ═══ */
.pwa-scr-head { padding: 8px 18px 14px; }
.pwa-scr-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; color: var(--pwa-maroon); }
.pwa-scr-title { font-family: 'Instrument Serif', Georgia, serif; font-weight: 400; font-size: 38px; line-height: 1.02; letter-spacing: .3px; margin: 5px 0 0; }

/* ═══ Bottom space ═══ */
.pwa-bottom-space { height: calc(var(--pwa-tab-h) + 8px); }

/* ═══ Screen animation ═══ */
.pwa-scr-anim { animation: pwa-scrIn .34s cubic-bezier(.2,.7,.3,1); }
@keyframes pwa-scrIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

/* ═══ Toast ═══ */
.pwa-toasts { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 100; display: flex; flex-direction: column; gap: 8px; }
.pwa-toast {
  padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 600;
  background: var(--pwa-espresso); color: #fff; box-shadow: var(--pwa-sh);
  animation: pwa-scrIn .3s ease-out;
}

/* ═══ Card ═══ */
.pwa-card { background: var(--pwa-surface); border: 1px solid var(--pwa-line); border-radius: var(--pwa-r-lg); box-shadow: var(--pwa-sh-sm); }
.pwa-card-pad { padding: 16px; }

/* ═══ Boarding Pass ═══ */
.pwa-bp { position: relative; border-radius: var(--pwa-r-lg); overflow: hidden; background: var(--pwa-surface); border: 1px solid var(--pwa-line); box-shadow: var(--pwa-sh); margin: 0 18px 14px; }
.pwa-bp-top { background: linear-gradient(140deg, var(--pwa-maroon), var(--pwa-maroon-700)); color: #fff; padding: 15px 17px; display: flex; align-items: center; gap: 10px; }
.pwa-bp-air { font-size: 12.5px; font-weight: 700; letter-spacing: .4px; }
.pwa-bp-no { margin-left: auto; font-family: 'IBM Plex Mono', monospace; font-size: 12.5px; background: rgba(255,255,255,.16); padding: 3px 9px; border-radius: 7px; }
.pwa-bp-route { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 10px; padding: 18px 17px 14px; }
.pwa-bp-end { }
.pwa-bp-end .code { font-family: 'Instrument Serif', Georgia, serif; font-size: 36px; line-height: 1; }
.pwa-bp-end .city { font-size: 12px; color: var(--pwa-ink-2); margin-top: 2px; }
.pwa-bp-mid { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.pwa-bp-mid .dur { font-size: 11px; color: var(--pwa-ink-3); font-family: 'IBM Plex Mono', monospace; }
.pwa-bp-dots { width: 100%; height: 1px; background: repeating-linear-gradient(90deg, var(--pwa-line-2) 0 5px, transparent 5px 10px); position: relative; }
.pwa-bp-dots svg { position: absolute; left: 50%; top: 50%; transform: translate(-50%,-50%); width: 16px; height: 16px; color: var(--pwa-maroon); background: var(--pwa-surface); }
.pwa-bp-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1px; border-top: 1px solid var(--pwa-line); background: var(--pwa-line); }
.pwa-bp-cell { background: var(--pwa-surface); padding: 10px 14px; }
.pwa-bp-cell .k { font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--pwa-ink-3); }
.pwa-bp-cell .v { font-size: 14px; font-weight: 600; margin-top: 2px; font-family: 'IBM Plex Mono', monospace; }

/* ═══ Match Ticket ═══ */
.pwa-ticket { position: relative; border-radius: var(--pwa-r-lg); overflow: hidden; margin: 0 18px 14px; box-shadow: var(--pwa-sh); }
.pwa-ticket-bg {
  position: absolute; inset: 0;
  background: linear-gradient(150deg, var(--pwa-maroon-700) 0%, #401226 48%, var(--pwa-espresso) 100%);
}
.pwa-ticket-in { position: relative; padding: 18px; color: #fff; }
.pwa-ticket-stage { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--pwa-gold-2); margin-bottom: 8px; }
.pwa-ticket-teams { font-family: 'Instrument Serif', Georgia, serif; font-size: 24px; line-height: 1.1; margin-bottom: 10px; }
.pwa-ticket-meta { display: flex; gap: 12px; font-size: 12px; color: rgba(255,255,255,.7); flex-wrap: wrap; }
.pwa-ticket-seat { margin-top: 12px; display: flex; gap: 16px; }
.pwa-ticket-seat .k { font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: rgba(255,255,255,.5); }
.pwa-ticket-seat .v { font-size: 15px; font-weight: 700; font-family: 'IBM Plex Mono', monospace; margin-top: 2px; }

/* ═══ QR Code ═══ */
.pwa-qr { display: flex; justify-content: center; padding: 16px; }
.pwa-qr-box {
  width: 160px; height: 160px; border-radius: 14px; background: #fff;
  border: 1px solid var(--pwa-line); display: grid; place-items: center;
  box-shadow: var(--pwa-sh-sm); padding: 12px;
}

/* ═══ Profile Info Rows ═══ */
.pwa-info-row { display: flex; align-items: center; gap: 12px; padding: 12px 18px; border-bottom: 1px solid var(--pwa-line); }
.pwa-info-ic { width: 36px; height: 36px; border-radius: 10px; background: var(--pwa-surface-2); display: grid; place-items: center; color: var(--pwa-ink-2); flex: none; }
.pwa-info-ic svg { width: 18px; height: 18px; }
.pwa-info-tx { flex: 1; min-width: 0; }
.pwa-info-k { font-size: 11px; font-weight: 600; color: var(--pwa-ink-3); text-transform: uppercase; letter-spacing: .3px; }
.pwa-info-v { font-size: 14px; font-weight: 500; margin-top: 1px; }

/* ═══ Sheet Content ═══ */
.pwa-sheet-kv { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--pwa-line); font-size: 14px; }
.pwa-sheet-kv:last-child { border-bottom: none; }
.pwa-sheet-kv .k { color: var(--pwa-ink-3); }
.pwa-sheet-kv .v { font-weight: 600; text-align: right; font-family: 'IBM Plex Mono', monospace; }
.pwa-sheet-btn {
  flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  padding: 12px 16px; border-radius: 13px; font-size: 13.5px; font-weight: 600;
  background: var(--pwa-surface); border: 1px solid var(--pwa-line-2); color: var(--pwa-ink);
  cursor: pointer; transition: .13s;
}
.pwa-sheet-btn:hover { border-color: var(--pwa-ink-3); }
.pwa-sheet-btn.pri { background: var(--pwa-maroon); border-color: var(--pwa-maroon); color: #fff; }
.pwa-sheet-btn.pri:hover { background: var(--pwa-maroon-600); }
</style>
