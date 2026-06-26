<script setup>
import { ref, computed, inject } from 'vue'
import PwaSheet from './PwaSheet.vue'

const props = defineProps({ matches: Array, guest: Object })

const DAYS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']
const MONTHS = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

function fmtDate(d) {
    if (!d) return ''
    const p = d.split('-')
    const dt = new Date(d + 'T00:00:00')
    return `${DAYS[dt.getDay()]} ${parseInt(p[2])} ${MONTHS[parseInt(p[1])]}`
}

const summary = computed(() => {
    const count = props.matches?.length || 0
    const tiers = [...new Set((props.matches || []).map(m => m.tier).filter(Boolean))]
    const blocks = [...new Set((props.matches || []).filter(m => m.seat?.block).map(m => m.seat.block))]
    const parts = [`${count} match${count !== 1 ? 'es' : ''}`]
    if (tiers.length) parts.push(`${tiers.join(' / ')} tribune`)
    if (blocks.length) parts.push(`Block ${blocks.join(', ')}`)
    return parts.join(' · ')
})

const isTbd = (name) => !name || name === 'TBD'

const sheetOpen = ref(false)
const activeMatch = ref(null)

function openMatch(m) {
    activeMatch.value = m
    sheetOpen.value = true
}
function closeMatch() {
    sheetOpen.value = false
    activeMatch.value = null
}

const changeSheetOpen = ref(false)
const changeCategory = ref('tickets')
const changeMessage = ref('')
const changeSubmitting = ref(false)

const changeCategories = [
    { id: 'tickets', label: 'Match tickets', desc: 'Seating, extra guests or access', icon: 'ticket' },
    { id: 'flights', label: 'Flights', desc: 'Schedule, route or class', icon: 'plane' },
    { id: 'hotel', label: 'Accommodation', desc: 'Dates, room type or hotel', icon: 'building' },
    { id: 'transport', label: 'Transport', desc: 'Pickup, vehicle or timing', icon: 'car' },
]

const changeCategoryIcons = {
    ticket: '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 17v2M13 11v2"/>',
    plane: '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
    building: '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>',
    car: '<path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/>',
}

function openChangeSheet() {
    sheetOpen.value = false
    changeCategory.value = 'tickets'
    changeMessage.value = ''
    setTimeout(() => { changeSheetOpen.value = true }, 100)
}

function closeChangeSheet() {
    changeSheetOpen.value = false
}

const toast = inject('toast', () => {})

function submitChange() {
    if (!changeMessage.value.trim()) return
    changeSubmitting.value = true
    setTimeout(() => {
        changeSubmitting.value = false
        changeSheetOpen.value = false
        changeMessage.value = ''
        toast('Your change request has been submitted')
    }, 800)
}
</script>

<template>
  <div>
    <div style="height: 54px;" />

    <!-- Header -->
    <div class="tk-head">
      <div class="tk-eyebrow">Match-day access</div>
      <h1 class="tk-title">Tickets</h1>
      <div class="tk-summary">{{ summary }}</div>
    </div>

    <!-- Hint banner -->
    <div class="tk-hint">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tk-hint-ic"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      Tap a ticket for your seat, QR pass and gate
    </div>

    <!-- Empty state -->
    <div v-if="!matches?.length" style="padding: 40px 18px; text-align: center; color: var(--pwa-ink-3);">
      <p style="font-size: 15px; font-weight: 500;">No tickets assigned yet</p>
      <p style="font-size: 13px; margin-top: 6px;">Match tickets will appear here once confirmed.</p>
    </div>

    <!-- Ticket cards -->
    <div v-for="m in matches" :key="m.id" class="tk-card" @click="openMatch(m)">
      <div class="tk-card-bg" />
      <div class="tk-card-in">
        <div class="tk-top">
          <div class="tk-top-left">
            <span v-if="m.tier" class="tk-tier">{{ m.tier }}</span>
            <span class="tk-stage">{{ (m.stage || 'Match').toUpperCase() }}</span>
          </div>
          <span v-if="m.upNext" class="tk-up-next"><span class="tk-up-dot"></span> Up next</span>
        </div>

        <div class="tk-teams">
          <div class="tk-team tk-team-home">
            <span v-if="m.homeCode" class="tk-code">{{ m.homeCode }}</span>
            <span v-else-if="isTbd(m.homeTeam)" class="tk-trophy">🏆</span>
            <span class="tk-name">{{ m.homeTeam || 'TBD' }}</span>
          </div>
          <span class="tk-vs">vs</span>
          <div class="tk-team tk-team-away">
            <span class="tk-name">{{ m.awayTeam || 'TBD' }}</span>
            <span v-if="m.awayCode" class="tk-code">{{ m.awayCode }}</span>
            <span v-else-if="isTbd(m.awayTeam)" class="tk-trophy">🏆</span>
          </div>
        </div>

        <div class="tk-meta">
          <span class="tk-meta-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            {{ fmtDate(m.date) }}
          </span>
          <span class="tk-meta-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            {{ m.time || m.kickoff || '—' }}
          </span>
          <span class="tk-meta-item" v-if="m.venue || m.venueName">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            {{ m.venue || m.venueName }}
          </span>
        </div>

        <div v-if="m.seat" class="tk-seats">
          <div class="tk-seat-cell">
            <div class="tk-seat-k">BLOCK</div>
            <div class="tk-seat-v">{{ m.seat.block || '—' }}</div>
          </div>
          <div class="tk-seat-cell">
            <div class="tk-seat-k">ROW</div>
            <div class="tk-seat-v">{{ m.seat.row || '—' }}</div>
          </div>
          <div class="tk-seat-cell">
            <div class="tk-seat-k">SEATS</div>
            <div class="tk-seat-v">{{ m.seat.number || '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="pwa-bottom-space" />

    <!-- Match Detail Sheet -->
    <PwaSheet
      :open="sheetOpen"
      :title="activeMatch ? `${activeMatch.homeTeam || 'TBD'} v ${activeMatch.awayTeam || 'TBD'}` : ''"
      :subtitle="activeMatch ? `${activeMatch.stage || 'Match'} · ${activeMatch.venueFull || activeMatch.venue || ''}` : ''"
      @close="closeMatch"
    >
      <template v-if="activeMatch">
        <!-- Mini ticket card -->
        <div class="tks-card">
          <div class="tks-card-bg" />
          <div class="tks-card-in">
            <div class="tks-top">
              <div class="tks-top-left">
                <span v-if="activeMatch.tier" class="tk-tier">{{ activeMatch.tier }}</span>
                <span class="tks-stage">{{ (activeMatch.stage || 'Match').toUpperCase() }}</span>
              </div>
            </div>

            <div class="tks-teams">
              <div class="tks-team tks-team-home">
                <span v-if="activeMatch.homeCode" class="tks-code">{{ activeMatch.homeCode }}</span>
                <span v-else-if="isTbd(activeMatch.homeTeam)" class="tks-trophy">🏆</span>
                <span class="tks-name">{{ activeMatch.homeTeam || 'TBD' }}</span>
              </div>
              <span class="tks-vs">vs</span>
              <div class="tks-team tks-team-away">
                <span class="tks-name">{{ activeMatch.awayTeam || 'TBD' }}</span>
                <span v-if="activeMatch.awayCode" class="tks-code">{{ activeMatch.awayCode }}</span>
                <span v-else-if="isTbd(activeMatch.awayTeam)" class="tks-trophy">🏆</span>
              </div>
            </div>

            <div class="tks-meta">
              <span class="tks-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                {{ fmtDate(activeMatch.date) }}
              </span>
              <span class="tks-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ activeMatch.time || '—' }}
              </span>
              <span class="tks-meta-item" v-if="activeMatch.venue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                {{ activeMatch.venue }}
              </span>
            </div>

            <div v-if="activeMatch.seat" class="tks-seats">
              <div class="tks-seat-cell">
                <div class="tks-seat-k">BLOCK</div>
                <div class="tks-seat-v">{{ activeMatch.seat.block || '—' }}</div>
              </div>
              <div class="tks-seat-cell">
                <div class="tks-seat-k">ROW</div>
                <div class="tks-seat-v">{{ activeMatch.seat.row || '—' }}</div>
              </div>
              <div class="tks-seat-cell">
                <div class="tks-seat-k">SEATS</div>
                <div class="tks-seat-v">{{ activeMatch.seat.number || '—' }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- QR Code -->
        <div class="tks-qr-wrap">
          <div class="tks-qr-box">
            <svg class="tks-qr-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
              <rect x="0" y="0" width="30" height="30" rx="2" fill="#1a1210"/><rect x="4" y="4" width="22" height="22" rx="1" fill="#fff"/><rect x="8" y="8" width="14" height="14" rx="1" fill="#1a1210"/>
              <rect x="70" y="0" width="30" height="30" rx="2" fill="#1a1210"/><rect x="74" y="4" width="22" height="22" rx="1" fill="#fff"/><rect x="78" y="8" width="14" height="14" rx="1" fill="#1a1210"/>
              <rect x="0" y="70" width="30" height="30" rx="2" fill="#1a1210"/><rect x="4" y="74" width="22" height="22" rx="1" fill="#fff"/><rect x="8" y="78" width="14" height="14" rx="1" fill="#1a1210"/>
              <rect x="35" y="0" width="5" height="5" fill="#1a1210"/><rect x="45" y="0" width="5" height="5" fill="#1a1210"/><rect x="55" y="0" width="5" height="5" fill="#1a1210"/>
              <rect x="35" y="10" width="5" height="5" fill="#1a1210"/><rect x="50" y="10" width="5" height="5" fill="#1a1210"/><rect x="60" y="10" width="5" height="5" fill="#1a1210"/>
              <rect x="40" y="20" width="5" height="5" fill="#1a1210"/><rect x="50" y="20" width="5" height="5" fill="#1a1210"/>
              <rect x="0" y="35" width="5" height="5" fill="#1a1210"/><rect x="10" y="35" width="5" height="5" fill="#1a1210"/><rect x="20" y="35" width="5" height="5" fill="#1a1210"/>
              <rect x="35" y="35" width="5" height="5" fill="#1a1210"/><rect x="45" y="35" width="5" height="5" fill="#1a1210"/><rect x="55" y="35" width="5" height="5" fill="#1a1210"/>
              <rect x="70" y="35" width="5" height="5" fill="#1a1210"/><rect x="80" y="35" width="5" height="5" fill="#1a1210"/><rect x="90" y="35" width="5" height="5" fill="#1a1210"/>
              <rect x="0" y="45" width="5" height="5" fill="#1a1210"/><rect x="15" y="45" width="5" height="5" fill="#1a1210"/><rect x="25" y="45" width="5" height="5" fill="#1a1210"/>
              <rect x="40" y="45" width="5" height="5" fill="#1a1210"/><rect x="50" y="45" width="5" height="5" fill="#1a1210"/><rect x="60" y="45" width="5" height="5" fill="#1a1210"/>
              <rect x="75" y="45" width="5" height="5" fill="#1a1210"/><rect x="85" y="45" width="5" height="5" fill="#1a1210"/><rect x="95" y="45" width="5" height="5" fill="#1a1210"/>
              <rect x="5" y="55" width="5" height="5" fill="#1a1210"/><rect x="15" y="55" width="5" height="5" fill="#1a1210"/><rect x="25" y="55" width="5" height="5" fill="#1a1210"/>
              <rect x="40" y="55" width="5" height="5" fill="#1a1210"/><rect x="55" y="55" width="5" height="5" fill="#1a1210"/>
              <rect x="70" y="55" width="5" height="5" fill="#1a1210"/><rect x="80" y="55" width="5" height="5" fill="#1a1210"/><rect x="95" y="55" width="5" height="5" fill="#1a1210"/>
              <rect x="35" y="65" width="5" height="5" fill="#1a1210"/><rect x="45" y="65" width="5" height="5" fill="#1a1210"/><rect x="55" y="65" width="5" height="5" fill="#1a1210"/>
              <rect x="70" y="65" width="5" height="5" fill="#1a1210"/><rect x="85" y="65" width="5" height="5" fill="#1a1210"/><rect x="95" y="65" width="5" height="5" fill="#1a1210"/>
              <rect x="40" y="75" width="5" height="5" fill="#1a1210"/><rect x="50" y="75" width="5" height="5" fill="#1a1210"/><rect x="60" y="75" width="5" height="5" fill="#1a1210"/>
              <rect x="75" y="75" width="5" height="5" fill="#1a1210"/><rect x="85" y="75" width="5" height="5" fill="#1a1210"/>
              <rect x="35" y="85" width="5" height="5" fill="#1a1210"/><rect x="50" y="85" width="5" height="5" fill="#1a1210"/>
              <rect x="70" y="85" width="5" height="5" fill="#1a1210"/><rect x="80" y="85" width="5" height="5" fill="#1a1210"/><rect x="95" y="85" width="5" height="5" fill="#1a1210"/>
              <rect x="35" y="95" width="5" height="5" fill="#1a1210"/><rect x="45" y="95" width="5" height="5" fill="#1a1210"/><rect x="60" y="95" width="5" height="5" fill="#1a1210"/>
              <rect x="75" y="95" width="5" height="5" fill="#1a1210"/><rect x="90" y="95" width="5" height="5" fill="#1a1210"/>
            </svg>
          </div>
          <div class="tks-qr-label">Present at {{ activeMatch.access || activeMatch.tier + ' Tribune' }} · {{ activeMatch.gate || 'Gate TBD' }}</div>
        </div>

        <!-- Venue details -->
        <div class="tks-details">
          <div class="tks-detail-row">
            <span class="tks-detail-label">Venue</span>
            <span class="tks-detail-value">{{ activeMatch.venueFull || activeMatch.venue || '—' }}</span>
          </div>
          <div class="tks-detail-row">
            <span class="tks-detail-label">Entrance</span>
            <span class="tks-detail-value">{{ activeMatch.gate || '—' }}</span>
          </div>
          <div class="tks-detail-row" style="border-bottom: none;">
            <span class="tks-detail-label">Access</span>
            <span class="tks-detail-value">{{ activeMatch.access || activeMatch.tier + ' Tribune' }}</span>
          </div>
        </div>
      </template>

      <template #footer>
        <button class="pwa-sheet-btn" style="flex:1;" @click="closeMatch">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
          Wallet
        </button>
        <button class="pwa-sheet-btn pri" style="flex:1;" @click="openChangeSheet">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Request a change
        </button>
      </template>
    </PwaSheet>

    <!-- Change Request Sheet -->
    <PwaSheet
      :open="changeSheetOpen"
      title="Request a change"
      :subtitle="changeCategories.find(c => c.id === changeCategory)?.label || 'Match tickets'"
      @close="closeChangeSheet"
    >
      <!-- Category selector -->
      <div class="cr-cats">
        <button
          v-for="cat in changeCategories" :key="cat.id"
          class="cr-cat" :class="{ 'cr-cat-on': changeCategory === cat.id }"
          @click="changeCategory = cat.id"
        >
          <div class="cr-cat-ic">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="changeCategoryIcons[cat.icon]" />
          </div>
          <div class="cr-cat-tx">
            <div class="cr-cat-name">{{ cat.label }}</div>
            <div class="cr-cat-desc">{{ cat.desc }}</div>
          </div>
          <div class="cr-cat-check" v-if="changeCategory === cat.id">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
        </button>
      </div>

      <!-- Message input -->
      <div class="cr-field">
        <label class="cr-label">What would you like to change?</label>
        <textarea
          v-model="changeMessage"
          class="cr-textarea"
          placeholder="Describe your request — your guest liaison will follow up to confirm."
          rows="4"
        ></textarea>
      </div>

      <!-- Hint -->
      <div class="cr-hint">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="cr-hint-ic"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        Typically confirmed within a few hours
      </div>

      <template #footer>
        <button
          class="pwa-sheet-btn pri" style="flex:1;"
          :disabled="!changeMessage.trim() || changeSubmitting"
          @click="submitChange"
        >
          <svg v-if="!changeSubmitting" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          {{ changeSubmitting ? 'Submitting…' : 'Submit request' }}
        </button>
      </template>
    </PwaSheet>
  </div>
</template>

<style>
/* ── Ticket detail sheet (unscoped — renders in PwaSheet teleport) ── */
.tks-card {
  position: relative; border-radius: 18px; overflow: hidden;
  box-shadow: 0 8px 24px -8px rgba(64,16,30,.4); margin-bottom: 18px;
}
.tks-card-bg {
  position: absolute; inset: 0;
  background: linear-gradient(155deg, var(--pwa-maroon, #8a1f3d) 0%, #501228 48%, var(--pwa-espresso, #241d1b) 100%);
}
.tks-card-in { position: relative; color: #fff; }

.tks-top { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px 0; }
.tks-top-left { display: flex; align-items: center; gap: 8px; }
.tks-stage { font-size: 10px; font-weight: 700; letter-spacing: 1px; color: rgba(255,255,255,.7); }

.tks-teams { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 16px 10px; }
.tks-team { display: flex; align-items: baseline; gap: 5px; flex: 1; min-width: 0; }
.tks-team-home { justify-content: flex-start; }
.tks-team-away { justify-content: flex-end; text-align: right; }
.tks-code { font-family: 'IBM Plex Mono', monospace; font-size: 13px; font-weight: 700; color: rgba(255,255,255,.4); }
.tks-name { font-family: 'Instrument Serif', Georgia, serif; font-size: 22px; line-height: 1.1; }
.tks-trophy { font-size: 18px; line-height: 1; }
.tks-vs { font-size: 12px; color: rgba(255,255,255,.35); font-style: italic; flex-shrink: 0; }

.tks-meta { display: flex; align-items: center; gap: 10px; padding: 0 16px 14px; flex-wrap: wrap; }
.tks-meta-item { display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; color: rgba(255,255,255,.6); }
.tks-meta-item svg { width: 12px; height: 12px; }

.tks-seats { display: flex; background: rgba(255,255,255,.07); border-top: 1px solid rgba(255,255,255,.1); }
.tks-seat-cell { flex: 1; padding: 10px 16px; }
.tks-seat-k { font-size: 9px; font-weight: 700; letter-spacing: .5px; color: rgba(255,255,255,.35); margin-bottom: 2px; }
.tks-seat-v { font-size: 16px; font-weight: 700; font-family: 'IBM Plex Mono', monospace; }

/* QR section */
.tks-qr-wrap {
  display: flex; flex-direction: column; align-items: center;
  padding: 22px 18px 18px;
  background: var(--pwa-surface, #fff); border: 1px solid var(--pwa-line, #ebe4d6);
  border-radius: 18px; margin-bottom: 18px;
  box-shadow: var(--pwa-sh-sm);
}
.tks-qr-box {
  width: 160px; height: 160px; padding: 12px;
  display: flex; align-items: center; justify-content: center;
}
.tks-qr-svg { width: 100%; height: 100%; }
.tks-qr-label {
  font-size: 12px; color: var(--pwa-ink-3, #9c958a);
  margin-top: 14px; text-align: center;
}

/* Venue details */
.tks-details { padding: 0 4px; }
.tks-detail-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 14px 0; border-bottom: 1px solid var(--pwa-line, #ebe4d6);
  font-size: 14px;
}
.tks-detail-label { color: var(--pwa-ink-3, #9c958a); }
.tks-detail-value { font-weight: 600; color: var(--pwa-ink, #26221e); text-align: right; }

/* ── Change Request Sheet ── */
.cr-cats {
  display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto;
  -webkit-overflow-scrolling: touch; scrollbar-width: none;
  padding-bottom: 2px;
}
.cr-cats::-webkit-scrollbar { display: none; }
.cr-cat {
  position: relative; flex: none; width: 150px; min-height: 160px;
  display: flex; flex-direction: column; justify-content: flex-end;
  gap: 0; padding: 16px; border-radius: 16px;
  background: var(--pwa-surface, #fff); border: 2px solid var(--pwa-line, #ebe4d6);
  cursor: pointer; transition: border-color .15s, background .15s;
  text-align: left; overflow: hidden;
}
.cr-cat:hover { border-color: var(--pwa-line-2, #ddd4c2); }
.cr-cat-on {
  border-color: var(--pwa-maroon, #8a1f3d);
  background: rgba(138,31,61,.05);
}
.cr-cat-ic {
  width: 32px; height: 32px; border-radius: 9px;
  background: var(--pwa-surface-2, #f4efe5);
  display: flex; align-items: center; justify-content: center;
  color: var(--pwa-ink-2, #6c665c); margin-bottom: 10px;
}
.cr-cat-on .cr-cat-ic { background: var(--pwa-maroon-soft, #f6e7ec); color: var(--pwa-maroon, #8a1f3d); }
.cr-cat-ic svg { width: 16px; height: 16px; }
.cr-cat-tx { position: relative; z-index: 1; }
.cr-cat-name { font-size: 14px; font-weight: 700; color: var(--pwa-ink, #26221e); line-height: 1.2; }
.cr-cat-desc { font-size: 11px; color: var(--pwa-ink-3, #9c958a); margin-top: 3px; line-height: 1.3; }
.cr-cat-check {
  position: absolute; right: -6px; bottom: -6px;
  width: 90px; height: 90px; color: rgba(26,18,16,.12);
  pointer-events: none;
}
.cr-cat-on .cr-cat-check { color: rgba(26,18,16,.16); }
.cr-cat-check svg { width: 100%; height: 100%; }

.cr-field { margin-bottom: 14px; }
.cr-label {
  display: block; font-size: 14px; font-weight: 600;
  color: var(--pwa-ink, #26221e); margin-bottom: 8px;
}
.cr-textarea {
  width: 100%; padding: 12px 14px; border-radius: 12px;
  border: 1px solid var(--pwa-line, #ebe4d6);
  background: var(--pwa-surface, #fff);
  font-family: inherit; font-size: 14px; color: var(--pwa-ink, #26221e);
  resize: vertical; min-height: 80px; transition: border-color .15s;
  outline: none;
}
.cr-textarea:focus { border-color: var(--pwa-maroon, #8a1f3d); box-shadow: 0 0 0 3px rgba(138,31,61,.08); }
.cr-textarea::placeholder { color: var(--pwa-ink-3, #9c958a); }

.cr-hint {
  display: flex; align-items: center; gap: 7px;
  font-size: 12.5px; color: var(--pwa-ink-3, #9c958a);
  padding: 0 2px;
}
.cr-hint-ic { width: 15px; height: 15px; flex-shrink: 0; }
</style>

<style scoped>
/* ── Tickets list page (scoped) ── */
.tk-head { padding: 0 18px 12px; }
.tk-eyebrow {
  font-size: 11px; font-weight: 700; letter-spacing: 1.4px;
  text-transform: uppercase; color: var(--pwa-maroon);
}
.tk-title {
  font-family: 'Instrument Serif', Georgia, serif; font-weight: 400;
  font-size: 38px; line-height: 1.02; margin: 5px 0 0;
}
.tk-summary { font-size: 13px; color: var(--pwa-ink-2); margin-top: 4px; }

.tk-hint {
  display: flex; align-items: center; gap: 8px;
  margin: 0 18px 18px; padding: 11px 14px;
  border-radius: 12px; background: var(--pwa-surface);
  border: 1px solid var(--pwa-line);
  font-size: 13px; color: var(--pwa-ink-2);
  box-shadow: var(--pwa-sh-sm);
}
.tk-hint-ic { width: 16px; height: 16px; flex-shrink: 0; color: var(--pwa-ink-3); }

.tk-card {
  position: relative; border-radius: var(--pwa-r-xl, 24px);
  overflow: hidden; margin: 0 18px 18px;
  box-shadow: 0 12px 32px -12px rgba(64,16,30,.45);
  cursor: pointer; transition: transform .12s, box-shadow .12s;
}
.tk-card:active { transform: scale(.98); }
.tk-card-bg {
  position: absolute; inset: 0;
  background: linear-gradient(155deg, var(--pwa-maroon, #8a1f3d) 0%, #501228 48%, var(--pwa-espresso, #241d1b) 100%);
}
.tk-card-in { position: relative; color: #fff; }

.tk-top { display: flex; align-items: center; justify-content: space-between; padding: 18px 18px 0; }
.tk-top-left { display: flex; align-items: center; gap: 8px; }
.tk-tier {
  font-size: 10px; font-weight: 800; letter-spacing: .6px;
  padding: 3px 9px; border-radius: 6px;
  background: var(--pwa-gold-soft, #f3ecdd); color: #8a6a2a;
}
.tk-stage { font-size: 10.5px; font-weight: 700; letter-spacing: 1px; color: rgba(255,255,255,.75); }
.tk-up-next { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; color: #5fd08a; }
.tk-up-dot {
  width: 7px; height: 7px; border-radius: 50%; background: #5fd08a;
  animation: tk-pulse 1.8s infinite;
}
@keyframes tk-pulse {
  0% { box-shadow: 0 0 0 0 rgba(95,208,138,.6); }
  70% { box-shadow: 0 0 0 7px rgba(95,208,138,0); }
  100% { box-shadow: 0 0 0 0 rgba(95,208,138,0); }
}

.tk-teams { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 18px 18px 14px; }
.tk-team { display: flex; align-items: baseline; gap: 6px; flex: 1; min-width: 0; }
.tk-team-home { justify-content: flex-start; }
.tk-team-away { justify-content: flex-end; text-align: right; }
.tk-code { font-family: 'IBM Plex Mono', monospace; font-size: 14px; font-weight: 700; color: rgba(255,255,255,.45); }
.tk-name { font-family: 'Instrument Serif', Georgia, serif; font-size: 26px; line-height: 1.1; }
.tk-trophy { font-size: 20px; line-height: 1; }
.tk-vs { font-size: 13px; color: rgba(255,255,255,.4); font-style: italic; flex-shrink: 0; }

.tk-meta { display: flex; align-items: center; gap: 12px; padding: 0 18px 16px; flex-wrap: wrap; }
.tk-meta-item { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; color: rgba(255,255,255,.65); }
.tk-meta-item svg { width: 13px; height: 13px; }

.tk-seats { display: flex; background: rgba(255,255,255,.08); border-top: 1px solid rgba(255,255,255,.1); }
.tk-seat-cell { flex: 1; padding: 12px 18px; }
.tk-seat-k { font-size: 10px; font-weight: 700; letter-spacing: .5px; color: rgba(255,255,255,.4); margin-bottom: 3px; }
.tk-seat-v { font-size: 18px; font-weight: 700; font-family: 'IBM Plex Mono', monospace; }
</style>
