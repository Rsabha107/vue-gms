<script setup>
import { ref, computed } from 'vue'
import GmsIcon from './GmsIcon.vue'
import GmsAvatar from './GmsAvatar.vue'

const props = defineProps({
    guests: { type: Array, default: () => [] },
    selectedGuestId: { type: [Number, String], default: null },
    tiers: { type: Array, default: () => [] },
    showExistingIndicator: { type: Boolean, default: false },
    existingGuestIds: { type: Array, default: () => [] },
    existingLabel: { type: String, default: 'has flight' },
})

const emit = defineEmits(['update:selectedGuestId', 'select'])

const searchQuery = ref('')

const filteredGuests = computed(() => {
    if (!searchQuery.value) return props.guests
    
    const query = searchQuery.value.trim().toLowerCase()
    return props.guests.filter(g => {
        return g.name.toLowerCase().includes(query) ||
               g.email?.toLowerCase().includes(query) ||
               g.group?.toLowerCase().includes(query)
    })
})

function selectGuest(guest) {
    emit('update:selectedGuestId', guest.id)
    emit('select', guest)
}

function hasExisting(guestId) {
    return props.showExistingIndicator && props.existingGuestIds.includes(guestId)
}
</script>

<template>
  <div>
    <!-- Search input -->
    <div class="gms-search-wrap" style="margin-bottom: 10px;">
      <GmsIcon name="search" :size="14" class="gms-search-icon" />
      <input 
        v-model="searchQuery" 
        class="gms-search-input" 
        placeholder="Search the guest registry…" 
      />
    </div>

    <!-- Guest list -->
    <div class="gms-guest-picker-list">
      <button
        v-for="g in filteredGuests"
        :key="g.id"
        type="button"
        :class="['gms-guest-picker-item', { 'on': selectedGuestId === g.id }]"
        @click="selectGuest(g)"
      >
        <GmsAvatar :name="g.name" size="sm" />
        <div style="flex:1;min-width:0;text-align:left;">
          <div class="gms-guest-picker-name">{{ g.name }}</div>
          <div class="gms-muted" style="font-size:11px;">
            {{ g.guestType === 'international' ? 'Int\'l' : 'Local' }} · {{ g.group || '—' }}<span v-if="g.email"> · {{ g.email }}</span>
          </div>
        </div>
        <span v-if="hasExisting(g.id)" class="gms-pill" style="font-size:10px;" :title="existingLabel">{{ existingLabel }}</span>
        <span
          v-if="tiers.find(t => t.id === g.tier)"
          class="gms-pill"
          :style="{
            background: tiers.find(t => t.id === g.tier).bg,
            color: tiers.find(t => t.id === g.tier).color,
            fontSize: '10.5px'
          }"
        >{{ g.tier }}</span>
        <span class="gms-guest-picker-radio">
          <GmsIcon v-if="selectedGuestId === g.id" name="check" :size="13" />
        </span>
      </button>
      <div v-if="filteredGuests.length === 0" class="gms-muted" style="font-size:12.5px;padding:14px;text-align:center;">
        No guests match "{{ searchQuery }}".
      </div>
    </div>
  </div>
</template>

<style scoped>
.gms-guest-picker-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 340px;
  overflow-y: auto;
  padding: 2px;
}

.gms-guest-picker-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: 1px solid var(--gms-border);
  border-radius: 10px;
  background: var(--gms-surface);
  cursor: pointer;
  transition: all 0.12s;
  text-align: left;
}

.gms-guest-picker-item:hover {
  border-color: var(--gms-maroon);
  background: var(--gms-maroon-tint);
}

.gms-guest-picker-item.on {
  border-color: var(--gms-maroon);
  background: var(--gms-maroon-tint);
  box-shadow: 0 0 0 1px var(--gms-maroon);
}

.gms-guest-picker-name {
  font-weight: 600;
  font-size: 13px;
  color: var(--gms-text);
}

.gms-guest-picker-radio {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 1.5px solid var(--gms-border);
  display: grid;
  place-items: center;
  flex-shrink: 0;
  transition: all 0.12s;
}

.gms-guest-picker-item.on .gms-guest-picker-radio {
  border-color: var(--gms-maroon);
  background: var(--gms-maroon);
  color: white;
}
</style>
