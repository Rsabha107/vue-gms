<script setup>
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsPill from '@/Components/Gms/GmsPill.vue'
import { Link } from '@inertiajs/vue3'
import { computed, ref, inject } from 'vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    event:           { type: Object, default: () => ({}) },
    countdown:       { type: Number, default: null },
    tiers:           { type: Array,  default: () => [] },
    invitationStats: { type: Object, default: () => ({}) },
    moduleCoverage:  { type: Object, default: () => ({}) },
    serviceGaps:     { type: Object, default: () => ({}) },
    alerts:          { type: Array,  default: () => [] },
    guestReadiness:  { type: Array,  default: () => [] },
    timeline:        { type: Array,  default: () => [] },
    suggestions:     { type: Array,  default: () => [] },
    portalPending:   { type: Object, default: () => ({}) },
    matches:         { type: Array,  default: () => [] },
})

const toast = inject('toast')

const MONTHS = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

function fmtDate(d) {
    if (!d) return '—'
    const p = d.split('-')
    return `${parseInt(p[2])} ${MONTHS[parseInt(p[1])]}`
}

function countdownColor() {
    if (props.countdown === null) return 'var(--gms-text-3)'
    if (props.countdown <= 14) return '#dc2626'
    if (props.countdown <= 30) return '#d97706'
    return '#16a34a'
}

const coverageModules = computed(() => Object.entries(props.moduleCoverage).map(([key, m]) => ({ key, ...m })))

const totalNeed = computed(() => coverageModules.value.reduce((s, m) => s + m.need, 0))
const totalCovered = computed(() => coverageModules.value.reduce((s, m) => s + m.covered, 0))
const overallPct = computed(() => totalNeed.value > 0 ? Math.round(totalCovered.value / totalNeed.value * 100) : 0)

// Readiness table
const tierFilter = ref('all')
const sortCol = ref('score')
const sortAsc = ref(true)

const filteredGuests = computed(() => {
    let list = props.guestReadiness
    if (tierFilter.value !== 'all') list = list.filter(g => g.tier === tierFilter.value)
    return list.slice().sort((a, b) => {
        const va = a[sortCol.value] ?? 0
        const vb = b[sortCol.value] ?? 0
        return sortAsc.value ? va - vb : vb - va
    })
})

const readinessTiers = computed(() => {
    const seen = new Map()
    props.guestReadiness.forEach(g => {
        if (!seen.has(g.tier)) seen.set(g.tier, { id: g.tier, name: g.tierName, color: g.tierColor })
    })
    return Array.from(seen.values())
})

function toggleSort(col) {
    if (sortCol.value === col) { sortAsc.value = !sortAsc.value }
    else { sortCol.value = col; sortAsc.value = true }
}

function dotClass(status) {
    if (status === 'confirmed') return 'dash-dot good'
    if (status === 'pending' || status === 'new' || status === 'change') return 'dash-dot warn'
    if (status === 'missing') return 'dash-dot bad'
    return 'dash-dot na'
}

function dotTitle(mod, status) {
    const labels = { flights: 'Flight', accomm: 'Accommodation', transport: 'Transport', seating: 'Seating', arrival: 'Arrival' }
    const label = labels[mod] || mod
    if (status === 'confirmed') return `${label}: Confirmed`
    if (status === 'pending' || status === 'new' || status === 'change') return `${label}: ${status.charAt(0).toUpperCase() + status.slice(1)}`
    if (status === 'missing') return `${label}: Missing`
    return `${label}: Not required`
}

function moduleHref(mod) {
    const map = { flights: '/gms/flights', accomm: '/gms/accommodation', transport: '/gms/transport', seating: '/gms/seating', arrival: '/gms/arrival-departure' }
    return map[mod] || '/gms'
}

const expandedAlert = ref(null)
const expandedGapModule = ref(null)

function toggleAlert(i) {
    expandedAlert.value = expandedAlert.value === i ? null : i
}

function severityColor(s) {
    if (s === 'critical') return '#dc2626'
    if (s === 'high') return '#d97706'
    if (s === 'medium') return '#3a6a8a'
    return '#6b7280'
}

function suggestionColor(s) {
    if (s === 'warn') return { bg: '#fef3c7', fg: '#92400e', border: '#fde68a' }
    if (s === 'good') return { bg: '#dcfce7', fg: '#166534', border: '#bbf7d0' }
    return { bg: '#f0f4f8', fg: '#334155', border: '#e2e8f0' }
}

// Timeline grouping
const timelineDays = computed(() => {
    const byDate = {}
    props.timeline.forEach(item => {
        if (!byDate[item.date]) {
            const p = item.date.split('-')
            const dt = new Date(item.date)
            byDate[item.date] = {
                date: item.date,
                display: fmtDate(item.date),
                weekday: dt.toLocaleDateString('en-GB', { weekday: 'short' }),
                items: [],
            }
        }
        byDate[item.date].items.push(item)
    })
    return Object.values(byDate).slice(0, 7)
})
</script>

<template>
  <div class="dash-fullpage view-anim">

    <!-- ═══════ HERO ═══════ -->
    <div class="dash-hero">
      <div class="dash-hero-content">
        <div class="dash-event-badge">
          <span class="dash-event-badge-dot"></span>
          Active event
        </div>
        <h1 class="dash-title">{{ event.name }}</h1>
        <div class="dash-meta">
          {{ event.location }} · {{ event.dates }}
        </div>
        <div class="dash-chips" v-if="invitationStats.total">
          <span class="dash-chip">{{ invitationStats.confirmed }} confirmed</span>
          <span class="dash-chip" v-if="invitationStats.pending > 0">{{ invitationStats.pending }} pending</span>
          <span class="dash-chip">{{ overallPct }}% serviced</span>
        </div>
      </div>
      <div class="dash-countdown-wrap" v-if="countdown !== null">
        <div class="dash-countdown" :style="{ '--cd-color': countdownColor() }">
          <span class="dash-cd-num">{{ countdown }}</span>
          <span class="dash-cd-label">days to go</span>
        </div>
      </div>
    </div>

    <!-- ═══════ MODULE COVERAGE ═══════ -->
    <div class="dash-section-label">Service coverage</div>
    <div class="dash-coverage-grid">
      <Link
        v-for="m in coverageModules" :key="m.key"
        :href="m.href"
        class="dash-cov-card"
      >
        <div class="dash-cov-top">
          <div class="dash-cov-ring-wrap">
            <svg class="dash-cov-ring" viewBox="0 0 36 36">
              <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--gms-border)" stroke-width="3" />
              <circle cx="18" cy="18" r="15.9" fill="none" :stroke="m.pct === 100 ? '#16a34a' : 'var(--gms-maroon)'" stroke-width="3"
                stroke-linecap="round"
                :stroke-dasharray="(m.pct * 100 / 100) + ' 100'"
                transform="rotate(-90 18 18)" />
            </svg>
            <span class="dash-cov-ring-pct">{{ m.pct }}%</span>
          </div>
          <div class="dash-cov-info">
            <div class="dash-cov-name">
              <GmsIcon :name="m.icon" :size="14" /> {{ m.name }}
            </div>
            <div class="dash-cov-count">{{ m.covered }}/{{ m.need }} <span>covered</span></div>
          </div>
        </div>
        <div class="dash-cov-statuses" v-if="m.statuses">
          <span v-if="m.statuses.confirmed" class="dash-cov-st good">{{ m.statuses.confirmed }} confirmed</span>
          <span v-if="m.statuses.new" class="dash-cov-st warn">{{ m.statuses.new }} new</span>
          <span v-if="m.statuses.pending" class="dash-cov-st warn">{{ m.statuses.pending }} pending</span>
          <span v-if="m.statuses.change" class="dash-cov-st info">{{ m.statuses.change }} change</span>
        </div>
      </Link>
    </div>

    <!-- ═══════ SERVICE GAPS ═══════ -->
    <template v-if="serviceGaps.totalGaps > 0">
      <div class="dash-section-label" style="margin-top:32px;">
        <GmsIcon name="alert-triangle" :size="14" style="color:#d97706;" /> Service gaps
      </div>
      <div class="dash-gaps-card">
        <div class="dash-gaps-header">
          <span class="dash-gaps-total">{{ serviceGaps.totalGaps }} gap{{ serviceGaps.totalGaps !== 1 ? 's' : '' }}</span>
          across {{ serviceGaps.uniqueGuests }} guest{{ serviceGaps.uniqueGuests !== 1 ? 's' : '' }}
        </div>
        <div class="dash-gaps-grid">
          <button
            v-for="(m, key) in serviceGaps.modules" :key="key"
            class="dash-gap-mod"
            :class="{ zero: m.count === 0, open: expandedGapModule === key }"
            @click="expandedGapModule = expandedGapModule === key ? null : (m.count > 0 ? key : null)"
          >
            <span class="dash-gap-icon"><GmsIcon :name="m.icon" :size="14" /></span>
            <span class="dash-gap-name">{{ m.name }}</span>
            <span class="dash-gap-count" v-if="m.count > 0">{{ m.count }} missing</span>
            <span class="dash-gap-ok" v-else><GmsIcon name="check" :size="11" /></span>
          </button>
        </div>

        <!-- Expanded guest list for selected module -->
        <div v-if="expandedGapModule && serviceGaps.modules[expandedGapModule]?.count > 0" class="dash-gaps-detail">
          <div class="dash-gaps-detail-head">
            <GmsIcon :name="serviceGaps.modules[expandedGapModule].icon" :size="13" />
            {{ serviceGaps.modules[expandedGapModule].name }} — {{ serviceGaps.modules[expandedGapModule].count }} guest{{ serviceGaps.modules[expandedGapModule].count !== 1 ? 's' : '' }} missing
            <Link :href="serviceGaps.modules[expandedGapModule].href" class="dash-alert-cta" style="margin-left:auto;">
              Go to {{ serviceGaps.modules[expandedGapModule].name }} <GmsIcon name="chevron-right" :size="12" />
            </Link>
          </div>
          <div class="dash-gaps-list">
            <div v-for="(g, gi) in serviceGaps.modules[expandedGapModule].guests" :key="gi" class="dash-alert-row">
              <GmsAvatar :name="g.name" size="sm" />
              <div class="dash-alert-row-name">{{ g.name }}</div>
              <GmsPill v-if="g.tierName" type="custom" :value="g.tierName" :bg="(g.tierColor || '#6b7280') + '1a'" :fg="g.tierColor || '#6b7280'" />
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════ ALERTS ═══════ -->
    <template v-if="alerts.length > 0">
      <div class="dash-section-label" style="margin-top:24px;">
        Action required
      </div>
      <div class="dash-alerts">
        <div v-for="(a, i) in alerts" :key="i" class="dash-alert-wrap" :style="{ '--al-color': severityColor(a.severity) }">
          <div class="dash-alert" :class="{ expanded: expandedAlert === i }" @click="toggleAlert(i)">
            <div class="dash-alert-icon">
              <GmsIcon :name="a.icon" :size="16" />
            </div>
            <div class="dash-alert-body">
              <div class="dash-alert-msg">{{ a.message }}</div>
              <div class="dash-alert-names" v-if="a.names && a.names.length > 0 && expandedAlert !== i">
                <GmsAvatar v-for="n in a.names.slice(0, 3)" :key="n" :name="n" size="sm" />
                <span v-if="a.count > 3" class="dash-alert-more">+{{ a.count - 3 }}</span>
              </div>
            </div>
            <div class="dash-alert-right">
              <Link :href="a.href" class="dash-alert-cta" @click.stop>{{ a.cta }} <GmsIcon name="chevron-right" :size="12" /></Link>
              <button class="dash-alert-toggle" :class="{ open: expandedAlert === i }">
                <GmsIcon name="chevron-down" :size="13" />
              </button>
            </div>
          </div>

          <!-- Expanded detail — grouped by guest -->
          <div v-if="expandedAlert === i && a.guests && a.guests.length > 0" class="dash-alert-detail">
            <template v-if="a.grouped">
              <div v-for="(g, gi) in a.guests" :key="gi" class="dash-ag">
                <div class="dash-ag-head">
                  <GmsAvatar :name="g.name" size="sm" />
                  <div class="dash-ag-name">{{ g.name }}</div>
                  <GmsPill v-if="g.tierName" type="custom" :value="g.tierName" :bg="(g.tierColor || '#6b7280') + '1a'" :fg="g.tierColor || '#6b7280'" />
                  <span class="dash-ag-count">{{ g.items.length }} request{{ g.items.length !== 1 ? 's' : '' }}</span>
                </div>
                <div class="dash-ag-items">
                  <Link v-for="(item, ii) in g.items" :key="ii" :href="item.href || a.href" class="dash-ag-item">
                    <span class="dash-ag-icon"><GmsIcon :name="item.icon || a.icon" :size="12" /></span>
                    <span class="dash-ag-type">{{ item.type }}</span>
                    <span v-if="item.code" class="dash-ag-code">{{ item.code }}</span>
                    <span v-if="item.status" class="dash-ag-status">{{ item.status }}</span>
                    <span v-if="item.submitted" class="dash-ag-date">{{ item.submitted }}</span>
                    <GmsIcon name="chevron-right" :size="11" style="margin-left:auto;opacity:.3;" />
                  </Link>
                </div>
              </div>
            </template>
            <template v-else>
              <div v-for="(g, gi) in a.guests" :key="gi" class="dash-alert-row">
                <GmsAvatar :name="g.name" size="sm" />
                <div class="dash-alert-row-name">{{ g.name }}</div>
                <GmsPill v-if="g.tierName" type="custom" :value="g.tierName" :bg="(g.tierColor || '#6b7280') + '1a'" :fg="g.tierColor || '#6b7280'" />
              </div>
            </template>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════ SUGGESTIONS ═══════ -->
    <div v-if="suggestions.length > 0" class="dash-suggestions">
      <div
        v-for="(s, i) in suggestions" :key="i"
        class="dash-suggestion"
        :style="{ background: suggestionColor(s.severity).bg, color: suggestionColor(s.severity).fg, borderColor: suggestionColor(s.severity).border }"
      >
        <GmsIcon :name="s.icon" :size="13" />
        {{ s.text }}
      </div>
    </div>

    <!-- ═══════ GUEST READINESS ═══════ -->
    <div class="dash-section-label" style="margin-top:36px;">Guest readiness</div>
    <div class="gms-card" style="overflow:hidden;">
      <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid var(--gms-border);">
        <div class="gms-seg" style="flex-shrink:0;">
          <button :class="{ on: tierFilter === 'all' }" @click="tierFilter = 'all'">All</button>
          <button v-for="t in readinessTiers" :key="t.id" :class="{ on: tierFilter === t.id }" @click="tierFilter = t.id">{{ t.name }}</button>
        </div>
        <span class="dash-readiness-count">{{ filteredGuests.length }} guest{{ filteredGuests.length !== 1 ? 's' : '' }}</span>
      </div>
      <div class="gms-table-wrap">
        <table class="gms-table dash-readiness-tbl">
          <thead>
            <tr>
              <th>Guest</th>
              <th class="dash-th-mod" @click="toggleSort('services.flights')" title="Flights"><GmsIcon name="plane" :size="13" /></th>
              <th class="dash-th-mod" @click="toggleSort('services.accomm')" title="Accommodation"><GmsIcon name="building" :size="13" /></th>
              <th class="dash-th-mod" @click="toggleSort('services.transport')" title="Transport"><GmsIcon name="car" :size="13" /></th>
              <th class="dash-th-mod" title="Seating"><GmsIcon name="grid" :size="13" /></th>
              <th class="dash-th-mod" title="Arrival & Dep."><GmsIcon name="arrivals" :size="13" /></th>
              <th class="dash-th-score" @click="toggleSort('score')">
                Readiness
                <GmsIcon :name="sortAsc ? 'chevron-up' : 'chevron-down'" :size="10" style="margin-left:2px;opacity:.5;" />
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="g in filteredGuests.slice(0, 20)" :key="g.id">
              <td>
                <div style="display:flex;align-items:center;gap:9px;">
                  <GmsAvatar :name="g.name" size="sm" />
                  <div>
                    <div style="font-weight:600;font-size:13px;">{{ g.name }}</div>
                    <GmsPill type="custom" :value="g.tierName" :bg="g.tierColor + '1a'" :fg="g.tierColor" style="margin-top:2px;" />
                  </div>
                </div>
              </td>
              <td v-for="mod in ['flights','accomm','transport','seating','arrival']" :key="mod" class="dash-td-dot">
                <Link v-if="g.services[mod] === 'missing'" :href="moduleHref(mod)" :title="dotTitle(mod, g.services[mod])">
                  <span :class="dotClass(g.services[mod])"></span>
                </Link>
                <span v-else :class="dotClass(g.services[mod])" :title="dotTitle(mod, g.services[mod])"></span>
              </td>
              <td>
                <div class="dash-score-wrap">
                  <div class="dash-score-bar">
                    <div class="dash-score-fill" :style="{ width: g.score + '%', background: g.score === 100 ? '#16a34a' : g.score >= 60 ? '#d97706' : '#dc2626' }" />
                  </div>
                  <span class="dash-score-text" :style="{ color: g.score === 100 ? '#16a34a' : g.score >= 60 ? '#92400e' : '#dc2626' }">{{ g.fulfilled }}/{{ g.required }}</span>
                </div>
              </td>
            </tr>
            <tr v-if="filteredGuests.length === 0">
              <td colspan="7">
                <div class="gms-empty" style="padding:30px;"><div class="gms-empty-title">No guests match this filter</div></div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="filteredGuests.length > 20" style="padding:12px 18px;text-align:center;border-top:1px solid var(--gms-border);">
        <Link href="/gms/guests" class="gms-btn gms-btn-ghost gms-btn-sm">View all {{ filteredGuests.length }} guests</Link>
      </div>
    </div>

    <!-- ═══════ TIMELINE ═══════ -->
    <template v-if="timelineDays.length > 0">
      <div class="dash-section-label" style="margin-top:36px;">Upcoming activity</div>
      <div class="dash-timeline-scroll">
        <div class="dash-timeline">
          <div v-for="day in timelineDays" :key="day.date" class="dash-tl-day">
            <div class="dash-tl-date">
              <span class="dash-tl-wd">{{ day.weekday }}</span>
              <span class="dash-tl-dd">{{ day.display }}</span>
            </div>
            <div class="dash-tl-items">
              <div v-for="(item, j) in day.items" :key="j" class="dash-tl-item">
                <span class="dash-tl-icon" :class="'dash-tl-' + item.type">
                  <GmsIcon :name="item.icon" :size="12" />
                </span>
                <div class="dash-tl-body">
                  <div class="dash-tl-label">{{ item.label }}</div>
                  <div class="dash-tl-meta" v-if="item.time || item.meta">
                    <span v-if="item.time">{{ item.time }}</span>
                    <span v-if="item.time && item.meta"> · </span>
                    <span v-if="item.meta">{{ item.meta }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

  </div>
</template>

<style scoped>
.dash-fullpage { padding: 32px 40px; min-height: 100%; }

/* ═══ Hero ═══ */
.dash-hero { display: flex; align-items: flex-end; gap: 20px; margin-bottom: 36px; flex-wrap: wrap; }
.dash-hero-content { flex: 1; min-width: 0; }
.dash-event-badge { display: flex; align-items: center; gap: 10px; color: var(--gms-maroon); font-size: 12px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase; margin-bottom: 8px; }
.dash-event-badge-dot { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; flex-shrink: 0; animation: pulse-dot 2s infinite; }
@keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }
.dash-title { font-family: var(--gms-font-display); font-size: 48px; line-height: 0.95; margin: 0; }
.dash-meta { margin-top: 10px; font-size: 15px; color: var(--gms-text-3); }
.dash-chips { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
.dash-chip { font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: var(--gms-maroon); color: white; letter-spacing: .2px; }
.dash-chip:nth-child(2) { background: #d97706; }
.dash-chip:nth-child(3) { background: #3a6a8a; }

/* Countdown */
.dash-countdown-wrap { flex-shrink: 0; }
.dash-countdown { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--cd-color); color: var(--cd-color); }
.dash-cd-num { font-family: var(--gms-font-display); font-size: 38px; line-height: 1; }
.dash-cd-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; }

/* ═══ Section labels ═══ */
.dash-section-label { font-family: var(--gms-font-display); font-size: 22px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }

/* ═══ Coverage cards ═══ */
.dash-coverage-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; }
.dash-cov-card { border: 1px solid var(--gms-border); border-radius: 13px; padding: 16px; background: var(--gms-surface); transition: .15s; text-decoration: none; color: var(--gms-text); display: flex; flex-direction: column; gap: 10px; }
.dash-cov-card:hover { border-color: var(--gms-maroon); box-shadow: 0 2px 8px rgba(0,0,0,.06); transform: translateY(-2px); }
.dash-cov-top { display: flex; align-items: center; gap: 12px; }
.dash-cov-ring-wrap { position: relative; width: 52px; height: 52px; flex-shrink: 0; }
.dash-cov-ring { width: 100%; height: 100%; }
.dash-cov-ring-pct { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; font-family: var(--gms-font-mono); }
.dash-cov-info { min-width: 0; }
.dash-cov-name { font-size: 12.5px; font-weight: 600; display: flex; align-items: center; gap: 5px; color: var(--gms-text-2); }
.dash-cov-count { font-size: 18px; font-weight: 700; font-family: var(--gms-font-display); margin-top: 2px; }
.dash-cov-count span { font-size: 11px; font-weight: 500; color: var(--gms-text-3); font-family: var(--gms-font-ui); }
.dash-cov-statuses { display: flex; gap: 8px; flex-wrap: wrap; }
.dash-cov-st { font-size: 10.5px; font-weight: 600; }
.dash-cov-st.good { color: #16a34a; }
.dash-cov-st.warn { color: #d97706; }
.dash-cov-st.info { color: #3a6a8a; }

/* ═══ Service Gaps ═══ */
.dash-gaps-card { border: 1px solid var(--gms-border); border-radius: 13px; background: var(--gms-surface); overflow: hidden; }
.dash-gaps-header { padding: 14px 18px; font-size: 13.5px; color: var(--gms-text-2); border-bottom: 1px solid var(--gms-border); }
.dash-gaps-total { font-weight: 700; color: var(--gms-text); font-size: 15px; }
.dash-gaps-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; }
.dash-gap-mod { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 18px 12px; border: none; background: none; cursor: pointer; transition: .12s; border-right: 1px solid var(--gms-border); position: relative; }
.dash-gap-mod:last-child { border-right: none; }
.dash-gap-mod:hover { background: var(--gms-bg); }
.dash-gap-mod.open { background: var(--gms-bg); }
.dash-gap-mod.open::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 24px; height: 2px; background: var(--gms-maroon); border-radius: 1px; }
.dash-gap-mod.zero { opacity: .5; cursor: default; }
.dash-gap-mod.zero:hover { background: none; }
.dash-gap-icon { width: 32px; height: 32px; border-radius: 8px; display: grid; place-items: center; background: rgba(138,31,61,.08); color: var(--gms-maroon); }
.dash-gap-mod.zero .dash-gap-icon { background: rgba(22,163,106,.08); color: #16a34a; }
.dash-gap-name { font-size: 11.5px; font-weight: 600; color: var(--gms-text-2); }
.dash-gap-count { font-size: 12px; font-weight: 700; color: #dc2626; }
.dash-gap-ok { color: #16a34a; font-size: 12px; font-weight: 600; }
.dash-gaps-detail { border-top: 1px solid var(--gms-border); }
.dash-gaps-detail-head { display: flex; align-items: center; gap: 8px; padding: 10px 18px; font-size: 12.5px; font-weight: 600; color: var(--gms-text-2); background: var(--gms-bg); border-bottom: 1px solid var(--gms-border); }
.dash-gaps-list { max-height: 240px; overflow-y: auto; padding: 4px 0; }

@media (max-width: 768px) { .dash-gaps-grid { grid-template-columns: repeat(2, 1fr); } }

/* ═══ Alerts ═══ */
.dash-alerts { display: flex; flex-direction: column; gap: 8px; }
.dash-alert-wrap { border: 1px solid var(--gms-border); border-left: 3px solid var(--al-color); border-radius: 10px; background: var(--gms-surface); transition: .12s; overflow: hidden; }
.dash-alert-wrap:hover { box-shadow: 0 1px 4px rgba(0,0,0,.06); }
.dash-alert { display: flex; align-items: center; gap: 14px; padding: 14px 18px; cursor: pointer; transition: .1s; }
.dash-alert:hover { background: var(--gms-bg); }
.dash-alert-icon { width: 36px; height: 36px; border-radius: 9px; display: grid; place-items: center; background: color-mix(in srgb, var(--al-color) 10%, transparent); color: var(--al-color); flex-shrink: 0; }
.dash-alert-body { flex: 1; min-width: 0; }
.dash-alert-msg { font-weight: 600; font-size: 13.5px; }
.dash-alert-names { display: flex; align-items: center; gap: 4px; margin-top: 6px; }
.dash-alert-more { font-size: 11px; color: var(--gms-text-3); margin-left: 2px; }
.dash-alert-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.dash-alert-cta { font-size: 12px; font-weight: 600; color: var(--gms-maroon); display: flex; align-items: center; gap: 4px; white-space: nowrap; text-decoration: none; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--gms-border); background: var(--gms-surface); transition: .1s; }
.dash-alert-cta:hover { background: var(--gms-bg); border-color: var(--gms-maroon); }
.dash-alert-toggle { background: none; border: none; cursor: pointer; color: var(--gms-text-3); padding: 4px; border-radius: 4px; transition: .15s; display: flex; align-items: center; }
.dash-alert-toggle:hover { color: var(--gms-text); }
.dash-alert-toggle.open { transform: rotate(180deg); }

.dash-alert-detail { border-top: 1px solid var(--gms-border); max-height: 340px; overflow-y: auto; }
.dash-alert-row { display: flex; align-items: center; gap: 10px; padding: 8px 18px 8px 68px; font-size: 13px; }
.dash-alert-row:hover { background: var(--gms-bg); }
.dash-alert-row-name { font-weight: 600; flex: 1; min-width: 0; }

/* Grouped guest alerts */
.dash-ag { border-bottom: 1px solid var(--gms-border); }
.dash-ag:last-child { border-bottom: none; }
.dash-ag-head { display: flex; align-items: center; gap: 10px; padding: 10px 18px; }
.dash-ag-name { font-weight: 700; font-size: 13.5px; flex: 1; min-width: 0; }
.dash-ag-count { font-size: 11px; color: var(--gms-text-3); white-space: nowrap; }
.dash-ag-items { padding: 0 18px 8px 50px; display: flex; flex-direction: column; gap: 4px; }
.dash-ag-item {
  display: flex; align-items: center; gap: 8px; padding: 6px 10px;
  border-radius: 7px; background: var(--gms-bg); text-decoration: none; color: var(--gms-text);
  font-size: 12.5px; transition: .1s;
}
.dash-ag-item:hover { background: var(--gms-border); }
.dash-ag-icon { width: 22px; height: 22px; border-radius: 5px; display: grid; place-items: center; background: var(--gms-surface); color: var(--gms-maroon); flex-shrink: 0; }
.dash-ag-type { font-weight: 600; }
.dash-ag-code { font-family: var(--gms-font-mono); font-size: 11px; color: var(--gms-text-3); }
.dash-ag-status { font-size: 10.5px; font-weight: 600; text-transform: capitalize; padding: 2px 7px; border-radius: 4px; background: rgba(217,119,6,.1); color: #92400e; }
.dash-ag-date { font-size: 11px; color: var(--gms-text-3); font-family: var(--gms-font-mono); }

/* ═══ Suggestions ═══ */
.dash-suggestions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
.dash-suggestion { display: flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 500; padding: 8px 14px; border-radius: 20px; border: 1px solid; }

/* ═══ Readiness table ═══ */
.dash-readiness-count { margin-left: auto; font-size: 12px; color: var(--gms-text-3); }
.dash-readiness-tbl th { font-size: 11px !important; }
.dash-th-mod { text-align: center !important; width: 48px; cursor: default; }
.dash-th-score { cursor: pointer; white-space: nowrap; width: 100px; }
.dash-td-dot { text-align: center; }
.dash-td-dot a { text-decoration: none; }

.dash-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; }
.dash-dot.good { background: #16a34a; }
.dash-dot.warn { background: #d97706; }
.dash-dot.bad { background: #dc2626; animation: pulse-bad 1.5s infinite; }
.dash-dot.na { background: var(--gms-border); }
@keyframes pulse-bad { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }

.dash-score-wrap { display: flex; align-items: center; gap: 8px; }
.dash-score-bar { width: 48px; height: 5px; border-radius: 3px; background: var(--gms-border); overflow: hidden; }
.dash-score-fill { height: 100%; border-radius: 3px; transition: width .3s; }
.dash-score-text { font-size: 11px; font-weight: 700; font-family: var(--gms-font-mono); white-space: nowrap; }

/* ═══ Timeline ═══ */
.dash-timeline-scroll { overflow-x: auto; padding-bottom: 4px; }
.dash-timeline { display: flex; gap: 16px; min-width: min-content; }
.dash-tl-day { min-width: 220px; max-width: 280px; border: 1px solid var(--gms-border); border-radius: 12px; background: var(--gms-surface); overflow: hidden; flex-shrink: 0; }
.dash-tl-date { padding: 10px 14px; background: var(--gms-bg); border-bottom: 1px solid var(--gms-border); display: flex; align-items: center; gap: 8px; }
.dash-tl-wd { font-weight: 700; font-size: 12px; text-transform: uppercase; color: var(--gms-maroon); }
.dash-tl-dd { font-size: 12px; color: var(--gms-text-2); font-family: var(--gms-font-mono); }
.dash-tl-items { padding: 6px 0; }
.dash-tl-item { display: flex; align-items: flex-start; gap: 9px; padding: 8px 14px; }
.dash-tl-icon { width: 24px; height: 24px; border-radius: 6px; display: grid; place-items: center; flex-shrink: 0; font-size: 12px; }
.dash-tl-match { background: rgba(138,31,61,.1); color: var(--gms-maroon); }
.dash-tl-checkin { background: rgba(63,125,82,.1); color: #3f7d52; }
.dash-tl-transport { background: rgba(176,96,56,.1); color: #b06038; }
.dash-tl-body { min-width: 0; }
.dash-tl-label { font-size: 12.5px; font-weight: 600; line-height: 1.3; }
.dash-tl-meta { font-size: 11px; color: var(--gms-text-3); margin-top: 2px; }

/* ═══ Animation ═══ */
.view-anim { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* ═══ Responsive ═══ */
@media (max-width: 1200px) {
  .dash-coverage-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
  .dash-fullpage { padding: 16px; }
  .dash-title { font-size: 32px; }
  .dash-hero { flex-direction: column; align-items: flex-start; }
  .dash-countdown-wrap { align-self: flex-start; }
  .dash-coverage-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
