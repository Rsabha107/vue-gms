<script setup>
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsPill from '@/Components/Gms/GmsPill.vue'
import { Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    event:   { type: Object, default: () => ({}) },
    stats:   { type: Object, default: () => ({}) },
    matches: { type: Array,  default: () => [] },
    guests:  { type: Array,  default: () => [] },
    tiers:   { type: Array,  default: () => [] },
})

function tierName(id) {
    return props.tiers.find(t => t.id === id)?.name ?? id
}
function tierColor(id) {
    return props.tiers.find(t => t.id === id)?.color ?? '#6b7280'
}
function fmtDate(d) {
    if (!d) return ''
    const dt = new Date(d)
    return dt.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
}
function matchMonth(d) {
    if (!d) return ''
    return new Date(d).toLocaleDateString('en-GB', { month: 'short' }).toUpperCase()
}
function matchDay(d) {
    if (!d) return ''
    return new Date(d).getDate()
}

const statCards = computed(() => [
    { label: 'Total Guests',  value: props.stats.totalGuests,  sub: `${props.stats.confirmed} confirmed`,    icon: 'users',    accent: false },
    { label: 'Invitations',   value: props.stats.invited,       sub: `${props.stats.pending} pending reply`, icon: 'mail',     accent: false },
    { label: 'Seat Fill',     value: props.stats.fillPct + '%', sub: `${props.stats.seatedGuests} seated`,  icon: 'grid',     accent: true  },
    { label: 'Flights',       value: props.stats.flightRequests, sub: 'requests logged',                     icon: 'plane',    accent: false },
    { label: 'Accommodation', value: props.stats.accommodation, sub: 'bookings',                             icon: 'building', accent: false },
    { label: 'Transport',     value: props.stats.transport,     sub: 'transfers',                            icon: 'car',      accent: false },
])
</script>

<template>
  <div class="gms-view">
    <!-- Header -->
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">{{ event.name }}</h1>
        <p class="gms-view-subtitle">{{ event.location }} · {{ event.dates }}</p>
      </div>
      <div class="gms-view-actions">
        <Link href="/gms/guests" class="gms-btn gms-btn-primary">
          <GmsIcon name="users" :size="14" />
          View Guests
        </Link>
      </div>
    </div>

    <!-- Stats -->
    <div class="gms-stats-grid">
      <div v-for="s in statCards" :key="s.label" class="gms-stat-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
          <div class="gms-stat-label">{{ s.label }}</div>
          <GmsIcon :name="s.icon" :size="14" style="color:var(--gms-text-3);" />
        </div>
        <div class="gms-stat-value" :style="s.accent ? 'color:var(--gms-maroon)' : ''">{{ s.value }}</div>
        <div class="gms-stat-sub">{{ s.sub }}</div>
      </div>
    </div>

    <!-- Main grid -->
    <div class="gms-grid-2" style="gap:20px;">
      <!-- Upcoming matches -->
      <div class="gms-card">
        <div class="gms-card-header">
          <span class="gms-card-title">Upcoming Fixtures</span>
          <Link href="/gms/seating" class="gms-btn gms-btn-ghost gms-btn-sm">
            View all
          </Link>
        </div>
        <div class="gms-card-body-0">
          <div v-for="m in matches" :key="m.id" class="gms-match-card"
               style="border:none;border-radius:0;border-bottom:1px solid var(--gms-border-2);padding:14px 20px;"
               @click="router.visit('/gms/seating')">
            <div class="gms-match-date-badge">
              <div class="month">{{ matchMonth(m.date) }}</div>
              <div class="day">{{ matchDay(m.date) }}</div>
            </div>
            <div class="gms-match-info">
              <div class="gms-match-name">{{ m.name }}</div>
              <div class="gms-match-meta">
                <span>{{ m.kickoff }}</span>
                <span>{{ m.stage }}</span>
              </div>
            </div>
            <GmsPill
              v-if="m.templateId"
              type="custom" value="Seating ready"
              :bg="'#dcfce7'" :fg="'#15803d'"
            />
            <GmsPill
              v-else
              type="custom" value="No template"
              :bg="'#fef9c3'" :fg="'#a16207'"
            />
          </div>
        </div>
      </div>

      <!-- Recent guests -->
      <div class="gms-card">
        <div class="gms-card-header">
          <span class="gms-card-title">Guest Registry</span>
          <Link href="/gms/guests" class="gms-btn gms-btn-ghost gms-btn-sm">View all</Link>
        </div>
        <div class="gms-card-body-0">
          <div
            v-for="g in guests"
            :key="g.id"
            class="gms-table"
            style="display:flex;align-items:center;gap:10px;padding:10px 20px;border-bottom:1px solid var(--gms-border-2);cursor:pointer;transition:background 120ms;"
            @mouseenter="e => e.currentTarget.style.background='var(--gms-surface-2)'"
            @mouseleave="e => e.currentTarget.style.background=''"
          >
            <GmsAvatar :name="g.name" size="sm" />
            <div style="flex:1;min-width:0;">
              <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ g.name }}</div>
              <div style="font-size:11.5px;color:var(--gms-text-3);">{{ g.title }}</div>
            </div>
            <span
              class="gms-pill"
              :style="{
                background: tierColor(g.tier) + '22',
                color: tierColor(g.tier),
                fontSize: '10.5px'
              }"
            >
              {{ tierName(g.tier) }}
            </span>
            <GmsPill :value="g.status" />
          </div>
        </div>
      </div>
    </div>

    <!-- Module quick links -->
    <div class="gms-card" style="margin-top:20px;">
      <div class="gms-card-header">
        <span class="gms-card-title">Modules</span>
      </div>
      <div class="gms-card-body">
        <div class="gms-grid-4" style="gap:12px;">
          <Link v-for="m in [
            { href:'/gms/flights',           label:'Flights',       icon:'plane',    count: stats.flightRequests },
            { href:'/gms/accommodation',     label:'Accommodation', icon:'building', count: stats.accommodation  },
            { href:'/gms/transport',         label:'Transport',     icon:'car',      count: stats.transport      },
            { href:'/gms/arrival-departure', label:'Arrival & Dep.',icon:'arrivals', count: stats.adRequests     },
          ]" :key="m.href" :href="m.href"
            style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px 12px;border:1px solid var(--gms-border);border-radius:var(--gms-radius-lg);text-decoration:none;color:var(--gms-text);cursor:pointer;transition:border-color 120ms,box-shadow 120ms;"
            @mouseenter="e => e.currentTarget.style.borderColor='var(--gms-maroon)'"
            @mouseleave="e => e.currentTarget.style.borderColor=''"
          >
            <div style="width:40px;height:40px;background:var(--gms-maroon-light);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--gms-maroon);">
              <GmsIcon :name="m.icon" :size="18" />
            </div>
            <span style="font-size:12.5px;font-weight:600;text-align:center;">{{ m.label }}</span>
            <span style="font-family:var(--gms-font-display);font-size:22px;color:var(--gms-text);line-height:1;">{{ m.count }}</span>
            <span style="font-size:11px;color:var(--gms-text-3);">requests</span>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
