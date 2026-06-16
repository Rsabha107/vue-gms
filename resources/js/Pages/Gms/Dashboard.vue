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
  <div class="gms-view-pad view-anim">
    <!-- Hero section -->
    <div style="display:flex;align-items:flex-end;gap:20px;margin-bottom:26px;flex-wrap:wrap;">
      <div style="flex:1;">
        <div style="display:flex;align-items:center;gap:10px;color:var(--gms-maroon);font-size:12px;font-weight:700;letter-spacing:0.6px;text-transform:uppercase;margin-bottom:6px;">
          <span style="width:7px;height:7px;border-radius:50%;background:#22c55e;"></span>
          Active event
        </div>
        <h1 style="font-family:var(--gms-font-display);font-size:46px;line-height:0.95;margin:0;">
          {{ event.name }}
        </h1>
        <div style="margin-top:8px;font-size:14px;color:var(--gms-text-3);">
          {{ event.location }} · {{ event.dates }} · {{ guests.length }} guests invited
        </div>
      </div>
      <div style="margin-left:auto;display:flex;gap:10px;">
        <button class="gms-btn" @click="handleBulkUpload">
          <GmsIcon name="upload" :size="14" />
          Bulk upload
        </button>
        <button class="gms-btn gms-btn-primary" @click="handleNewInvitation">
          <GmsIcon name="plus" :size="14" />
          New invitation
        </button>
      </div>
    </div>

    <!-- Module tiles -->
    <div class="cards-grid" style="margin-bottom:28px;">
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
      <div class="gms-card" style="padding:4px 0;">
        <div style="display:flex;align-items:center;gap:10px;padding:16px 20px 12px;">
          <h3 style="font-family:var(--gms-font-display);font-size:22px;margin:0;">
            Needs your attention
          </h3>
          <span
            v-if="needsAttention.length > 0"
            class="gms-pill"
            style="margin-left:auto;background:#fef9c3;color:#a16207;font-size:11px;font-weight:600;"
          >
            {{ needsAttention.length }}
          </span>
        </div>
        <div v-if="needsAttention.length > 0">
          <div
            v-for="(item, i) in needsAttention"
            :key="i"
            style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-top:1px solid var(--gms-border-2);"
          >
            <GmsAvatar :name="item.guest.name" size="md" />
            <div style="flex:1;min-width:0;">
              <div style="font-weight:600;font-size:13.5px;">{{ item.guest.name }}</div>
              <div style="font-size:12px;color:var(--gms-text-3);">{{ item.label }}</div>
            </div>
            <button
              class="gms-btn gms-btn-sm"
              @click="handleGuestAction(item.guest, item.cta)"
            >
              {{ item.cta }}
            </button>
          </div>
        </div>
        <div
          v-else
          style="padding:40px 20px;text-align:center;color:var(--gms-text-3);"
        >
          <GmsIcon name="check-circle" :size="32" style="opacity:0.5;margin-bottom:8px;" />
          <div style="font-size:13px;">All caught up!</div>
        </div>
      </div>

      <!-- Recent activity -->
      <div class="gms-card" style="padding:4px 0;">
        <div style="padding:16px 20px 12px;">
          <h3 style="font-family:var(--gms-font-display);font-size:22px;margin:0;">
            Recent activity
          </h3>
        </div>
        <div v-if="recentActivity.length > 0">
          <div
            v-for="(activity, i) in recentActivity"
            :key="i"
            style="display:flex;align-items:center;gap:11px;padding:11px 20px;border-top:1px solid var(--gms-border-2);"
          >
            <GmsAvatar :name="activity.guest.name" size="sm" />
            <div style="flex:1;font-size:13px;">
              <b style="font-weight:600;">{{ getLastName(activity.guest.name) }}</b> <span style="color:var(--gms-text-3);">{{ activity.text }}</span>
            </div>
            <div style="font-size:11px;color:var(--gms-text-3);white-space:nowrap;">
              {{ activity.time }}
            </div>
          </div>
        </div>
        <div
          v-else
          style="padding:40px 20px;text-align:center;color:var(--gms-text-3);"
        >
          <div style="font-size:13px;">No recent activity</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Module tile cards */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 14px;
}

.dash-tile {
  padding: 18px 20px;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: transform 0.15s, box-shadow 0.15s;
  cursor: pointer;
  text-decoration: none;
  color: var(--gms-text);
}

.dash-tile:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.dash-tile-icon {
  width: 40px;
  height: 40px;
  border-radius: 11px;
  display: grid;
  place-items: center;
}

.dash-tile-value {
  font-family: var(--gms-font-display);
  font-size: 34px;
  line-height: 0.9;
  font-feature-settings: 'tnum';
}

.dash-tile-sub {
  color: var(--gms-text-3);
  font-size: 12.5px;
  margin-top: 3px;
}

/* Two-column layout */
.dash-cols {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 18px;
  align-items: start;
}

@media (max-width: 1024px) {
  .dash-cols {
    grid-template-columns: 1fr;
  }
}

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
</style>
