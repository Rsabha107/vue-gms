<script setup>
import { ref, computed, inject, onMounted, onBeforeUnmount } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'
import GmsMiniStat from '@/Components/Gms/GmsMiniStat.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    matches: { type: Array, default: () => [] },
    venues:  { type: Array, default: () => [] },
    event:   { type: Object, default: () => ({}) },
})

const toast = inject('toast')
const localMatches = ref(props.matches.map(m => ({ ...m })))

// ── View + filter ─────────────────────────────────────────────────
const viewMode    = ref('table') // 'table' | 'grid'
const stageFilter = ref('all')

const stages = computed(() =>
    [...new Set(localMatches.value.map(m => m.stage).filter(Boolean))]
)

const filteredMatches = computed(() =>
    stageFilter.value === 'all'
        ? localMatches.value
        : localMatches.value.filter(m => m.stage === stageFilter.value)
)

// ── Stats ─────────────────────────────────────────────────────────
const matchStats = computed(() => {
    const venuesUsed      = new Set(localMatches.value.map(m => m.venueId)).size
    const ticketsAllocated = localMatches.value.reduce(
        (s, m) => s + ((m.seatsTotal || 0) - (m.seatsLeft || 0)), 0
    )
    return [
        { label: 'Fixtures',             value: localMatches.value.length,          color: 'var(--gms-maroon)' },
        { label: 'Venues in use',        value: venuesUsed,                          color: '#3B82F6'           },
        { label: 'Tickets allocated',    value: ticketsAllocated.toLocaleString(),   color: '#15803d'           },
        { label: 'Scheduling conflicts', value: 0,                                   color: 'var(--gms-text-3)' },
    ]
})

// ── Venue lookup ──────────────────────────────────────────────────
const venueMap = computed(() => {
    const m = {}
    props.venues.forEach(v => { m[v.id] = v.name })
    return m
})

function venueName(match) {
    return match.venueName || venueMap.value[match.venueId] || match.venueId || '—'
}

// ── Helpers ───────────────────────────────────────────────────────
const flagEmoji = (cc) => {
    if (!cc) return ''
    return cc.toUpperCase().replace(/./g, c =>
        String.fromCodePoint(0x1F1E6 - 65 + c.charCodeAt(0))
    )
}

function stageClass(code) {
    if (!code) return ''
    const c = (code + '').toUpperCase()
    if (c.includes('OPENING') || c.includes('CEREMONY')) return 'mc-stage-opening'
    if (c.includes('GROUP')) return 'mc-stage-group'
    if (c.includes('QUARTER')) return 'mc-stage-qf'
    if (c.includes('SEMI')) return 'mc-stage-sf'
    return 'mc-stage-final'
}

function allocated(m) { return (m.seatsTotal || 0) - (m.seatsLeft || 0) }
function capPct(m)    { return m.seatsTotal ? Math.min(100, allocated(m) / m.seatsTotal * 100) : 0 }

// ── Modal state ───────────────────────────────────────────────────
const matchModal   = ref(false)
const editingMatch = ref(null)
const deleteModal  = ref(false)
const deletingId   = ref(null)

const stageOptions = [
    { code: 'OPENING',  label: 'Opening'       },
    { code: 'GROUP_A',  label: 'Group A'        },
    { code: 'GROUP_B',  label: 'Group B'        },
    { code: 'GROUP_C',  label: 'Group C'        },
    { code: 'GROUP_D',  label: 'Group D'        },
    { code: 'QUARTER',  label: 'Quarter Final'  },
    { code: 'SEMI',     label: 'Semi Final'     },
    { code: 'FINAL',    label: 'Final'          },
]

const commonCodes = ['QA', 'JP', 'BR', 'IT', 'SE', 'NG', 'FR', 'DE', 'ES', 'GB', 'AR', 'KR', null]

const form = useForm({
    name: '', venueId: '', stageCode: '', stageLabel: '',
    homeTeam: '', homeCode: '', awayTeam: '', awayCode: '',
    date: '', day: '', kickoff: '', stage: '',
    seatsTotal: '', seatsSold: '',
    featured: false, bracketTBD: false,
})

function onStageChange() {
    const opt = stageOptions.find(s => s.label === form.stage)
    if (opt) form.stageCode = opt.code
}

function openNew() {
    editingMatch.value = null
    form.reset()
    matchModal.value = true
}

function openEdit(match) {
    editingMatch.value = match
    const parts = (match.date ?? '').split(' ')
    const hasDay = parts.length > 1 && parts[0].length === 3 && /^[A-Za-z]+$/.test(parts[0])

    form.venueId    = match.venueId    ?? ''
    form.stageCode  = match.stageCode  ?? ''
    form.stageLabel = match.stageLabel ?? ''
    form.homeTeam   = match.homeTeam   ?? ''
    form.homeCode   = match.homeCode   ?? ''
    form.awayTeam   = match.awayTeam   ?? ''
    form.awayCode   = match.awayCode   ?? ''
    form.day        = hasDay ? parts[0] : ''
    form.date       = hasDay ? parts.slice(1).join(' ') : (match.date ?? '')
    form.kickoff    = match.kickoff    ?? ''
    form.stage      = match.stage      ?? ''
    form.seatsTotal = match.seatsTotal ?? ''
    form.seatsSold  = match.seatsTotal != null && match.seatsLeft != null
                        ? match.seatsTotal - match.seatsLeft : ''
    form.featured   = match.featured   ?? false
    form.bracketTBD = match.bracketTBD ?? false
    form.name       = match.name       ?? ''
    matchModal.value = true
}

function saveMatch() {
    const name       = form.homeTeam && form.awayTeam ? `${form.homeTeam} v ${form.awayTeam}` : form.name
    const date       = form.day ? `${form.day} ${form.date}`.trim() : form.date
    const seatsTotal = Number(form.seatsTotal) || 0
    const seatsLeft  = Math.max(0, seatsTotal - (Number(form.seatsSold) || 0))
    const venName    = props.venues.find(v => String(v.id) === String(form.venueId))?.name

    const patch = { ...form.data(), name, date, seatsTotal, seatsLeft, venueName: venName }

    if (editingMatch.value) {
        const idx = localMatches.value.findIndex(m => m.id === editingMatch.value.id)
        if (idx !== -1) localMatches.value[idx] = { ...localMatches.value[idx], ...patch }
        form.put(route('gms.matches.update', editingMatch.value.id), {
            onSuccess: () => { matchModal.value = false; toast('Match updated.') },
            onError:   () => toast('Failed to save.', 'error'),
            preserveScroll: true,
        })
    } else {
        localMatches.value.unshift({ id: 'M' + Date.now(), ...patch })
        form.post(route('gms.matches.store'), {
            onSuccess: () => { matchModal.value = false; toast('Match created.') },
            onError:   () => toast('Failed to save.', 'error'),
            preserveScroll: true,
        })
    }
}

function openDelete(id) { deletingId.value = id; deleteModal.value = true }

function confirmDelete() {
    localMatches.value = localMatches.value.filter(m => m.id !== deletingId.value)
    router.delete(route('gms.matches.destroy', deletingId.value), {
        onSuccess: () => { deleteModal.value = false; toast('Match deleted.') },
        onError:   () => toast('Failed to delete.', 'error'),
        preserveScroll: true,
    })
}

onMounted(() => {})
onBeforeUnmount(() => {})
</script>

<template>
  <div class="gms-view">
    <!-- Header -->
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Matches</h1>
        <p class="gms-view-subtitle">
          Fixtures for <strong>{{ event.name }}</strong>. Switch events from the sidebar to manage another schedule.
        </p>
      </div>
      <div class="gms-view-actions">
        <button class="gms-btn gms-btn-primary" @click="openNew">
          <GmsIcon name="plus" :size="14" /> New match
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="gms-stats">
      <GmsMiniStat v-for="s in matchStats" :key="s.label" :label="s.label" :value="s.value" :color="s.color" />
    </div>

    <!-- Stage tabs + view toggle + count -->
    <div class="mxt-toolbar">
      <div class="gms-seg">
        <button
          :class="{ on: stageFilter === 'all' }"
          @click="stageFilter = 'all'"
        >All stages</button>
        <button
          v-for="s in stages" :key="s"
          :class="{ on: stageFilter === s }"
          @click="stageFilter = s"
        >{{ s }}</button>
      </div>

      <div class="mxt-toolbar-right">
        <span class="mxt-count">{{ filteredMatches.length }} of {{ localMatches.length }}</span>
        <div class="mxt-view-toggle">
          <button class="mxt-vbtn" :class="{ on: viewMode === 'table' }" @click="viewMode = 'table'" title="Table view">
            <GmsIcon name="list" :size="14" />
          </button>
          <button class="mxt-vbtn" :class="{ on: viewMode === 'grid' }" @click="viewMode = 'grid'" title="Grid view">
            <GmsIcon name="grid" :size="14" />
          </button>
        </div>
      </div>
    </div>

    <!-- ── Table View ─────────────────────────────────────────────── -->
    <div v-if="viewMode === 'table'" class="gms-card">
      <div class="gms-card-body-0">
        <table class="mxt-table">
          <thead>
            <tr>
              <th class="mxt-th">Stage</th>
              <th class="mxt-th">Fixture</th>
              <th class="mxt-th">When</th>
              <th class="mxt-th">Venue</th>
              <th class="mxt-th">Capacity</th>
              <th class="mxt-th mxt-th-actions"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="match in filteredMatches" :key="match.id" class="mxt-row" @click="openEdit(match)" style="cursor: pointer;">

              <!-- Stage -->
              <td class="mxt-td mxt-td-stage">
                <div class="mxt-stage-pills">
                  <span class="mc-stage" :class="stageClass(match.stageCode)">{{ match.stage }}</span>
                  <span v-if="match.featured" class="mc-stage mc-stage-featured">Featured</span>
                </div>
              </td>

              <!-- Fixture -->
              <td class="mxt-td mxt-td-fixture">
                <div class="mxt-fixture-teams">
                  <template v-if="match.homeCode">
                    <span class="mxt-code">{{ match.homeCode }}</span>
                    <span class="mxt-tname">{{ match.homeTeam }}</span>
                  </template>
                  <template v-else>
                    <span class="mxt-tbd">🏆</span>
                    <span class="mxt-tname">{{ match.homeTeam }}</span>
                  </template>
                  <span class="mxt-v">v</span>
                  <template v-if="match.awayCode">
                    <span class="mxt-tname">{{ match.awayTeam }}</span>
                    <span class="mxt-code">{{ match.awayCode }}</span>
                  </template>
                  <template v-else>
                    <span class="mxt-tname">{{ match.awayTeam }}</span>
                    <span class="mxt-tbd">🏆</span>
                  </template>
                </div>
                <div class="mxt-fixture-label">{{ match.stageLabel }}</div>
              </td>

              <!-- When -->
              <td class="mxt-td mxt-td-when">
                <div class="mxt-date">{{ match.date }}</div>
                <div class="mxt-time">{{ match.kickoff }}</div>
              </td>

              <!-- Venue -->
              <td class="mxt-td mxt-td-venue">{{ venueName(match) }}</td>

              <!-- Capacity -->
              <td class="mxt-td mxt-td-cap">
                <div class="mxt-cap-bar">
                  <div class="mxt-cap-fill" :style="{ width: capPct(match) + '%' }"></div>
                </div>
                <span class="mxt-cap-text">{{ allocated(match) }}/{{ match.seatsTotal }}</span>
              </td>

              <!-- Actions -->
              <td class="mxt-td mxt-td-actions">
                <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" @click.stop="openEdit(match)" title="Edit">
                  <GmsIcon name="edit" :size="14" />
                </button>
                <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" @click.stop="openDelete(match.id)" title="Delete">
                  <GmsIcon name="trash" :size="14" />
                </button>
              </td>
            </tr>

            <tr v-if="!filteredMatches.length">
              <td colspan="6">
                <div class="gms-empty">
                  <div class="gms-empty-title">No matches for this stage</div>
                  <div class="gms-empty-sub">Try a different filter or add a match.</div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Grid View ──────────────────────────────────────────────── -->
    <div v-if="viewMode === 'grid'" class="gms-match-grid">
      <div v-for="match in filteredMatches" :key="match.id" class="gms-match-card" @click="openEdit(match)" style="cursor: pointer;">
        <div class="gms-match-top">
          <span class="mc-stage" :class="stageClass(match.stageCode)">{{ match.stage }}</span>
          <div style="margin-left:auto;display:flex;gap:4px;">
            <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" @click.stop="openEdit(match)">
              <GmsIcon name="edit" :size="13" />
            </button>
            <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" @click.stop="openDelete(match.id)">
              <GmsIcon name="trash" :size="13" />
            </button>
          </div>
        </div>

        <div class="gms-match-teams">
          <div class="gms-match-team">
            <div v-if="match.homeCode" class="gms-match-flag">{{ flagEmoji(match.homeCode) }}</div>
            <div v-else class="gms-match-flag">🏆</div>
            <div class="gms-match-name">{{ match.homeTeam }}</div>
          </div>
          <div class="gms-match-vs">v</div>
          <div class="gms-match-team gms-match-team-away">
            <div class="gms-match-name">{{ match.awayTeam }}</div>
            <div v-if="match.awayCode" class="gms-match-flag">{{ flagEmoji(match.awayCode) }}</div>
            <div v-else class="gms-match-flag">🏆</div>
          </div>
        </div>

        <div class="gms-match-meta">
          <div class="gms-match-mi"><GmsIcon name="calendar" :size="13" /><span>{{ match.date }}</span></div>
          <div class="gms-match-mi"><GmsIcon name="clock" :size="13" /><span>{{ match.kickoff }}</span></div>
        </div>
        <div class="gms-match-meta">
          <div class="gms-match-mi"><GmsIcon name="map" :size="13" /><span>{{ venueName(match) }}</span></div>
        </div>

        <div class="gms-match-status">
          <div class="gms-match-status-text">
            <span class="gms-match-assigned">{{ allocated(match) }}</span>
            <span class="gms-match-total">/ {{ match.seatsTotal }} seats</span>
          </div>
          <div class="gms-match-bar">
            <div class="gms-match-bar-fill" :style="{ width: capPct(match) + '%' }"></div>
          </div>
        </div>
      </div>

      <div class="gms-match-card gms-match-add" @click="openNew">
        <div class="gms-match-add-icon"><GmsIcon name="plus" :size="24" /></div>
        <div class="gms-match-add-text">Add new match</div>
      </div>
    </div>

    <!-- ── Create / Edit Modal ──────────────────────────────────── -->
    <GmsModal
      :open="matchModal"
      :title="editingMatch ? 'Edit match' : 'New match'"
      :subtitle="'for ' + (event?.name ?? 'Doha Cup \'26')"
      size="lg"
      @close="matchModal = false"
    >
      <div class="mxm-body">

        <!-- Fixture -->
        <div class="gms-field">
          <label class="gms-label">Fixture</label>
          <div class="mxm-fixture-row">
            <!-- Home -->
            <div class="mxm-team-col">
              <input v-model="form.homeCode" class="gms-input mxm-code-input" placeholder="—" maxlength="3" />
              <div class="mxm-chips">
                <button
                  v-for="cc in commonCodes" :key="cc ?? '__tbd'"
                  type="button"
                  class="mxm-chip"
                  :class="{ on: cc !== null ? form.homeCode === cc : form.homeCode === '' }"
                  @click="form.homeCode = cc ?? ''"
                >{{ cc ?? '🏆' }}</button>
              </div>
              <input v-model="form.homeTeam" class="gms-input" placeholder="Team name" />
            </div>
            <!-- VS -->
            <div class="mxm-vs-sep">v</div>
            <!-- Away -->
            <div class="mxm-team-col">
              <input v-model="form.awayCode" class="gms-input mxm-code-input" placeholder="—" maxlength="3" />
              <div class="mxm-chips">
                <button
                  v-for="cc in commonCodes" :key="cc ?? '__tbd'"
                  type="button"
                  class="mxm-chip"
                  :class="{ on: cc !== null ? form.awayCode === cc : form.awayCode === '' }"
                  @click="form.awayCode = cc ?? ''"
                >{{ cc ?? '🏆' }}</button>
              </div>
              <input v-model="form.awayTeam" class="gms-input" placeholder="Team name" />
            </div>
          </div>
        </div>

        <!-- Stage + Label -->
        <div class="gms-form-grid">
          <div class="gms-field">
            <label class="gms-label">Stage / round</label>
            <select v-model="form.stage" class="gms-input" @change="onStageChange">
              <option value="">Select stage</option>
              <option v-for="s in stageOptions" :key="s.code" :value="s.label">{{ s.label }}</option>
            </select>
          </div>
          <div class="gms-field">
            <label class="gms-label">Label</label>
            <input v-model="form.stageLabel" class="gms-input" placeholder="Opening Ceremony & Group A" />
          </div>
        </div>

        <!-- Date + Day -->
        <div class="gms-form-grid">
          <div class="gms-field">
            <label class="gms-label">Date</label>
            <input v-model="form.date" class="gms-input" placeholder="10 Aug 2026" />
          </div>
          <div class="gms-field">
            <label class="gms-label">Day</label>
            <input v-model="form.day" class="gms-input" placeholder="Mon" maxlength="3" />
          </div>
        </div>

        <!-- Kickoff + Venue -->
        <div class="gms-form-grid">
          <div class="gms-field">
            <label class="gms-label">Kick-off</label>
            <input v-model="form.kickoff" class="gms-input" placeholder="19:00" />
          </div>
          <div class="gms-field">
            <label class="gms-label">Venue</label>
            <select v-model="form.venueId" class="gms-input">
              <option value="">Select venue</option>
              <option v-for="v in venues" :key="v.id" :value="v.id">{{ v.name }}</option>
            </select>
          </div>
        </div>

        <!-- Capacity + Tickets sold -->
        <div class="gms-form-grid">
          <div class="gms-field">
            <label class="gms-label">Capacity</label>
            <input v-model="form.seatsTotal" class="gms-input" type="number" placeholder="320" />
          </div>
          <div class="gms-field">
            <label class="gms-label">Tickets sold</label>
            <input v-model="form.seatsSold" class="gms-input" type="number" placeholder="0" />
          </div>
        </div>

        <!-- Toggles -->
        <div class="mxm-toggles">
          <button type="button" class="mxm-toggle-pill" :class="{ on: form.featured }" @click="form.featured = !form.featured">
            <span class="mxm-check"><GmsIcon v-if="form.featured" name="check" :size="10" /></span>
            Featured fixture
          </button>
          <button type="button" class="mxm-toggle-pill" :class="{ on: form.bracketTBD }" @click="form.bracketTBD = !form.bracketTBD">
            <span class="mxm-check"><GmsIcon v-if="form.bracketTBD" name="check" :size="10" /></span>
            Bracket TBD
          </button>
        </div>

      </div>
      <template #footer>
        <button class="gms-btn gms-btn-ghost" @click="matchModal = false">Cancel</button>
        <button class="gms-btn gms-btn-primary" @click="saveMatch">
          {{ editingMatch ? 'Save changes' : 'Create match' }}
        </button>
      </template>
    </GmsModal>

    <!-- ── Delete Confirm ──────────────────────────────────────── -->
    <GmsModal :open="deleteModal" title="Delete Match" size="sm" @close="deleteModal = false">
      <p style="font-size:13.5px;color:var(--gms-text-2);">
        This match will be permanently removed along with any seat assignments.
      </p>
      <template #footer>
        <button class="gms-btn gms-btn-ghost" @click="deleteModal = false">Cancel</button>
        <button class="gms-btn gms-btn-danger" @click="confirmDelete">Delete</button>
      </template>
    </GmsModal>
  </div>
</template>
