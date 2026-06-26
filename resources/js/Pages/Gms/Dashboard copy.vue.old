<script setup>
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsPill from '@/Components/Gms/GmsPill.vue'
import { Link, router } from '@inertiajs/vue3'
import { computed, inject } from 'vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    event:   { type: Object, default: () => ({}) },
    stats:   { type: Object, default: () => ({}) },
    matches: { type: Array,  default: () => [] },
    guests:  { type: Array,  default: () => [] },
    tiers:   { type: Array,  default: () => [] },
})

const toast = inject('toast')

function tierName(id) {
    return props.tiers.find(t => t.id === id)?.name ?? id
}
function tierColor(id) {
    return props.tiers.find(t => t.id === id)?.color ?? '#6b7280'
}

// Compute stats for module tiles
const accepted = computed(() => props.stats.confirmed)
const pending = computed(() => props.stats.pending)
const fillPct = computed(() => props.stats.fillPct)
const seatedCount = computed(() => props.stats.seatedGuests)
const totalSeats = computed(() => props.stats.totalSeats)

// Module tiles data
const moduleTiles = computed(() => [
    {
        key: 'guests',
        icon: 'users',
        value: props.guests.length,
        sub: 'guests · tap to manage',
        accent: '#8a1f3d',
    },
    {
        key: 'invitations',
        icon: 'mail',
        value: `${accepted.value}/${props.guests.length}`,
        sub: `${pending.value} pending response`,
        accent: '#a9844a',
    },
    {
        key: 'seating',
        icon: 'grid',
        value: `${fillPct.value}%`,
        sub: `${seatedCount.value} of ${totalSeats.value} seats assigned`,
        accent: '#3a6a8a',
    },
    {
        key: 'flights',
        icon: 'plane',
        value: props.stats.flightRequests,
        sub: props.stats.flightRequests > 0 ? 'flight requests' : 'all requests handled',
        accent: '#7a5a8a',
    },
    {
        key: 'accommodation',
        icon: 'building',
        value: props.stats.accommodation,
        sub: props.stats.accommodation > 0 ? 'room bookings' : 'no bookings yet',
        accent: '#3f7d52',
    },
    {
        key: 'transport',
        icon: 'car',
        value: props.stats.transport,
        sub: props.stats.transport > 0 ? 'transport requests' : 'cars assigned',
        accent: '#b06038',
    },
    {
        key: 'arrival-departure',
        icon: 'arrivals',
        value: props.stats.adRequests,
        sub: props.stats.adRequests > 0 ? 'meet & greet protocols' : 'protocols set',
        accent: '#3a6a8a',
    },
])

// Needs attention list - guests that require action
const needsAttention = computed(() => {
    const items = []
    
    // 1. Pending invitations (up to 3)
    const pendingGuests = props.guests.filter(g => g.status === 'pending').slice(0, 3)
    pendingGuests.forEach(g => {
        items.push({
            guest: g,
            type: 'invite',
            label: 'Invitation pending',
            cta: 'Resend',
        })
    })
    
    // 2. Draft hotel bookings (up to 2)
    const draftHotels = props.guests.filter(g => 
        g.facilities?.hotel && g.facilities.hotel.status === 'draft'
    ).slice(0, 2)
    draftHotels.forEach(g => {
        items.push({
            guest: g,
            type: 'hotel',
            label: 'Hotel booking is a draft',
            cta: 'Review',
        })
    })
    
    // 3. New flight requests (up to 2)
    const newFlights = props.guests.filter(g => 
        g.facilities?.flight && g.facilities.flight.status === 'new'
    ).slice(0, 2)
    newFlights.forEach(g => {
        items.push({
            guest: g,
            type: 'flight',
            label: 'New flight request',
            cta: 'Confirm',
        })
    })
    
    return items.slice(0, 5)
})

// Mock recent activity (in production, this would come from the backend)
const recentActivity = computed(() => {
    const activities = []
    
    // Sample activities based on confirmed guests
    const confirmedGuests = props.guests.filter(g => g.status === 'confirmed')
    if (confirmedGuests.length > 0) {
        activities.push({
            guest: confirmedGuests[0],
            text: 'accepted the invitation',
            time: '12m ago',
        })
    }
    if (confirmedGuests.length > 1) {
        activities.push({
            guest: confirmedGuests[1],
            text: 'was assigned seat A-02-07',
            time: '48m ago',
        })
    }
    if (confirmedGuests.length > 2) {
        activities.push({
            guest: confirmedGuests[2],
            text: 'confirmed hotel — Mandarin Oriental',
            time: '2h ago',
        })
    }
    
    // Add some generic activities
    const allGuests = props.guests
    if (allGuests.length > 3) {
        activities.push({
            guest: allGuests[3],
            text: 'flight marked confirmed',
            time: '3h ago',
        })
    }
    if (allGuests.length > 4) {
        activities.push({
            guest: allGuests[4],
            text: 'companion added (×1)',
            time: '5h ago',
        })
    }
    
    return activities.slice(0, 5)
})

function getLastName(fullName) {
    return fullName.split(' ').slice(-1)[0]
}

function handleBulkUpload() {
    toast('Bulk upload feature coming soon', 'info')
}

function handleNewInvitation() {
    router.visit('/gms/invitations')
}

function handleGuestAction(guest, action) {
    toast(`${action} for ${guest.name}`, 'info')
}
</script>

<template>
  <div class="dash-fullpage view-anim">
    <!-- Hero section -->
    <div class="dash-hero">
      <div class="dash-hero-content">
        <div class="dash-event-badge">
          <span class="dash-event-badge-dot"></span>
          Active event
        </div>
        <h1 class="dash-title">
          {{ event.name }}
        </h1>
        <div class="dash-meta">
          {{ event.location }} · {{ event.dates }} · {{ guests.length }} guests invited
        </div>
      </div>
      <div class="dash-actions">
        <button class="gms-btn" @click="handleBulkUpload">
          <GmsIcon name="upload" :size="14" />
          <span>Bulk upload</span>
        </button>
        <button class="gms-btn gms-btn-primary" @click="handleNewInvitation">
          <GmsIcon name="plus" :size="14" />
          <span>New invitation</span>
        </button>
      </div>
    </div>

    <!-- Module tiles -->
    <div class="cards-grid" style="margin-bottom:36px;">
      <Link
        v-for="tile in moduleTiles"
        :key="tile.key"
        :href="`/gms/${tile.key}`"
        class="card dash-tile"
      >
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <span
            class="dash-tile-icon"
            :style="{ background: tile.accent + '18', color: tile.accent }"
          >
            <GmsIcon :name="tile.icon" :size="20" />
          </span>
          <span style="color:var(--gms-text-3);">
            <GmsIcon name="chevron-right" :size="16" />
          </span>
        </div>
        <div>
          <div class="dash-tile-value">{{ tile.value }}</div>
          <div class="dash-tile-sub">{{ tile.sub }}</div>
        </div>
      </Link>
    </div>

    <!-- Two-column layout -->
    <div class="dash-cols">
      <!-- Needs attention -->
      <div class="gms-card dash-section">
        <div class="dash-section-header">
          <h3 class="dash-section-title">
            Needs your attention
          </h3>
          <span
            v-if="needsAttention.length > 0"
            class="gms-pill dash-badge"
          >
            {{ needsAttention.length }}
          </span>
        </div>
        <div v-if="needsAttention.length > 0">
          <div
            v-for="(item, i) in needsAttention"
            :key="i"
            class="dash-list-item"
          >
            <GmsAvatar :name="item.guest.name" size="md" />
            <div class="dash-list-content">
              <div class="dash-list-name">{{ item.guest.name }}</div>
              <div class="dash-list-label">{{ item.label }}</div>
            </div>
            <button
              class="gms-btn gms-btn-sm"
              @click="handleGuestAction(item.guest, item.cta)"
            >
              {{ item.cta }}
            </button>
          </div>
        </div>
        <div v-else class="dash-empty">
          <GmsIcon name="check-circle" :size="32" style="opacity:0.5;margin-bottom:8px;" />
          <div>All caught up!</div>
        </div>
      </div>

      <!-- Recent activity -->
      <div class="gms-card dash-section">
        <div class="dash-section-header">
          <h3 class="dash-section-title">
            Recent activity
          </h3>
        </div>
        <div v-if="recentActivity.length > 0">
          <div
            v-for="(activity, i) in recentActivity"
            :key="i"
            class="dash-activity-item"
          >
            <GmsAvatar :name="activity.guest.name" size="sm" />
            <div class="dash-activity-text">
              <b>{{ getLastName(activity.guest.name) }}</b> <span>{{ activity.text }}</span>
            </div>
            <div class="dash-activity-time">
              {{ activity.time }}
            </div>
          </div>
        </div>
        <div v-else class="dash-empty">
          <div>No recent activity</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Full-page layout */
.dash-fullpage {
  padding: 32px 40px;
  min-height: 100%;
}

/* Hero section */
.dash-hero {
  display: flex;
  align-items: flex-end;
  gap: 20px;
  margin-bottom: 40px;
  flex-wrap: wrap;
}

.dash-hero-content {
  flex: 1;
  min-width: 0;
}

.dash-event-badge {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--gms-maroon);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.dash-event-badge-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  flex-shrink: 0;
}

.dash-title {
  font-family: var(--gms-font-display);
  font-size: 52px;
  line-height: 0.95;
  margin: 0;
  word-wrap: break-word;
}

.dash-meta {
  margin-top: 10px;
  font-size: 15px;
  color: var(--gms-text-3);
}

.dash-actions {
  margin-left: auto;
  display: flex;
  gap: 10px;
  flex-shrink: 0;
}

/* Module tile cards */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 16px;
}

.dash-tile {
  padding: 20px 22px;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 14px;
  transition: transform 0.15s, box-shadow 0.15s;
  cursor: pointer;
  text-decoration: none;
  color: var(--gms-text);
  min-height: 140px;
}

.dash-tile:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.dash-tile:active {
  transform: translateY(-1px);
}

.dash-tile-icon {
  width: 42px;
  height: 42px;
  border-radius: 11px;
  display: grid;
  place-items: center;
}

.dash-tile-value {
  font-family: var(--gms-font-display);
  font-size: 36px;
  line-height: 0.9;
  font-feature-settings: 'tnum';
}

.dash-tile-sub {
  color: var(--gms-text-3);
  font-size: 13px;
  margin-top: 4px;
}

/* Two-column layout */
.dash-cols {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 20px;
  align-items: start;
}

/* Dashboard sections */
.dash-section {
  padding: 4px 0 !important;
}

.dash-section-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px 20px 12px;
}

.dash-section-title {
  font-family: var(--gms-font-display);
  font-size: 22px;
  margin: 0;
}

.dash-badge {
  margin-left: auto;
  background: #fef9c3;
  color: #a16207;
  font-size: 11px;
  font-weight: 600;
}

.dash-list-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  border-top: 1px solid var(--gms-border-2);
}

.dash-list-content {
  flex: 1;
  min-width: 0;
}

.dash-list-name {
  font-weight: 600;
  font-size: 13.5px;
}

.dash-list-label {
  font-size: 12px;
  color: var(--gms-text-3);
}

.dash-activity-item {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 11px 20px;
  border-top: 1px solid var(--gms-border-2);
}

.dash-activity-text {
  flex: 1;
  font-size: 13px;
}

.dash-activity-text b {
  font-weight: 600;
}

.dash-activity-text span {
  color: var(--gms-text-3);
}

.dash-activity-time {
  font-size: 11px;
  color: var(--gms-text-3);
  white-space: nowrap;
}

.dash-empty {
  padding: 40px 20px;
  text-align: center;
  color: var(--gms-text-3);
  font-size: 13px;
}

/* Animation */
.view-anim {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* ═══════════════════════════════════════════════════════════════════
   TABLET (≤ 1200px)
   ═══════════════════════════════════════════════════════════════════ */
@media (max-width: 1200px) {
  .dash-cols {
    grid-template-columns: 1fr;
  }
  
  .cards-grid {
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  }
}

/* ═══════════════════════════════════════════════════════════════════
   MOBILE (≤ 768px)
   ═══════════════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
  .dash-fullpage {
    padding: 16px;
  }
  
  /* Hero section - stack vertically */
  .dash-hero {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 24px;
  }
  
  .dash-title {
    font-size: 32px;
  }
  
  .dash-meta {
    font-size: 12.5px;
  }
  
  .dash-event-badge {
    font-size: 10.5px;
    margin-bottom: 6px;
  }
  
  .dash-actions {
    margin-left: 0;
    width: 100%;
    gap: 8px;
  }
  
  .dash-actions .gms-btn {
    flex: 1;
    justify-content: center;
    min-height: 44px;
  }
  
  .dash-actions .gms-btn span {
    display: inline;
  }
  
  /* Module tiles - 2 columns */
  .cards-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  
  .dash-tile {
    padding: 12px 14px;
    gap: 8px;
    min-height: 100px;
  }
  
  .dash-tile:hover {
    transform: none;
  }
  
  .dash-tile:active {
    transform: scale(0.98);
  }
  
  .dash-tile-icon {
    width: 32px;
    height: 32px;
  }
  
  .dash-tile-value {
    font-size: 24px;
  }
  
  .dash-tile-sub {
    font-size: 11px;
    margin-top: 2px;
  }
  
  /* Cards padding adjustment */
  .gms-card {
    padding: 4px 0 !important;
  }
  
  .dash-cols {
    gap: 16px;
  }
  
  /* Section headers - more compact */
  .dash-section-header {
    padding: 12px 14px 8px;
  }
  
  .dash-section-title {
    font-size: 18px;
  }
  
  .dash-badge {
    font-size: 10px;
    padding: 2px 6px;
  }
  
  /* List items - more compact */
  .dash-list-item {
    padding: 8px 14px;
    gap: 10px;
  }
  
  .dash-list-name {
    font-size: 12.5px;
  }
  
  .dash-list-label {
    font-size: 11px;
  }
  
  .dash-list-item .gms-btn-sm {
    font-size: 11px;
    padding: 6px 12px;
  }
  
  /* Activity items - more compact */
  .dash-activity-item {
    padding: 8px 14px;
    gap: 8px;
  }
  
  .dash-activity-text {
    font-size: 12px;
  }
  
  .dash-activity-time {
    font-size: 10px;
  }
  
  .dash-empty {
    padding: 28px 14px;
    font-size: 12px;
  }
}

/* ═══════════════════════════════════════════════════════════════════
   SMALL PHONES (≤ 480px)
   ═══════════════════════════════════════════════════════════════════ */
@media (max-width: 480px) {
  .dash-fullpage {
    padding: 12px;
  }
  
  .dash-hero {
    margin-bottom: 20px;
    gap: 12px;
  }
  
  .dash-title {
    font-size: 26px;
  }
  
  .dash-meta {
    font-size: 11.5px;
  }
  
  .dash-event-badge {
    font-size: 10px;
    margin-bottom: 4px;
  }
  
  .dash-event-badge-dot {
    width: 6px;
    height: 6px;
  }
  
  .dash-actions {
    flex-direction: column;
    gap: 6px;
  }
  
  .dash-actions .gms-btn {
    width: 100%;
  }
  
  /* Module tiles - single column for very small screens */
  .cards-grid {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  
  .dash-tile {
    padding: 10px 12px;
    min-height: 85px;
    gap: 6px;
  }
  
  .dash-tile-icon {
    width: 28px;
    height: 28px;
  }
  
  .dash-tile-value {
    font-size: 22px;
  }
  
  .dash-tile-sub {
    font-size: 10.5px;
  }
  
  /* Section headers - smaller */
  .dash-section-header {
    padding: 10px 12px 6px;
  }
  
  .dash-section-title {
    font-size: 16px;
  }
  
  /* List items - stack button below on very small screens */
  .dash-list-item {
    padding: 8px 12px;
    gap: 8px;
    flex-wrap: wrap;
  }
  
  .dash-list-item .gms-btn-sm {
    width: 100%;
    margin-top: 4px;
  }
  
  .dash-list-content {
    flex: 1 1 100%;
  }
  
  .dash-list-name {
    font-size: 12px;
  }
  
  .dash-list-label {
    font-size: 10.5px;
  }
  
  /* Activity items */
  .dash-activity-item {
    padding: 7px 12px;
    gap: 8px;
    flex-wrap: wrap;
  }
  
  .dash-activity-text {
    font-size: 11.5px;
  }
  
  .dash-activity-time {
    flex-basis: 100%;
    padding-left: 36px;
    margin-top: -4px;
  }
  
  .dash-empty {
    padding: 24px 12px;
    font-size: 11.5px;
  }
}

/* ═══════════════════════════════════════════════════════════════════
   TOUCH DEVICE OPTIMIZATIONS
   ═══════════════════════════════════════════════════════════════════ */
@media (hover: none) and (pointer: coarse) {
  .dash-tile {
    min-height: 110px;
  }
  
  .dash-tile:hover {
    transform: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  
  .gms-btn {
    min-height: 44px;
  }
  
  .gms-btn-sm {
    min-height: 38px;
    padding: 7px 12px;
  }
}
</style>
