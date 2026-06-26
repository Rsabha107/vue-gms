<script setup>
defineProps({ flights: Array, guest: Object, event: Object })
</script>

<template>
  <div>
    <div style="height: 54px;" />
    <div class="pwa-scr-head">
      <div class="pwa-scr-eyebrow">Air Travel</div>
      <h1 class="pwa-scr-title">Flights</h1>
    </div>

    <div v-if="!flights || flights.length === 0" style="padding: 40px 18px; text-align: center; color: var(--pwa-ink-3);">
      <p style="font-size: 15px; font-weight: 500;">No flights booked</p>
      <p style="font-size: 13px; margin-top: 6px;">Your flight itinerary will appear here once confirmed.</p>
    </div>

    <div v-for="f in flights" :key="f.id" class="pwa-bp">
      <div class="pwa-bp-top">
        <span class="pwa-bp-air">{{ f.airline || 'Qatar Airways' }}</span>
        <span class="pwa-bp-no">{{ f.flightNo || f.code }}</span>
      </div>
      <div class="pwa-bp-route">
        <div class="pwa-bp-end">
          <div class="code">{{ f.origin || f.fromCode || '—' }}</div>
          <div class="city">{{ f.originCity || '' }}</div>
        </div>
        <div class="pwa-bp-mid">
          <div class="dur">{{ f.duration || '' }}</div>
          <div class="pwa-bp-dots">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>
            </svg>
          </div>
        </div>
        <div class="pwa-bp-end" style="text-align: right;">
          <div class="code">{{ f.destination || f.toCode || '—' }}</div>
          <div class="city">{{ f.destCity || '' }}</div>
        </div>
      </div>
      <div class="pwa-bp-grid">
        <div class="pwa-bp-cell"><div class="k">DATE</div><div class="v">{{ f.date || '—' }}</div></div>
        <div class="pwa-bp-cell"><div class="k">CLASS</div><div class="v">{{ f.class || '—' }}</div></div>
        <div class="pwa-bp-cell"><div class="k">PAX</div><div class="v">{{ f.pax || 1 }}</div></div>
      </div>
    </div>
  </div>
</template>
