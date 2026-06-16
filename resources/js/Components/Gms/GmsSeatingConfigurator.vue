<script setup>
import { ref, computed } from 'vue'
import GmsIcon from './GmsIcon.vue'

const props = defineProps({
    template:  { type: Object, default: null }, // null = new
    venueId:   { type: String, default: '' },
    venueName: { type: String, default: '' },
})

const emit = defineEmits(['back', 'saved'])

const isNew  = !props.template
const clone  = o => JSON.parse(JSON.stringify(o))

const STARTER_BLOCKS = [
    { id: 'A', label: 'Block A', tier: 'VVIP', rows: [
        { label: '1', seats: 10, aisles: [5] },
        { label: '2', seats: 12, aisles: [6] },
    ]},
]

const name      = ref(props.template?.name ?? '')
const draft     = ref({ blocks: clone(props.template ? props.template.blocks : STARTER_BLOCKS) })
const selId     = ref(draft.value.blocks[0]?.id ?? null)

const selBlock  = computed(() => draft.value.blocks.find(b => b.id === selId.value) ?? draft.value.blocks[0])
const totalSeats= computed(() => draft.value.blocks.reduce((a, b) => a + b.rows.reduce((x, r) => x + r.seats, 0), 0))
const totalRows = computed(() => draft.value.blocks.reduce((a, b) => a + b.rows.length, 0))
const origSeats = props.template
    ? props.template.blocks.reduce((a, b) => a + b.rows.reduce((x, r) => x + r.seats, 0), 0)
    : 0
const seatDelta = computed(() => {
    if (isNew || totalSeats.value === origSeats) return null
    const d = totalSeats.value - origSeats
    return (d > 0 ? '+' : '') + d
})

// ── Block helpers ─────────────────────────────────────────────────
function nextId() {
    const used = new Set(draft.value.blocks.map(b => b.id))
    for (const ch of 'ABCDEFGHIJKLMNOPQRSTUVWXYZ') if (!used.has(ch)) return ch
    return 'Z' + draft.value.blocks.length
}
function setBlocks(fn) { draft.value = { ...draft.value, blocks: fn(draft.value.blocks) } }
function setRows(bid, fn) {
    setBlocks(bs => bs.map(b => b.id !== bid ? b : { ...b, rows: fn(b.rows) }))
}

function addBlock() {
    const id = nextId()
    setBlocks(bs => [...bs, { id, label: 'Block ' + id, tier: 'VIP', rows: [{ label: '1', seats: 12, aisles: [6] }] }])
    selId.value = id
}
function delBlock(id) {
    setBlocks(bs => {
        const next = bs.filter(b => b.id !== id)
        if (selId.value === id && next[0]) selId.value = next[0].id
        return next
    })
}
function patchBlock(patch) { setBlocks(bs => bs.map(b => b.id === selId.value ? { ...b, ...patch } : b)) }

function updateBlockCode(newCode) {
    newCode = newCode.trim()
    if (!newCode || newCode === selId.value) return
    
    // Check if code already exists
    const exists = draft.value.blocks.some(b => b.id === newCode && b.id !== selId.value)
    if (exists) {
        alert('Block code "' + newCode + '" already exists. Please use a unique code.')
        return
    }
    
    const oldId = selId.value
    setBlocks(bs => bs.map(b => b.id === oldId ? { ...b, id: newCode } : b))
    selId.value = newCode
}

// ── Row helpers ───────────────────────────────────────────────────
function addRow(count = 1) {
    for (let k = 0; k < count; k++) {
        setRows(selId.value, rows => {
            const last  = rows[rows.length - 1]
            const num   = parseInt(last?.label ?? '0', 10)
            const label = String((isNaN(num) ? rows.length : num) + 1)
            return [...rows, { label, seats: last ? last.seats : 12, aisles: last ? clone(last.aisles || []) : [] }]
        })
    }
}
function dupRow(ri) {
    setRows(selId.value, rows => {
        const r = clone(rows[ri])
        r.label = r.label + 'b'
        const next = [...rows]
        next.splice(ri + 1, 0, r)
        return next
    })
}
function delRow(ri) { setRows(selId.value, rows => rows.filter((_, i) => i !== ri)) }
function moveRow(ri, dir) {
    setRows(selId.value, rows => {
        const j = ri + dir
        if (j < 0 || j >= rows.length) return rows
        const next = [...rows]
        const t = next[ri]; next[ri] = next[j]; next[j] = t
        return next
    })
}
function patchRow(ri, patch) {
    setRows(selId.value, rows => rows.map((r, i) => i === ri ? { ...r, ...patch } : r))
}
function setSeatCount(ri, n) {
    setRows(selId.value, rows => rows.map((r, i) => {
        if (i !== ri) return r
        const seats = Math.max(1, Math.min(85, isNaN(n) ? 1 : n))
        return { ...r, seats, aisles: (r.aisles || []).filter(a => a < seats) }
    }))
}
function toggleAisle(ri, col) {
    setRows(selId.value, rows => rows.map((r, i) => {
        if (i !== ri) return r
        const set = new Set(r.aisles || [])
        set.has(col) ? set.delete(col) : set.add(col)
        return { ...r, aisles: [...set].sort((a, b) => a - b) }
    }))
}

// ── Save ──────────────────────────────────────────────────────────
const nameErr = ref(false)
function save() {
    if (!name.value.trim()) { nameErr.value = true; return }
    nameErr.value = false
    emit('saved', {
        id:      props.template?.id ?? ('tpl-' + Date.now()),
        name:    name.value.trim(),
        venueId: props.venueId,
        blocks:  clone(draft.value.blocks),
        isNew,
    })
}
</script>

<template>
  <div class="gms-view">

    <!-- Header -->
    <div class="gms-seat-header">
      <div class="gms-seat-back" @click="$emit('back')">← Back</div>
      <div class="gms-seat-title-row">
        <div class="gms-seat-title-area">
          <h1 class="gms-seat-title">{{ isNew ? 'New seating template' : 'Edit template' }}</h1>
          <div class="gms-seat-subtitle">
            {{ venueName || 'Venue' }} · configure blocks, rows, seat counts and aisle positions — each row can have its own seat count
          </div>
        </div>
        <div class="gms-seat-actions">
          <button class="gms-btn gms-btn-sm gms-btn-ghost" @click="$emit('back')">Cancel</button>
          <button class="gms-btn gms-btn-sm gms-btn-primary" @click="save">
            <GmsIcon name="check" :size="13" /> {{ isNew ? 'Create template' : 'Save template' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Template name card -->
    <div class="gms-card" style="padding:14px 18px;margin-bottom:16px;display:flex;gap:18px;align-items:flex-end;flex-wrap:wrap;">
      <div style="flex:1 1 280px;min-width:220px;">
        <label class="gms-label">Template name</label>
        <input
          v-model="name"
          class="gms-input"
          :class="nameErr ? 'gms-input-error' : ''"
          placeholder="e.g. Lusail — Full VIP Tribune"
          @input="nameErr=false"
          style="margin-top:4px;"
        />
        <div v-if="nameErr" style="font-size:11.5px;color:#dc2626;margin-top:3px;">Template name is required.</div>
      </div>
      <div style="padding-bottom:14px;">
        <div style="font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--gms-text-3);margin-bottom:4px;">Venue</div>
        <span class="gms-pill" style="background:var(--gms-invited-bg);color:var(--gms-invited-fg);font-weight:600;">
          {{ venueName || '—' }}
        </span>
      </div>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px;">
      <div class="gms-stat-card">
        <div class="gms-stat-value" style="color:#3b82f6;">{{ draft.blocks.length }}</div>
        <div class="gms-stat-label">Blocks</div>
      </div>
      <div class="gms-stat-card">
        <div class="gms-stat-value" style="color:var(--gms-gold);">{{ totalRows }}</div>
        <div class="gms-stat-label">Rows</div>
      </div>
      <div class="gms-stat-card">
        <div class="gms-stat-value" style="color:var(--gms-maroon);">
          {{ totalSeats }}
          <span v-if="seatDelta" style="font-size:14px;font-weight:600;font-family:var(--gms-font-ui);"
            :style="{color: seatDelta.startsWith('+') ? '#15803d' : '#dc2626'}">
            {{ seatDelta }}
          </span>
        </div>
        <div class="gms-stat-label">Total seats</div>
      </div>
    </div>

    <div class="gms-lc-grid">

      <!-- ── Block strip ─────────────────────────────────────────── -->
      <div class="gms-lc-blocks-bar">
        <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--gms-text-3);flex:none;">
          Blocks
        </div>
        <div class="gms-lc-blocks-scroll">
          <div
            v-for="b in draft.blocks" :key="b.id"
            :class="['gms-lc-bchip', b.id === selId ? 'on' : '']"
            @click="selId = b.id"
          >
            <div class="gms-lc-block-id">{{ b.id }}</div>
            <div class="gms-lc-bchip-meta">
              <div class="gms-lc-bchip-name">{{ b.label }}</div>
              <div style="font-size:11px;color:var(--gms-text-3);white-space:nowrap;">
                {{ b.tier }} · {{ b.rows.length }}r · {{ b.rows.reduce((s,r)=>s+r.seats,0) }}s
              </div>
            </div>
            <button v-if="draft.blocks.length > 1"
              class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon gms-lc-del-btn"
              title="Delete block"
              @click.stop="delBlock(b.id)">
              <GmsIcon name="x" :size="12" />
            </button>
          </div>
        </div>
        <button class="gms-btn gms-btn-sm" style="flex:none;" @click="addBlock">
          <GmsIcon name="plus" :size="13" /> Add block
        </button>
      </div>

      <!-- ── Block editor ────────────────────────────────────────── -->
      <div v-if="selBlock" class="gms-lc-editor">

        <!-- Live preview (sticky) -->
        <div class="gms-lc-preview-top">
          <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--gms-text-3);margin-bottom:8px;display:flex;align-items:center;gap:7px;">
            <GmsIcon name="map" :size="12" /> Live preview
            <span style="font-weight:400;color:var(--gms-text-3);">· {{ selBlock.label }} · {{ selBlock.tier }}</span>
          </div>
          <div class="gms-lc-preview-scroll">
            <div class="gms-pitch" style="height:28px;font-size:9px;letter-spacing:2px;width:70%;max-width:320px;margin:0 auto 10px;">PITCH</div>
            <div style="display:flex;flex-direction:column;gap:4px;align-items:center;">
              <template v-for="(row, ri) in selBlock.rows" :key="ri">
                <div style="display:flex;align-items:center;gap:3px;justify-content:center;">
                  <span style="width:16px;font-size:9px;font-family:var(--gms-font-mono);color:var(--gms-text-3);text-align:center;flex:none;">{{ row.label }}</span>
                  <template v-for="c in row.seats" :key="c">
                    <span class="gms-lc-pv-seat">
                      <span class="gms-lc-pv-num">{{ c }}</span>
                      <span class="gms-seat available" style="width:13px;height:13px;cursor:default;border-radius:3px;" />
                    </span>
                    <span
                      v-if="c < row.seats"
                      :class="['gms-cfg-gap', (row.aisles||[]).includes(c) ? 'on' : '']"
                    />
                  </template>
                  <span style="width:16px;font-size:9px;font-family:var(--gms-font-mono);color:var(--gms-text-3);text-align:center;flex:none;">{{ row.label }}</span>
                </div>
                <div v-if="row.walkway" class="gms-walkway" style="width:70%;max-width:280px;"><span>walkway</span></div>
              </template>
            </div>
          </div>
        </div>

        <!-- Block code, name + tier -->
        <div class="gms-lc-editor-head">
          <div class="gms-field" style="margin-bottom:0;">
            <label class="gms-label">Block code</label>
            <input class="gms-input" :value="selBlock.id" @blur="updateBlockCode($event.target.value)" @keydown.enter="$event.target.blur()" placeholder="A" maxlength="8" style="margin-top:4px;font-family:var(--gms-font-mono);" />
          </div>
          <div class="gms-field" style="flex:1;margin-bottom:0;">
            <label class="gms-label">Block name</label>
            <input class="gms-input" :value="selBlock.label" @input="patchBlock({label: $event.target.value})" style="margin-top:4px;" />
          </div>
          <div class="gms-field" style="margin-bottom:0;">
            <label class="gms-label">Tier</label>
            <div class="gms-seg" style="margin-top:4px;">
              <button :class="selBlock.tier==='VVIP' ? 'on' : ''" @click="patchBlock({tier:'VVIP'})">VVIP</button>
              <button :class="selBlock.tier==='VIP'  ? 'on' : ''" @click="patchBlock({tier:'VIP'})">VIP</button>
            </div>
          </div>
        </div>

        <!-- Aisle tip -->
        <div class="gms-hint" style="margin:10px 0 12px;">
          <GmsIcon name="info" :size="13" />
          Click the gap between two seats in the dot strip to add or remove an <strong>aisle</strong>.
          Aisles appear as golden dividers in the preview.
        </div>

        <!-- Rows list -->
        <div class="gms-lc-rows">
          <div v-for="(row, ri) in selBlock.rows" :key="ri" class="gms-lc-row">

            <!-- Move up/down -->
            <div class="gms-lc-row-move">
              <button class="gms-lc-mv" :disabled="ri === 0" @click="moveRow(ri,-1)" title="Move up">▲</button>
              <button class="gms-lc-mv" :disabled="ri === selBlock.rows.length-1" @click="moveRow(ri,1)" title="Move down">▼</button>
            </div>

            <!-- Row label -->
            <div class="gms-lc-row-label">
              <span style="font-size:9.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--gms-text-3);">ROW</span>
              <input class="gms-lc-rowname" :value="row.label" @input="patchRow(ri,{label:$event.target.value})" />
            </div>

            <!-- Seat stepper -->
            <div class="gms-lc-stepper">
              <button @click="setSeatCount(ri, row.seats-1)">−</button>
              <input class="gms-lc-seatnum" type="number" :value="row.seats" min="1" max="85"
                @change="setSeatCount(ri, parseInt($event.target.value,10))" />
              <button @click="setSeatCount(ri, row.seats+1)">+</button>
              <span style="font-size:11px;color:var(--gms-text-3);margin-left:4px;">seats</span>
            </div>

            <!-- Dot strip with clickable aisle gaps -->
            <div class="gms-lc-row-prev">
              <div class="gms-cfg-dots">
                <template v-for="c in row.seats" :key="c">
                  <span class="gms-cfg-seatcol">
                    <span class="gms-cfg-num">{{ c }}</span>
                    <span class="gms-cfg-seat" />
                  </span>
                  <span
                    v-if="c < row.seats"
                    :class="['gms-cfg-gap', 'clickable', (row.aisles||[]).includes(c) ? 'on' : '']"
                    :title="(row.aisles||[]).includes(c) ? `Remove aisle after seat ${c}` : `Add aisle after seat ${c}`"
                    @click="toggleAisle(ri, c)"
                  />
                </template>
              </div>
            </div>

            <!-- Row actions -->
            <div class="gms-lc-row-acts">
              <button
                :class="['gms-btn', 'gms-btn-sm', row.walkway ? 'gms-btn-primary' : 'gms-btn-ghost']"
                style="font-size:11.5px;padding:4px 9px;"
                :title="row.walkway ? 'Remove walkway after this row' : 'Add walkway after this row'"
                @click="patchRow(ri,{walkway:!row.walkway})"
              >walkway</button>
              <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" title="Duplicate row" @click="dupRow(ri)">⧉</button>
              <button v-if="selBlock.rows.length > 1"
                class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" title="Delete row" @click="delRow(ri)">
                <GmsIcon name="x" :size="12" />
              </button>
            </div>
          </div>
        </div>

        <!-- Add row buttons -->
        <div style="display:flex;gap:8px;margin-top:12px;">
          <button class="gms-btn gms-btn-sm" @click="addRow(1)"><GmsIcon name="plus" :size="13" /> Add row</button>
          <button class="gms-btn gms-btn-sm" @click="addRow(3)"><GmsIcon name="plus" :size="13" /> Add 3 rows</button>
        </div>

        <div class="gms-hint" style="margin-top:10px;">
          <GmsIcon name="info" :size="13" />
          Saving regenerates the seat map and preserves existing assignments where seats still exist.
        </div>

      </div><!-- /editor -->
    </div><!-- /lc-grid -->
  </div>
</template>
