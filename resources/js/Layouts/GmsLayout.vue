<script setup>
import { ref, computed, provide, onMounted, onUnmounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsToast from '@/Components/Gms/GmsToast.vue'
import GmsCommandPalette from '@/Components/Gms/GmsCommandPalette.vue'

const page     = usePage()
const auth     = computed(() => page.props.auth)
const event    = computed(() => page.props.event ?? { name: "Doha Cup '26", location: 'Lusail, Qatar' })
const events   = computed(() => page.props.gmsEvents ?? [])
const currentUrl = computed(() => page.url)

// ── Event selector ────────────────────────────────────────────────
const eventSelectorOpen = ref(false)

function toggleEventSelector() {
    eventSelectorOpen.value = !eventSelectorOpen.value
}

function switchEvent(eventId) {
    eventSelectorOpen.value = false
    router.post(route('gms.events.switch'), {
        event_id: eventId
    }, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            addToast('Event switched successfully')
        },
        onError: () => {
            addToast('Failed to switch event', 'error')
        }
    })
}

// ── Toast system ─────────────────────────────────────────────────
const toasts = ref([])
let toastId = 0
function addToast(msg, type = 'success') {
    const id = ++toastId
    toasts.value.push({ id, msg, type })
    setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id) }, 3200)
}
provide('toast', addToast)

// ── Command palette ───────────────────────────────────────────────
const cmdOpen = ref(false)
function openCmd() { cmdOpen.value = true }
function closeCmd() { cmdOpen.value = false }

function handleKey(e) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); cmdOpen.value = !cmdOpen.value }
    if (e.key === 'Escape') cmdOpen.value = false
}
onMounted(() => window.addEventListener('keydown', handleKey))
onUnmounted(() => window.removeEventListener('keydown', handleKey))

// ── Nav structure ─────────────────────────────────────────────────
const breadcrumbMap = {
    'gms/guests':            'Guests',
    'gms/invitations':       'Invitations',
    'gms/seating':           'Seating',
    'gms/service-levels':    'Service Levels',
    'gms/flights':           'Flights',
    'gms/accommodation':     'Accommodation',
    'gms/transport':         'Transport',
    'gms/arrival-departure': 'Arrival & Departure',
    'gms/events':            'Events',
    'gms/venues':            'Venues',
    'gms/matches':           'Matches',
    'gms/settings':          'Settings',
}

const currentBreadcrumb = computed(() => {
    const loc = currentUrl.value.replace(/^\//, '')
    if (loc === 'gms' || loc === 'gms/') return 'Overview'
    for (const [key, label] of Object.entries(breadcrumbMap)) {
        if (loc.startsWith(key)) return label
    }
    return 'Overview'
})

const nav = [
    { name: 'gms.dashboard',       label: 'Dashboard',          icon: 'home',     href: '/gms' },
    { name: 'gms.guests.index',    label: 'Guests',             icon: 'users',    href: '/gms/guests' },
    { name: 'gms.invitations.index', label: 'Invitations',      icon: 'mail',     href: '/gms/invitations' },
    { name: 'gms.seating.index',   label: 'Seating',            icon: 'grid',     href: '/gms/seating' },
    { name: 'gms.service-levels.index', label: 'Service Levels', icon: 'star',    href: '/gms/service-levels' },
]
const modules = [
    { name: 'gms.flights.index',       label: 'Flights',       icon: 'plane',     href: '/gms/flights' },
    { name: 'gms.accommodation.index', label: 'Accommodation', icon: 'building',  href: '/gms/accommodation' },
    { name: 'gms.transport.index',     label: 'Transport',     icon: 'car',       href: '/gms/transport' },
    { name: 'gms.ad.index',            label: 'Arrival & Dep.', icon: 'arrivals',  href: '/gms/arrival-departure' },
]
const setup = [
    { name: 'gms.events.index',        label: 'Events',        icon: 'calendar',  href: '/gms/events' },
    { name: 'gms.venues.index',        label: 'Venues',        icon: 'map',       href: '/gms/venues' },
    { name: 'gms.matches.index',       label: 'Matches',       icon: 'trophy',    href: '/gms/matches' },
    { name: 'gms.settings',            label: 'Settings',      icon: 'settings',  href: '/gms/settings' },
]

function isActive(item) {
    if (item.href === '/gms') {
        return currentUrl.value === '/gms' || currentUrl.value === '/gms/'
    }
    return currentUrl.value.startsWith(item.href)
}
</script>

<template>
<div class="gms-app">
  <div class="gms-shell">

    <!-- ── Sidebar ──────────────────────────────────────────────── -->
    <aside class="gms-sidebar">
      <!-- Logo -->
      <div class="gms-sidebar-logo">
        <Link href="/gms" class="gms-sidebar-logo-mark">
          <div class="gms-logo-icon">G</div>
          <div class="gms-logo-text">
            <strong>GMS</strong>
            <span>Protocol Suite</span>
          </div>
        </Link>
      </div>

      <!-- Event selector -->
      <div style="margin: 14px 16px 8px; position: relative;">
        <button 
          class="gms-event-switch"
          @click="toggleEventSelector"
        >
          <div class="gms-event-ic">🏆</div>
          <div class="gms-event-lbl">
            <b>{{ event.name }}</b>
            <span>{{ event.location }}</span>
          </div>
          <GmsIcon :name="eventSelectorOpen ? 'chevron-up' : 'chevron-down'" :size="11" class="gms-event-cv" />
        </button>

        <!-- Event dropdown -->
        <div 
          v-if="eventSelectorOpen && events.length > 0" 
          class="gms-event-dropdown"
          @click.stop
        >
          <div 
            v-for="evt in events" 
            :key="evt.id"
            class="gms-event-dropdown-item"
            :class="{ active: evt.id === event.id }"
            @click="switchEvent(evt.id)"
          >
            <div class="gms-event-ic">🏆</div>
            <div style="flex: 1; min-width: 0;">
              <div class="gms-event-dropdown-name">{{ evt.name }}</div>
              <div class="gms-event-dropdown-sub">{{ evt.location }} • {{ evt.dates }}</div>
            </div>
            <GmsIcon v-if="evt.id === event.id" name="check" :size="14" style="color: var(--gms-maroon); flex-shrink: 0;" />
          </div>
        </div>
      </div>

      <!-- Nav -->
      <nav class="gms-sidebar-nav">
        <div class="gms-nav-section">
          <div class="gms-nav-section-label">Core</div>
          <template v-for="item in nav" :key="item.name">
            <Link :href="item.href"
                  class="gms-nav-item"
                  :class="{ active: isActive(item) }">
              <GmsIcon :name="item.icon" :size="16" class="gms-nav-icon" />
              {{ item.label }}
            </Link>
          </template>
        </div>

        <div class="gms-nav-section" style="margin-top: 4px;">
          <div class="gms-nav-section-label">Modules</div>
          <template v-for="item in modules" :key="item.name">
            <Link :href="item.href"
                  class="gms-nav-item"
                  :class="{ active: isActive(item) }">
              <GmsIcon :name="item.icon" :size="16" class="gms-nav-icon" />
              {{ item.label }}
            </Link>
          </template>
        </div>

        <div class="gms-nav-section" style="margin-top: 4px;">
          <div class="gms-nav-section-label">Setup</div>
          <template v-for="item in setup" :key="item.name">
            <Link :href="item.href"
                  class="gms-nav-item"
                  :class="{ active: isActive(item) }">
              <GmsIcon :name="item.icon" :size="16" class="gms-nav-icon" />
              {{ item.label }}
            </Link>
          </template>
        </div>
      </nav>

      <!-- Footer / user -->
      <div class="gms-sidebar-footer">
        <div class="gms-sidebar-user">
          <GmsAvatar v-if="auth?.user" :name="auth.user.name" size="sm" />
          <div class="gms-sidebar-user-info">
            <div class="gms-sidebar-user-name">{{ auth?.user?.name ?? 'User' }}</div>
            <div class="gms-sidebar-user-role">Protocol Officer</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- ── Main ──────────────────────────────────────────────────── -->
    <div class="gms-main">

      <!-- Topbar -->
      <header class="gms-topbar">
        <div class="gms-topbar-breadcrumb">
          <Link href="/gms" style="color: inherit; text-decoration: none;">GMS</Link>
          <span class="gms-bc-sep">›</span>
          <span class="gms-bc-current">{{ currentBreadcrumb }}</span>
        </div>

        <div class="gms-topbar-actions">
          <button class="gms-topbar-search-trigger" @click="openCmd">
            <GmsIcon name="search" :size="14" />
            <span>Quick search…</span>
            <span class="gms-topbar-search-kbd">⌘K</span>
          </button>
          <button class="gms-topbar-btn" title="Notifications">
            <GmsIcon name="bell" :size="16" />
          </button>
          <Link href="/profile" class="gms-topbar-btn" title="Profile" style="text-decoration:none;">
            <GmsAvatar v-if="auth?.user" :name="auth.user.name" size="sm" style="width:26px;height:26px;font-size:10px;" />
            <GmsIcon v-else name="user" :size="16" />
          </Link>
        </div>
      </header>

      <!-- Page content -->
      <slot />
    </div>
  </div>

  <!-- Toasts -->
  <GmsToast :toasts="toasts" />

  <!-- Command palette -->
  <GmsCommandPalette v-if="cmdOpen" @close="closeCmd" />
</div>
</template>
