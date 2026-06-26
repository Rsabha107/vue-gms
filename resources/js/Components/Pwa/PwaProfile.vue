<script setup>
import { computed } from 'vue'

const props = defineProps({ guest: Object, event: Object, services: Object })

const initials = computed(() => {
    const n = props.guest?.name || ''
    return n.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
})
const avColors = ['#8a1f3d', '#a9844a', '#3a6a8a', '#3f7d52', '#7a5a8a']
const avColor = computed(() => avColors[(props.guest?.name?.charCodeAt(0) || 0) % avColors.length])

const infoRows = computed(() => [
    { icon: 'user', k: 'Full Name', v: props.guest?.name || '—' },
    { icon: 'mail', k: 'Email', v: props.guest?.email || '—' },
    { icon: 'phone', k: 'Phone', v: props.guest?.phone || '—' },
    { icon: 'flag', k: 'Nationality', v: props.guest?.nationality || '—' },
    { icon: 'tag', k: 'Reference', v: props.guest?.reference_number || props.guest?.id || '—' },
    { icon: 'star', k: 'Service Level', v: props.guest?.tier_info?.name || props.guest?.tier || '—' },
    { icon: 'users', k: 'Group', v: props.guest?.group?.name || props.guest?.group?.label || '—' },
])

const icons = {
    user: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    mail: '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
    phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
    flag: '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
    tag: '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
    star: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
    users: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    calendar: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
}
</script>

<template>
  <div>
    <div style="height: 54px;" />

    <!-- Profile header -->
    <div style="text-align: center; padding: 10px 18px 24px;">
      <div class="pwa-greet-av" :style="{ background: avColor, width: '72px', height: '72px', borderRadius: '22px', fontSize: '24px', margin: '0 auto 12px' }">{{ initials }}</div>
      <h2 style="font-family: 'Instrument Serif', Georgia, serif; font-size: 28px; margin: 0;">{{ guest?.name }}</h2>
      <div style="font-size: 13px; color: var(--pwa-ink-2); margin-top: 4px;">{{ guest?.title || '' }}</div>
      <div style="display: flex; justify-content: center; gap: 8px; margin-top: 10px;">
        <span class="pwa-tier vvip">{{ guest?.tier_info?.name || guest?.tier || 'Guest' }}</span>
        <span class="pwa-pill gold"><span class="dot" /> {{ event?.name || 'Event' }}</span>
      </div>
    </div>

    <!-- QR Badge -->
    <div class="pwa-sec">
      <span class="pwa-sec-t">Your QR Badge</span>
      <span class="pwa-sec-line" />
    </div>
    <div class="pwa-qr">
      <div class="pwa-qr-box">
        <div style="text-align: center; color: var(--pwa-ink-3); font-size: 12px;">
          <div style="font-size: 48px; margin-bottom: 6px;">📱</div>
          <div style="font-weight: 600;">Scan for access</div>
          <div style="font-size: 10px; margin-top: 2px;">{{ guest?.reference_number || guest?.id || '—' }}</div>
        </div>
      </div>
    </div>

    <!-- Info rows -->
    <div class="pwa-sec">
      <span class="pwa-sec-t">Personal Details</span>
      <span class="pwa-sec-line" />
    </div>
    <div class="pwa-card" style="margin: 0 18px; overflow: hidden;">
      <div v-for="row in infoRows" :key="row.k" class="pwa-info-row">
        <div class="pwa-info-ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons[row.icon]" />
        </div>
        <div class="pwa-info-tx">
          <div class="pwa-info-k">{{ row.k }}</div>
          <div class="pwa-info-v">{{ row.v }}</div>
        </div>
      </div>
    </div>

    <!-- Event info -->
    <div class="pwa-sec" style="margin-top: 8px;">
      <span class="pwa-sec-t">Event</span>
      <span class="pwa-sec-line" />
    </div>
    <div class="pwa-card" style="margin: 0 18px; overflow: hidden;">
      <div class="pwa-info-row">
        <div class="pwa-info-ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons.calendar" />
        </div>
        <div class="pwa-info-tx">
          <div class="pwa-info-k">Event</div>
          <div class="pwa-info-v">{{ event?.name || '—' }}</div>
        </div>
      </div>
      <div class="pwa-info-row" v-if="event?.location">
        <div class="pwa-info-ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        </div>
        <div class="pwa-info-tx">
          <div class="pwa-info-k">Location</div>
          <div class="pwa-info-v">{{ event.location }}</div>
        </div>
      </div>
      <div class="pwa-info-row" v-if="event?.dates || event?.formatted_dates">
        <div class="pwa-info-ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons.calendar" />
        </div>
        <div class="pwa-info-tx">
          <div class="pwa-info-k">Dates</div>
          <div class="pwa-info-v">{{ event.dates || event.formatted_dates }}</div>
        </div>
      </div>
    </div>
  </div>
</template>
