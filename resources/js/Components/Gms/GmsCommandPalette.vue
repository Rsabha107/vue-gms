<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import GmsIcon from './GmsIcon.vue'

const emit = defineEmits(['close'])

const query = ref('')
const inputRef = ref(null)
onMounted(() => inputRef.value?.focus())

const pages = [
    { label: 'Dashboard',        icon: 'home',     href: '/gms' },
    { label: 'Guests',           icon: 'users',    href: '/gms/guests' },
    { label: 'Invitations',      icon: 'mail',     href: '/gms/invitations' },
    { label: 'Seating',          icon: 'grid',     href: '/gms/seating' },
    { label: 'Service Levels',   icon: 'star',     href: '/gms/service-levels' },
    { label: 'Flights',          icon: 'plane',    href: '/gms/flights' },
    { label: 'Accommodation',    icon: 'building', href: '/gms/accommodation' },
    { label: 'Transport',        icon: 'car',      href: '/gms/transport' },
    { label: 'Arrival & Dep.',   icon: 'arrivals', href: '/gms/arrival-departure' },
]

const filtered = computed(() => {
    const q = query.value.toLowerCase()
    if (!q) return pages
    return pages.filter(p => p.label.toLowerCase().includes(q))
})

function go(href) {
    router.visit(href)
    emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div class="gms-cmdk-overlay" @click.self="$emit('close')">
      <div class="gms-cmdk">
        <div class="gms-cmdk-input-wrap">
          <GmsIcon name="search" :size="16" style="color:var(--gms-text-3);flex-shrink:0;" />
          <input
            ref="inputRef"
            v-model="query"
            class="gms-cmdk-input"
            placeholder="Search pages, guests…"
            @keydown.escape="$emit('close')"
          />
        </div>
        <div class="gms-cmdk-results">
          <div class="gms-cmdk-group-label">Navigation</div>
          <div
            v-for="item in filtered"
            :key="item.href"
            class="gms-cmdk-item"
            @click="go(item.href)"
          >
            <GmsIcon :name="item.icon" :size="15" class="gms-cmdk-item-icon" />
            {{ item.label }}
            <span class="gms-cmdk-item-sub">Go to →</span>
          </div>
          <div v-if="filtered.length === 0" style="padding:20px 18px;color:var(--gms-text-3);font-size:13px;text-align:center;">
            No results for "{{ query }}"
          </div>
        </div>
        <div class="gms-cmdk-footer">
          <span><span class="gms-cmdk-kbd">↑↓</span> Navigate</span>
          <span><span class="gms-cmdk-kbd">↵</span> Open</span>
          <span><span class="gms-cmdk-kbd">Esc</span> Close</span>
        </div>
      </div>
    </div>
  </Teleport>
</template>
