<script setup>
import { ref, reactive, computed, watch, onMounted, nextTick, inject } from 'vue'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'

defineOptions({ layout: GmsLayout })

const toast = inject('toast')

const props = defineProps({
  plan:   { type: Object, required: true },
  guests: { type: Array, default: () => [] },
  event:  { type: Object, required: true },
})

const TABLE_DEFS = {
  round:      { seats: 8,  w: 100, h: 100, label: 'Round',      isTable: true },
  banquet:    { seats: 10, w: 160, h: 52,  label: 'Banquet',     isTable: true },
  square:     { seats: 4,  w: 76,  h: 76,  label: 'Square',      isTable: true },
  head:       { seats: 8,  w: 180, h: 44,  label: 'Head table',  isTable: true },
  sweetheart: { seats: 2,  w: 72,  h: 72,  label: 'Sweetheart',  isTable: true },
  cocktail:   { seats: 0,  w: 44,  h: 44,  label: 'Cocktail',    isTable: true },
}

const FEATURE_DEFS = {
  stage:    { w: 200, h: 60,  label: 'Stage' },
  dance:    { w: 160, h: 120, label: 'Dance floor' },
  bar:      { w: 120, h: 44,  label: 'Bar' },
  entrance: { w: 90,  h: 30,  label: 'Entrance' },
  wall:     { w: 140, h: 6,   label: 'Wall' },
  pillar:   { w: 24,  h: 24,  label: 'Pillar' },
}

const TIER_OPTIONS = ['None', 'VVIP', 'VIP', 'Platinum', 'Gold', 'Silver']

const items = reactive(props.plan.data?.items?.length ? JSON.parse(JSON.stringify(props.plan.data.items)) : [])
const planName = ref(props.plan.name || 'Gala Dinner')

const roomW = ref(props.plan.data?.roomW || 780)
const roomH = ref(props.plan.data?.roomH || 600)

const showGrid = ref(true)
const snapEnabled = ref(true)
const tierColors = ref(true)
const GRID_SIZE = 16
const SNAP_SIZE = 16

const scale = ref(0.86)
const panX = ref(0)
const panY = ref(0)

const selectedId = ref(null)
const selGuest = ref(null)
const guestFilter = ref('all')
const guestSearch = ref('')

const canvasWrap = ref(null)
let isPanning = ref(false)
let panStartX = 0, panStartY = 0, panStartPX = 0, panStartPY = 0

let isDragging = ref(false)
let dragItem = null
let dragStartX = 0, dragStartY = 0, dragItemX = 0, dragItemY = 0

let isResizing = ref(false)
let resizeItem = null
let resizeStartX = 0, resizeStartY = 0, resizeStartW = 0, resizeStartH = 0

let isRotating = ref(false)
let rotateItem = null
let rotateCenterX = 0, rotateCenterY = 0

let uid = Math.max(0, ...items.map(i => i.id || 0))

const guestById = computed(() => Object.fromEntries(props.guests.map(g => [g.id, g])))

const seatedIds = computed(() => {
  const map = {}
  for (const it of items) {
    if (!it.assign) continue
    for (const [, gid] of Object.entries(it.assign)) {
      if (gid) map[gid] = it.name || it.id
    }
  }
  return map
})

const seatedCount = computed(() => Object.keys(seatedIds.value).length)
const totalSeats = computed(() => items.reduce((n, t) => n + (t.seats || 0), 0))
const tableCount = computed(() => items.filter(i => TABLE_DEFS[i.type]).length)

const selectedItem = computed(() => items.find(i => i.id === selectedId.value))
const isTable = computed(() => selectedItem.value && !!TABLE_DEFS[selectedItem.value.type])
const itemLabel = computed(() => {
  if (!selectedItem.value) return ''
  const def = TABLE_DEFS[selectedItem.value.type] || FEATURE_DEFS[selectedItem.value.type]
  return def?.label || selectedItem.value.type
})

const filteredGuests = computed(() => {
  let list = props.guests
  if (guestFilter.value === 'unseated') {
    list = list.filter(g => !seatedIds.value[g.id])
  }
  if (guestSearch.value) {
    const q = guestSearch.value.toLowerCase()
    list = list.filter(g => g.name.toLowerCase().includes(q))
  }
  return list
})

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').filter(w => w.length).map(w => w[0].toUpperCase()).slice(0, 2).join('')
}

const tierColorMap = {
  'Platinum': '#8a1f3d',
  'Gold':     '#c4973a',
  'Silver':   '#6b5c53',
  'Bronze':   '#8b6914',
}

function getTierColor(tier) {
  return tierColorMap[tier] || '#a09488'
}

// ── Chair positioning ──
function getChairPositions(item) {
  const { type, seats, w, h } = item
  if (!seats || seats === 0) return []
  const positions = []
  const cs = 19
  const gap = 5

  if (type === 'round') {
    const radius = w / 2 + cs / 2 + gap
    for (let i = 0; i < seats; i++) {
      const a = (i / seats) * 2 * Math.PI - Math.PI / 2
      positions.push({ x: Math.cos(a) * radius, y: Math.sin(a) * radius, rot: (a * 180 / Math.PI) + 90 })
    }
  } else if (type === 'sweetheart') {
    const spacing = w / (seats + 1)
    for (let i = 0; i < seats; i++) {
      positions.push({ x: -w / 2 + spacing * (i + 1), y: -h / 2 - cs / 2 - gap + 2, rot: 0 })
    }
  } else if (type === 'banquet') {
    const top = Math.ceil(seats / 2)
    const bot = seats - top
    const spacing = w / (top + 1)
    for (let i = 0; i < top; i++) positions.push({ x: -w / 2 + spacing * (i + 1), y: -h / 2 - cs / 2 - gap, rot: 0 })
    const spacingB = w / (bot + 1)
    for (let i = 0; i < bot; i++) positions.push({ x: -w / 2 + spacingB * (i + 1), y: h / 2 + cs / 2 + gap, rot: 180 })
  } else if (type === 'head') {
    const spacing = w / (seats + 1)
    for (let i = 0; i < seats; i++) positions.push({ x: -w / 2 + spacing * (i + 1), y: h / 2 + cs / 2 + gap, rot: 180 })
  } else if (type === 'square') {
    const perSide = Math.max(1, Math.ceil(seats / 4))
    let placed = 0
    const sides = [
      { axis: 'x', crossVal: -h / 2 - cs / 2 - gap, rot: 0 },
      { axis: 'y', crossVal:  w / 2 + cs / 2 + gap, rot: 90 },
      { axis: 'x', crossVal:  h / 2 + cs / 2 + gap, rot: 180 },
      { axis: 'y', crossVal: -w / 2 - cs / 2 - gap, rot: 270 },
    ]
    for (let s = 0; s < 4 && placed < seats; s++) {
      const count = Math.min(perSide, seats - placed)
      const side = sides[s]
      const dim = side.axis === 'x' ? w : h
      const spacing = dim / (count + 1)
      for (let i = 0; i < count && placed < seats; i++) {
        const pos = -dim / 2 + spacing * (i + 1)
        positions.push({ x: side.axis === 'x' ? pos : side.crossVal, y: side.axis === 'y' ? pos : side.crossVal, rot: side.rot })
        placed++
      }
    }
  }
  return positions
}

function chairStyle(pos) {
  return { left: `calc(50% + ${pos.x}px)`, top: `calc(50% + ${pos.y}px)`, transform: `translate(-50%, -50%) rotate(${pos.rot}deg)` }
}

// ── Item CRUD ──
function addItem(type) {
  const def = TABLE_DEFS[type] || FEATURE_DEFS[type]
  if (!def) return
  const wrap = canvasWrap.value
  const cx = wrap ? (wrap.clientWidth / 2 - panX.value) / scale.value : roomW.value / 2
  const cy = wrap ? (wrap.clientHeight / 2 - panY.value) / scale.value : roomH.value / 2
  let x = Math.max(0, Math.min(roomW.value - def.w, cx - def.w / 2))
  let y = Math.max(0, Math.min(roomH.value - def.h, cy - def.h / 2))
  if (snapEnabled.value) { x = Math.round(x / SNAP_SIZE) * SNAP_SIZE; y = Math.round(y / SNAP_SIZE) * SNAP_SIZE }
  const isT = !!TABLE_DEFS[type]
  const tableNum = isT ? items.filter(i => TABLE_DEFS[i.type]).length + 1 : 0
  items.push({ id: ++uid, type, x, y, w: def.w, h: def.h, rotation: 0, seats: def.seats || 0, name: isT ? `${tableNum}` : def.label.toUpperCase(), assign: {}, tierLabel: '' })
  selectedId.value = uid
}

function duplicateItem(id) {
  const src = items.find(i => i.id === id)
  if (!src) return
  const clone = JSON.parse(JSON.stringify(src))
  clone.id = ++uid
  clone.x += 20
  clone.y += 20
  clone.assign = {}
  if (TABLE_DEFS[clone.type]) {
    clone.name = `${items.filter(i => TABLE_DEFS[i.type]).length + 1}`
  }
  items.push(clone)
  selectedId.value = clone.id
}

function deleteItem(id) {
  const idx = items.findIndex(i => i.id === id)
  if (idx >= 0) { items.splice(idx, 1); if (selectedId.value === id) selectedId.value = null }
}

// ── Inspector setters ──
function setSeats(item, n) {
  item.seats = n
  const keys = Object.keys(item.assign).map(Number).filter(k => k >= n)
  keys.forEach(k => delete item.assign[k])
}

function setRotation(item, deg) { item.rotation = deg % 360 }

function setDiameter(item, ft) {
  const px = Math.round(ft * 16)
  item.w = px; item.h = px
}

function setWidth(item, ft) { item.w = Math.round(ft * 16) }
function setHeight(item, ft) { item.h = Math.round(ft * 16) }

// ── Canvas pan/zoom ──
function onWheel(e) {
  e.preventDefault()
  const delta = e.deltaY > 0 ? -0.05 : 0.05
  const newScale = Math.max(0.2, Math.min(3, scale.value + delta))
  const rect = canvasWrap.value.getBoundingClientRect()
  const mx = e.clientX - rect.left, my = e.clientY - rect.top
  panX.value = mx - (mx - panX.value) * (newScale / scale.value)
  panY.value = my - (my - panY.value) * (newScale / scale.value)
  scale.value = newScale
}
function zoomIn() { scale.value = Math.min(3, scale.value + 0.1) }
function zoomOut() { scale.value = Math.max(0.2, scale.value - 0.1) }
function zoomFit() {
  if (!canvasWrap.value) return
  const cw = canvasWrap.value.clientWidth, ch = canvasWrap.value.clientHeight, pad = 40
  scale.value = Math.min((cw - pad * 2) / roomW.value, (ch - pad * 2) / roomH.value, 1.5)
  panX.value = (cw - roomW.value * scale.value) / 2
  panY.value = (ch - roomH.value * scale.value) / 2
}

function onCanvasMouseDown(e) {
  if (e.target.closest('.fp-item') || e.target.closest('.fp-chair') || e.target.closest('.fp-handle')) return
  selectedId.value = null
  isPanning.value = true
  panStartX = e.clientX; panStartY = e.clientY; panStartPX = panX.value; panStartPY = panY.value
  document.addEventListener('mousemove', onCanvasMouseMove)
  document.addEventListener('mouseup', onCanvasMouseUp)
}
function onCanvasMouseMove(e) { if (isPanning.value) { panX.value = panStartPX + (e.clientX - panStartX); panY.value = panStartPY + (e.clientY - panStartY) } }
function onCanvasMouseUp() { isPanning.value = false; document.removeEventListener('mousemove', onCanvasMouseMove); document.removeEventListener('mouseup', onCanvasMouseUp) }

// ── Item dragging ──
function onItemMouseDown(e, item) {
  if (e.target.closest('.fp-handle')) return
  e.stopPropagation()
  selectedId.value = item.id
  isDragging.value = true; dragItem = item
  dragStartX = e.clientX; dragStartY = e.clientY; dragItemX = item.x; dragItemY = item.y
  document.addEventListener('mousemove', onItemMouseMove)
  document.addEventListener('mouseup', onItemMouseUp)
}
function onItemMouseMove(e) {
  if (!isDragging.value || !dragItem) return
  let newX = dragItemX + (e.clientX - dragStartX) / scale.value
  let newY = dragItemY + (e.clientY - dragStartY) / scale.value
  if (snapEnabled.value) { newX = Math.round(newX / SNAP_SIZE) * SNAP_SIZE; newY = Math.round(newY / SNAP_SIZE) * SNAP_SIZE }
  dragItem.x = Math.max(-dragItem.w / 2, Math.min(roomW.value - dragItem.w / 2, newX))
  dragItem.y = Math.max(-dragItem.h / 2, Math.min(roomH.value - dragItem.h / 2, newY))
}
function onItemMouseUp() { isDragging.value = false; dragItem = null; document.removeEventListener('mousemove', onItemMouseMove); document.removeEventListener('mouseup', onItemMouseUp) }

// ── Resize handle ──
function onResizeDown(e, item) {
  e.stopPropagation(); e.preventDefault()
  isResizing.value = true; resizeItem = item
  resizeStartX = e.clientX; resizeStartY = e.clientY; resizeStartW = item.w; resizeStartH = item.h
  document.addEventListener('mousemove', onResizeMove)
  document.addEventListener('mouseup', onResizeUp)
}
function onResizeMove(e) {
  if (!isResizing.value || !resizeItem) return
  const dx = (e.clientX - resizeStartX) / scale.value
  const dy = (e.clientY - resizeStartY) / scale.value
  const isProportional = resizeItem.type === 'round' || resizeItem.type === 'cocktail' || resizeItem.type === 'sweetheart'
  if (isProportional) {
    const d = Math.max(dx, dy)
    const sz = Math.max(30, resizeStartW + d)
    resizeItem.w = sz; resizeItem.h = sz
  } else {
    resizeItem.w = Math.max(30, resizeStartW + dx)
    resizeItem.h = Math.max(10, resizeStartH + dy)
  }
}
function onResizeUp() { isResizing.value = false; resizeItem = null; document.removeEventListener('mousemove', onResizeMove); document.removeEventListener('mouseup', onResizeUp) }

// ── Rotate handle ──
function onRotateDown(e, item) {
  e.stopPropagation(); e.preventDefault()
  isRotating.value = true; rotateItem = item
  const wrap = canvasWrap.value.getBoundingClientRect()
  rotateCenterX = wrap.left + panX.value + (item.x + item.w / 2) * scale.value
  rotateCenterY = wrap.top + panY.value + (item.y + item.h / 2) * scale.value
  document.addEventListener('mousemove', onRotateMove)
  document.addEventListener('mouseup', onRotateUp)
}
function onRotateMove(e) {
  if (!isRotating.value || !rotateItem) return
  const angle = Math.atan2(e.clientY - rotateCenterY, e.clientX - rotateCenterX)
  let deg = Math.round((angle * 180 / Math.PI + 90) % 360)
  if (deg < 0) deg += 360
  if (snapEnabled.value) deg = Math.round(deg / 15) * 15
  rotateItem.rotation = deg
}
function onRotateUp() { isRotating.value = false; rotateItem = null; document.removeEventListener('mousemove', onRotateMove); document.removeEventListener('mouseup', onRotateUp) }

// ── Chair click ──
function clickChair(e, item, seatIdx) {
  e.stopPropagation()
  if (item.assign[seatIdx]) { item.assign[seatIdx] = null; return }
  if (selGuest.value) { item.assign[seatIdx] = selGuest.value.id; selGuest.value = null }
}
function seatGuest(item, idx) { return guestById.value[item.assign?.[idx]] }

function occupancy(item) {
  if (!item.assign) return 0
  return Object.values(item.assign).filter(Boolean).length
}

// ── Sample data ──
function loadSample() {
  items.splice(0, items.length); uid = 0
  const sampleItems = [
    { type: 'stage', x: 260, y: 16, w: 200, h: 60, name: 'MAIN STAGE' },
    { type: 'head', x: 420, y: 36, w: 180, h: 44, seats: 8, name: 'Head' },
    { type: 'dance', x: 360, y: 130, w: 160, h: 120, name: 'DANCE FLOOR' },
    { type: 'bar', x: 560, y: 150, w: 120, h: 44, name: 'BAR' },
    { type: 'entrance', x: 340, y: 540, w: 90, h: 30, name: 'ENTRANCE' },
    { type: 'round', x: 140, y: 155, w: 100, h: 100, seats: 8, name: '1' },
    { type: 'round', x: 260, y: 245, w: 120, h: 120, seats: 10, name: '2' },
    { type: 'round', x: 490, y: 195, w: 100, h: 100, seats: 8, name: '3' },
    { type: 'round', x: 590, y: 210, w: 100, h: 100, seats: 8, name: '4' },
    { type: 'round', x: 140, y: 310, w: 100, h: 100, seats: 8, name: '5' },
    { type: 'round', x: 440, y: 290, w: 80, h: 80, seats: 6, name: '6' },
    { type: 'round', x: 480, y: 310, w: 100, h: 100, seats: 8, name: '7' },
    { type: 'round', x: 590, y: 330, w: 100, h: 100, seats: 8, name: '8' },
    { type: 'round', x: 180, y: 420, w: 100, h: 100, seats: 8, name: '9' },
    { type: 'round', x: 310, y: 400, w: 100, h: 100, seats: 8, name: '10' },
    { type: 'round', x: 460, y: 410, w: 80, h: 80, seats: 6, name: '11' },
    { type: 'round', x: 540, y: 410, w: 80, h: 80, seats: 6, name: '12' },
    { type: 'banquet', x: 280, y: 120, w: 60, h: 52, seats: 6, name: '13' },
  ]
  sampleItems.forEach(si => {
    const def = TABLE_DEFS[si.type] || FEATURE_DEFS[si.type]
    items.push({ id: ++uid, type: si.type, x: si.x, y: si.y, w: si.w || def.w, h: si.h || def.h, rotation: 0, seats: si.seats ?? def.seats ?? 0, name: si.name || '', assign: {}, tierLabel: '' })
  })
  if (props.guests.length >= 5) {
    const g = props.guests
    const t2 = items.find(i => i.name === '2'); if (t2) { t2.assign[0] = g[0]?.id; t2.assign[1] = g[1]?.id }
    const t5 = items.find(i => i.name === '5'); if (t5) t5.assign[0] = g[2]?.id
    const t8 = items.find(i => i.name === '8'); if (t8) { t8.assign[0] = g[3]?.id; t8.assign[1] = g[4]?.id }
  }
  nextTick(zoomFit); toast('Sample floor plan loaded')
}

function newPlan() {
  if (items.length && !confirm('Clear current plan and start fresh?')) return
  items.splice(0, items.length); uid = 0; planName.value = 'Untitled Plan'; selectedId.value = null; nextTick(zoomFit)
}

const roomFtW = computed({ get: () => +(roomW.value / 16).toFixed(1), set: v => { roomW.value = Math.round(v * 16) } })
const roomFtH = computed({ get: () => +(roomH.value / 16).toFixed(1), set: v => { roomH.value = Math.round(v * 16) } })

// ── Autosave ──
const saving = ref(false)
const savedAt = ref(null)
let timer
function persist() {
  saving.value = true
  window.axios.post(`/gms/floorplans/${props.plan.id}`, { name: planName.value, data: { items, roomW: roomW.value, roomH: roomH.value } })
    .then(r => { savedAt.value = r.data.savedAt })
    .catch(() => { toast('Failed to save floor plan', 'error') })
    .finally(() => { saving.value = false })
}
watch([items, planName, roomW, roomH], () => { clearTimeout(timer); timer = setTimeout(persist, 700) }, { deep: true })

onMounted(() => { nextTick(zoomFit) })

const stageTransform = computed(() => `translate(${panX.value}px, ${panY.value}px) scale(${scale.value})`)
const zoomPct = computed(() => Math.round(scale.value * 100))

function itemStyle(item) {
  const s = { left: item.x + 'px', top: item.y + 'px', width: item.w + 'px', height: item.h + 'px' }
  if (item.rotation) s.transform = `rotate(${item.rotation}deg)`
  return s
}

// inspector computed helpers
const inspDiameterFt = computed(() => selectedItem.value ? +(selectedItem.value.w / 16).toFixed(1) : 0)
const inspWidthFt = computed(() => selectedItem.value ? +(selectedItem.value.w / 16).toFixed(1) : 0)
const inspHeightFt = computed(() => selectedItem.value ? +(selectedItem.value.h / 16).toFixed(1) : 0)
const isCircularTable = computed(() => selectedItem.value && (selectedItem.value.type === 'round' || selectedItem.value.type === 'cocktail'))
</script>

<template>
  <div class="gms-view fp-view">
    <!-- Header -->
    <div class="fp-ehead">
      <div class="fp-etitle">
        <h1>Floor plans</h1>
        <div class="sub">Banquet &amp; reception seating · {{ event?.name || 'Event' }}</div>
      </div>
      <div class="fp-estats">
        <span><b>{{ tableCount }}</b> tables</span>
        <span><b>{{ totalSeats }}</b> seats</span>
        <span><b>{{ seatedCount }}</b> seated</span>
      </div>
      <div style="flex:1" />
      <div class="fp-eactions">
        <button class="gms-btn gms-btn-ghost gms-btn-sm" @click="loadSample">Sample</button>
        <button class="gms-btn gms-btn-primary gms-btn-sm" @click="newPlan">New plan</button>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="fp-toolbar">
      <div class="fp-tg">
        <button class="fp-tt" :class="{ on: showGrid }" @click="showGrid = !showGrid">Grid</button>
        <button class="fp-tt" :class="{ on: snapEnabled }" @click="snapEnabled = !snapEnabled">Snap</button>
        <button class="fp-tt" :class="{ on: tierColors }" @click="tierColors = !tierColors">Tier colours</button>
      </div>
      <div class="fp-room">
        <span class="fp-room-l">ROOM</span>
        <input type="range" class="fp-slider sm" :min="20" :max="80" :step="0.5" v-model.number="roomFtW" />
        <span class="fp-room-v">{{ roomFtW.toFixed(1) }}</span>
        <span class="fp-room-l">ft</span>
        <span class="fp-room-x">&times;</span>
        <input type="range" class="fp-slider sm" :min="20" :max="60" :step="0.5" v-model.number="roomFtH" />
        <span class="fp-room-v">{{ roomFtH.toFixed(1) }}</span>
        <span class="fp-room-l">ft</span>
      </div>
      <div style="flex:1" />
      <span class="fp-save" v-if="saving || savedAt">{{ saving ? 'Saving…' : 'Saved' }}</span>
      <div style="display:flex;align-items:center;gap:6px;">
        <button class="fp-zoom-btn" @click="zoomOut"><GmsIcon name="minus" :size="14" /></button>
        <span class="fp-zoom-v">{{ zoomPct }}%</span>
        <button class="fp-zoom-btn" @click="zoomIn"><GmsIcon name="plus" :size="14" /></button>
        <button class="fp-zoom-fit" @click="zoomFit">Fit</button>
      </div>
    </div>

    <!-- Body: 3-column -->
    <div class="fp-body">
      <!-- Left palette -->
      <aside class="fp-left">
        <div class="fp-pal-h">Tables</div>
        <div class="fp-pal-grid">
          <button v-for="(def, key) in TABLE_DEFS" :key="key" class="fp-pal-item" @click="addItem(key)">
            <span class="fp-pal-seats" :class="{ st: def.seats === 0 }">{{ def.seats || 'stand' }}</span>
            <div class="fp-pal-prev">
              <svg v-if="key === 'round'" viewBox="0 0 52 36"><circle cx="26" cy="18" r="14" fill="none" stroke="#a09488" stroke-width="1.2" /><circle v-for="i in 6" :key="i" :cx="26 + 14 * Math.cos((i/6)*Math.PI*2 - Math.PI/2)" :cy="18 + 10 * Math.sin((i/6)*Math.PI*2 - Math.PI/2)" r="2.5" fill="#d4cdc5" stroke="#a09488" stroke-width=".6" /></svg>
              <svg v-else-if="key === 'banquet'" viewBox="0 0 52 36"><rect x="8" y="11" width="36" height="14" rx="2" fill="none" stroke="#a09488" stroke-width="1.2" /><circle v-for="i in 4" :key="i" :cx="14 + (i-1)*10" cy="8" r="2.5" fill="#d4cdc5" stroke="#a09488" stroke-width=".6" /><circle v-for="i in 4" :key="'b'+i" :cx="14 + (i-1)*10" cy="28" r="2.5" fill="#d4cdc5" stroke="#a09488" stroke-width=".6" /></svg>
              <svg v-else-if="key === 'square'" viewBox="0 0 52 36"><rect x="16" y="8" width="20" height="20" rx="1.5" fill="none" stroke="#a09488" stroke-width="1.2" /><circle cx="26" cy="5" r="2.5" fill="#d4cdc5" stroke="#a09488" stroke-width=".6" /><circle cx="39" cy="18" r="2.5" fill="#d4cdc5" stroke="#a09488" stroke-width=".6" /><circle cx="26" cy="31" r="2.5" fill="#d4cdc5" stroke="#a09488" stroke-width=".6" /><circle cx="13" cy="18" r="2.5" fill="#d4cdc5" stroke="#a09488" stroke-width=".6" /></svg>
              <svg v-else-if="key === 'head'" viewBox="0 0 52 36"><rect x="4" y="10" width="44" height="10" rx="2" fill="none" stroke="#a09488" stroke-width="1.2" /><circle v-for="i in 5" :key="i" :cx="8 + (i-1)*9" cy="25" r="2.5" fill="#d4cdc5" stroke="#a09488" stroke-width=".6" /></svg>
              <svg v-else-if="key === 'sweetheart'" viewBox="0 0 52 36"><path d="M14 22 A12 12 0 0 1 38 22 L14 22Z" fill="none" stroke="#a09488" stroke-width="1.2" /><circle cx="21" cy="12" r="2.5" fill="#d4cdc5" stroke="#a09488" stroke-width=".6" /><circle cx="31" cy="12" r="2.5" fill="#d4cdc5" stroke="#a09488" stroke-width=".6" /></svg>
              <svg v-else-if="key === 'cocktail'" viewBox="0 0 52 36"><circle cx="26" cy="18" r="8" fill="none" stroke="#a09488" stroke-width="1.2" stroke-dasharray="2 2" /></svg>
            </div>
            <span class="fp-pal-name">{{ def.label }}</span>
          </button>
        </div>
        <div class="fp-pal-h">Floor Features</div>
        <div class="fp-pal-grid">
          <button v-for="(def, key) in FEATURE_DEFS" :key="key" class="fp-pal-item" @click="addItem(key)">
            <div class="fp-pal-prev">
              <svg v-if="key === 'stage'" viewBox="0 0 52 36"><rect x="6" y="8" width="40" height="20" rx="2" fill="#241d1b" /></svg>
              <svg v-else-if="key === 'dance'" viewBox="0 0 52 36"><rect x="6" y="4" width="40" height="28" rx="2" fill="none" stroke="#c4973a" stroke-width="1.5" /><line v-for="i in 4" :key="i" :x1="6 + i*10" :y1="4" :x2="6 + i*10 - 6" :y2="32" stroke="#c4973a" stroke-width=".6" opacity=".4" /></svg>
              <svg v-else-if="key === 'bar'" viewBox="0 0 52 36"><rect x="6" y="10" width="40" height="16" rx="2" fill="#f5ecd8" stroke="#c4973a" stroke-width="1.2" /></svg>
              <svg v-else-if="key === 'entrance'" viewBox="0 0 52 36"><line x1="10" y1="24" x2="42" y2="24" stroke="#6b5c53" stroke-width="2" /><path d="M10 24 Q10 10 26 10" fill="none" stroke="#a09488" stroke-width="1" stroke-dasharray="2 2" /></svg>
              <svg v-else-if="key === 'wall'" viewBox="0 0 52 36"><rect x="6" y="16" width="40" height="4" rx="1" fill="#6b5c53" /></svg>
              <svg v-else-if="key === 'pillar'" viewBox="0 0 52 36"><rect x="20" y="12" width="12" height="12" rx="1" fill="#e0d9d0" stroke="#a09488" stroke-width="1" /><line x1="20" y1="12" x2="32" y2="24" stroke="#a09488" stroke-width=".6" /><line x1="32" y1="12" x2="20" y2="24" stroke="#a09488" stroke-width=".6" /></svg>
            </div>
            <span class="fp-pal-name">{{ def.label }}</span>
          </button>
        </div>
        <div class="fp-pal-hint">
          <GmsIcon name="plus" :size="15" />
          <span>Drag any item onto the floor. Click to select, then edit on the right.</span>
        </div>
      </aside>

      <!-- Canvas -->
      <div class="fp-canvas-wrap" ref="canvasWrap" @wheel.prevent="onWheel" @mousedown="onCanvasMouseDown">
        <div class="fp-canvas" :style="{ cursor: isPanning ? 'grabbing' : 'grab' }">
          <div class="fp-stage-layer" :style="{ transform: stageTransform, transformOrigin: '0 0' }">
            <div class="fp-floor" :class="{ grid: showGrid }" :style="{ width: roomW + 'px', height: roomH + 'px', backgroundSize: GRID_SIZE + 'px ' + GRID_SIZE + 'px' }">

              <div v-for="item in items" :key="item.id"
                class="fp-item" :class="{ sel: selectedId === item.id }"
                :style="itemStyle(item)"
                @mousedown="onItemMouseDown($event, item)"
              >
                <!-- Selection ring -->
                <div v-if="selectedId === item.id" class="fp-sel-ring"></div>

                <!-- Rotate handle -->
                <div v-if="selectedId === item.id" class="fp-rot-wrap">
                  <div class="fp-rot-stem"></div>
                  <button class="fp-handle fp-rot" @mousedown="onRotateDown($event, item)" title="Rotate">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v4h4"/></svg>
                  </button>
                </div>

                <!-- Resize handle -->
                <button v-if="selectedId === item.id" class="fp-handle fp-resize" @mousedown="onResizeDown($event, item)" title="Resize"></button>

                <!-- TABLE BODIES -->
                <div v-if="item.type === 'round'"
                  class="fp-tablebody round" :style="{ width: item.w + 'px', height: item.h + 'px', borderRadius: '50%' }">
                  <div class="fp-tname"><b>{{ item.name }}</b><span class="fp-tseat">{{ occupancy(item) }}/{{ item.seats }}</span></div>
                </div>
                <div v-else-if="item.type === 'sweetheart'"
                  class="fp-tablebody sweetheart" :style="{ width: item.w + 'px', height: Math.round(item.h * 0.6) + 'px', marginTop: Math.round(item.h * 0.4) + 'px', borderRadius: item.w / 2 + 'px ' + item.w / 2 + 'px 4px 4px' }">
                  <div class="fp-tname"><b>{{ item.name }}</b><span class="fp-tseat">{{ occupancy(item) }}/{{ item.seats }}</span></div>
                </div>
                <div v-else-if="item.type === 'cocktail'"
                  class="fp-tablebody cocktail" :style="{ width: item.w + 'px', height: item.h + 'px', borderRadius: '50%', borderStyle: 'dashed' }">
                  <div class="fp-tname"><b>{{ item.name }}</b><em class="fp-stand">stand</em></div>
                </div>
                <div v-else-if="item.type === 'banquet' || item.type === 'head' || item.type === 'square'"
                  class="fp-tablebody rect" :style="{ width: item.w + 'px', height: item.h + 'px', borderRadius: '4px' }">
                  <div class="fp-tname"><b>{{ item.name }}</b><span class="fp-tseat">{{ occupancy(item) }}/{{ item.seats }}</span></div>
                </div>
                <!-- FEATURE BODIES -->
                <div v-else-if="item.type === 'stage'" class="fp-feat stage" :style="{ width: item.w + 'px', height: item.h + 'px', borderRadius: '4px' }"><span class="fp-flabel">{{ item.name }}</span></div>
                <div v-else-if="item.type === 'dance'" class="fp-feat dance" :style="{ width: item.w + 'px', height: item.h + 'px', borderRadius: '4px' }"><span class="fp-flabel">{{ item.name }}</span></div>
                <div v-else-if="item.type === 'bar'" class="fp-feat bar" :style="{ width: item.w + 'px', height: item.h + 'px', borderRadius: '4px' }"><span class="fp-flabel">{{ item.name }}</span></div>
                <div v-else-if="item.type === 'entrance'" class="fp-feat entrance" :style="{ width: item.w + 'px', height: item.h + 'px' }"><span class="fp-flabel">{{ item.name }}</span><div class="fp-door"></div></div>
                <div v-else-if="item.type === 'wall'" class="fp-feat wall" :style="{ width: item.w + 'px', height: item.h + 'px', borderRadius: '3px' }"></div>
                <div v-else-if="item.type === 'pillar'" class="fp-feat pillar" :style="{ width: item.w + 'px', height: item.h + 'px', borderRadius: '3px' }"></div>

                <!-- Chairs -->
                <template v-if="TABLE_DEFS[item.type] && item.seats > 0">
                  <button v-for="(pos, ci) in getChairPositions(item)" :key="ci"
                    class="fp-chair" :class="{ on: !!seatGuest(item, ci) }"
                    :style="chairStyle(pos)" :title="seatGuest(item, ci)?.name || 'Empty'"
                    @click="clickChair($event, item, ci)">
                    <template v-if="seatGuest(item, ci)">
                      <div class="fp-chair-avatar" :style="{ background: tierColors ? getTierColor(seatGuest(item, ci).tier) : 'var(--gms-maroon)' }">
                        <span class="fp-chair-lbl">{{ getInitials(seatGuest(item, ci).name) }}</span>
                      </div>
                    </template>
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>
        <div v-if="!items.length" class="fp-empty-overlay">
          <div class="fp-empty-card">
            <div class="fp-empty-t">Start your floor plan</div>
            <div class="fp-empty-s">Click a table type on the left, or load a sample layout.</div>
            <button class="gms-btn gms-btn-primary gms-btn-sm" @click="loadSample">Load sample</button>
          </div>
        </div>
      </div>

      <!-- Right panel -->
      <aside class="fp-right">
        <!-- Inspector (when item selected) -->
        <div v-if="selectedItem" class="fp-insp">
          <div class="fp-insp-h">
            <span class="fp-insp-kind">{{ itemLabel }}</span>
            <div class="fp-insp-acts">
              <button class="fp-insp-btn" @click="duplicateItem(selectedItem.id)" title="Duplicate"><GmsIcon name="copy" :size="14" /></button>
              <button class="fp-insp-btn" @click="deleteItem(selectedItem.id)" title="Delete"><GmsIcon name="trash" :size="14" /></button>
            </div>
          </div>
          <div class="fp-insp-body">
            <!-- Name -->
            <div class="fp-field">
              <label class="fp-label">Table name / number</label>
              <input v-model="selectedItem.name" class="fp-input" />
            </div>

            <!-- Chairs (tables only) -->
            <div v-if="isTable && selectedItem.type !== 'cocktail'" class="fp-field">
              <label class="fp-label">Chairs · 6–12</label>
              <div class="fp-seg">
                <button v-for="n in [6, 8, 10, 12]" :key="n" :class="{ on: selectedItem.seats === n }" @click="setSeats(selectedItem, n)">{{ n }}</button>
              </div>
            </div>

            <!-- Diameter (round tables) -->
            <div v-if="isCircularTable" class="fp-field">
              <label class="fp-label">Diameter · {{ inspDiameterFt }} ft</label>
              <input type="range" class="fp-slider" :min="2" :max="12" :step="0.1" :value="inspDiameterFt" @input="setDiameter(selectedItem, +$event.target.value)" />
            </div>

            <!-- Width / Height (rectangular) -->
            <template v-if="!isCircularTable">
              <div class="fp-field">
                <label class="fp-label">Width · {{ inspWidthFt }} ft</label>
                <input type="range" class="fp-slider" :min="1" :max="20" :step="0.1" :value="inspWidthFt" @input="setWidth(selectedItem, +$event.target.value)" />
              </div>
              <div class="fp-field">
                <label class="fp-label">Height · {{ inspHeightFt }} ft</label>
                <input type="range" class="fp-slider" :min="0.5" :max="12" :step="0.1" :value="inspHeightFt" @input="setHeight(selectedItem, +$event.target.value)" />
              </div>
            </template>

            <!-- Rotation -->
            <div class="fp-field">
              <label class="fp-label">Rotation</label>
              <div class="fp-rotrow">
                <button v-for="d in [0, 90, 180, 270]" :key="d" class="fp-rot-chip" :class="{ on: selectedItem.rotation === d }" @click="setRotation(selectedItem, d)">{{ d }}°</button>
                <button class="fp-rot-chip" @click="setRotation(selectedItem, selectedItem.rotation - 15)">−</button>
                <button class="fp-rot-chip" @click="setRotation(selectedItem, selectedItem.rotation + 15)">+</button>
              </div>
            </div>

            <!-- Tier label (tables only) -->
            <div v-if="isTable" class="fp-field">
              <label class="fp-label">VIP tier label</label>
              <div class="fp-tierpills">
                <button v-for="t in TIER_OPTIONS" :key="t" class="fp-tp" :class="{ on: (selectedItem.tierLabel || 'None') === t }" @click="selectedItem.tierLabel = t === 'None' ? '' : t">{{ t }}</button>
              </div>
            </div>

            <!-- Occupancy footer -->
            <div v-if="isTable" class="fp-insp-foot">
              <span class="fp-occ">{{ occupancy(selectedItem) }}/{{ selectedItem.seats }} seated</span>
            </div>
          </div>
        </div>

        <!-- Guest roster -->
        <div class="fp-roster">
          <div class="fp-roster-h">
            <div class="fp-roster-t">
              <span>Guests</span>
              <span class="fp-roster-n">{{ seatedCount }}/{{ guests.length }} seated</span>
            </div>
            <div class="fp-roster-seg">
              <button :class="{ on: guestFilter === 'all' }" @click="guestFilter = 'all'">All</button>
              <button :class="{ on: guestFilter === 'unseated' }" @click="guestFilter = 'unseated'">Unseated</button>
            </div>
            <div class="fp-search-wrap">
              <GmsIcon name="search" :size="14" />
              <input v-model="guestSearch" placeholder="Search guests..." class="fp-search" />
            </div>
          </div>
          <div class="fp-roster-list">
            <button v-for="g in filteredGuests" :key="g.id"
              class="fp-gitem" :class="{ seated: !!seatedIds[g.id], on: selGuest?.id === g.id }"
              @click="selGuest = selGuest?.id === g.id ? null : g">
              <GmsAvatar :name="g.name" size="sm" />
              <div class="fp-gmeta">
                <div class="fp-gname">{{ g.name }}</div>
                <div class="fp-gsub"><span class="fp-gdot" :style="{ background: getTierColor(g.tier) }"></span>{{ g.tier }} · {{ g.group_id }}</div>
              </div>
              <span v-if="seatedIds[g.id]" class="fp-gat">{{ seatedIds[g.id] }}</span>
              <span class="fp-ggrip"><svg width="8" height="14" viewBox="0 0 8 14" fill="currentColor"><circle cx="2" cy="2" r="1.2"/><circle cx="6" cy="2" r="1.2"/><circle cx="2" cy="7" r="1.2"/><circle cx="6" cy="7" r="1.2"/><circle cx="2" cy="12" r="1.2"/><circle cx="6" cy="12" r="1.2"/></svg></span>
            </button>
            <div v-if="!filteredGuests.length" class="fp-no-guests">
              <template v-if="guestSearch">No guests matching "{{ guestSearch }}"</template>
              <template v-else-if="guestFilter === 'unseated'">Everyone is seated</template>
              <template v-else>No guests available</template>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<style scoped>
/* ── Header ── */
.fp-ehead { flex:none; display:flex; align-items:flex-end; gap:20px; padding:18px 24px 16px; background:var(--gms-surface); border-bottom:1px solid var(--gms-border) }
.fp-etitle h1 { font-family:var(--gms-font-display); font-weight:400; font-size:30px; line-height:1; margin:0; letter-spacing:.3px }
.fp-etitle .sub { color:var(--gms-text-2); font-size:12.5px; margin-top:5px }
.fp-estats { display:flex; align-items:center; gap:18px; margin-left:6px; padding-bottom:2px }
.fp-estats span { font-size:12px; color:var(--gms-text-3) }
.fp-estats b { font-family:var(--gms-font-display); font-weight:400; font-size:22px; color:var(--gms-text); margin-right:5px }
.fp-eactions { margin-left:auto; display:flex; align-items:center; gap:8px; padding-bottom:2px }

/* ── Toolbar ── */
.fp-toolbar { height:50px; flex:none; display:flex; align-items:center; gap:16px; padding:0 18px; background:var(--gms-surface); border-bottom:1px solid var(--gms-border) }
.fp-tg { display:flex; gap:4px; background:var(--gms-bg); border:1px solid var(--gms-border); border-radius:10px; padding:3px }
.fp-tt { padding:5px 12px; border-radius:7px; font-size:12.5px; font-weight:600; color:var(--gms-text-2); background:none; border:none; cursor:pointer; transition:.12s }
.fp-tt:hover { color:var(--gms-text) }
.fp-tt.on { background:var(--gms-maroon); color:#fff; box-shadow:0 1px 4px rgba(138,31,61,.4) }
.fp-room { display:flex; align-items:center; gap:9px }
.fp-room-l { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--gms-text-3) }
.fp-room-v { font-size:11.5px; color:var(--gms-text-2); font-family:var(--gms-font-mono); min-width:42px }
.fp-room-x { color:var(--gms-text-3) }
.fp-save { font-size:12px; color:var(--gms-text-3) }
.fp-slider { -webkit-appearance:none; appearance:none; width:100%; height:5px; border-radius:5px; background:var(--gms-bg); outline:none; cursor:pointer }
.fp-slider.sm { width:104px }
.fp-slider::-webkit-slider-thumb { -webkit-appearance:none; width:16px; height:16px; border-radius:50%; background:var(--gms-maroon); border:2px solid var(--gms-surface); box-shadow:0 1px 3px rgba(0,0,0,.18); cursor:grab }
.fp-slider::-moz-range-thumb { width:14px; height:14px; border-radius:50%; background:var(--gms-maroon); border:2px solid var(--gms-surface); cursor:grab }
.fp-zoom-btn { width:30px; height:30px; border-radius:8px; border:1px solid var(--gms-border); background:var(--gms-surface); display:grid; place-items:center; cursor:pointer; color:var(--gms-text-2); transition:.12s }
.fp-zoom-btn:hover { border-color:var(--gms-maroon); color:var(--gms-maroon) }
.fp-zoom-v { font-size:12px; font-family:var(--gms-font-mono); color:var(--gms-text-2); min-width:36px; text-align:center }
.fp-zoom-fit { padding:5px 12px; border-radius:8px; border:1px solid var(--gms-border); background:var(--gms-surface); font-size:12px; font-weight:600; color:var(--gms-text-2); cursor:pointer; transition:.12s }
.fp-zoom-fit:hover { border-color:var(--gms-maroon); color:var(--gms-maroon) }

/* ── Body 3-column ── */
.fp-body { flex:1; display:grid; grid-template-columns:212px minmax(0,1fr) 326px; min-height:0; overflow:hidden }

/* ── Left palette ── */
.fp-left { background:var(--gms-surface); border-right:1px solid var(--gms-border); overflow-y:auto; padding:14px 13px 22px }
.fp-pal-h { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--gms-text-3); padding:6px 4px 9px }
.fp-pal-h:not(:first-child) { margin-top:10px; border-top:1px solid var(--gms-border); padding-top:14px }
.fp-pal-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px }
.fp-pal-item { position:relative; display:flex; flex-direction:column; align-items:center; gap:5px; padding:9px 6px 8px; border:1px solid var(--gms-border); border-radius:11px; background:var(--gms-surface); cursor:pointer; transition:.12s; user-select:none }
.fp-pal-item:hover { border-color:var(--gms-maroon); background:rgba(138,31,61,.04); box-shadow:0 1px 4px rgba(0,0,0,.06); transform:translateY(-1px) }
.fp-pal-prev { width:52px; height:36px; display:grid; place-items:center; pointer-events:none }
.fp-pal-prev svg { width:52px; height:36px }
.fp-pal-name { font-size:11px; font-weight:600; color:var(--gms-text-2); line-height:1; pointer-events:none }
.fp-pal-seats { position:absolute; top:5px; right:6px; font-size:9.5px; font-weight:700; color:var(--gms-maroon); background:rgba(138,31,61,.08); border-radius:6px; padding:0 5px; line-height:15px; min-width:15px; text-align:center }
.fp-pal-seats.st { font-size:8px; letter-spacing:.3px; color:var(--gms-text-3); background:var(--gms-bg) }
.fp-pal-hint { display:flex; gap:8px; align-items:flex-start; margin-top:16px; padding:11px; border-radius:10px; background:var(--gms-bg); color:var(--gms-text-3); font-size:11.5px; line-height:1.45 }
.fp-pal-hint svg { flex:none; margin-top:1px; color:var(--gms-maroon) }

/* ── Canvas ── */
.fp-canvas-wrap { position:relative; overflow:hidden; background:radial-gradient(circle at 50% 0,#e9e2d4,#ddd4c4); min-width:0 }
.fp-canvas { position:absolute; inset:0; overflow:hidden; touch-action:none }
.fp-stage-layer { position:absolute; left:0; top:0; will-change:transform }
.fp-floor { position:relative; background:var(--gms-surface); border:1px solid rgba(38,34,30,.12); border-radius:6px; box-shadow:0 20px 60px -24px rgba(38,34,30,.45),0 0 0 1px rgba(38,34,30,.03) }
.fp-floor.grid { background-image:linear-gradient(rgba(38,34,30,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(38,34,30,.045) 1px,transparent 1px) }

/* ── Items ── */
.fp-item { position:absolute; will-change:transform; cursor:grab }
.fp-item:active { cursor:grabbing }
.fp-item.sel { z-index:5 }

/* Selection ring */
.fp-sel-ring { position:absolute; inset:-8px; border:1.5px dashed var(--gms-maroon); border-radius:9px; pointer-events:none; opacity:.75; z-index:1 }

/* Rotate handle */
.fp-rot-wrap { position:absolute; left:50%; top:-10px; transform:translateX(-50%); display:flex; flex-direction:column; align-items:center; z-index:8 }
.fp-rot-stem { width:1.5px; height:22px; background:var(--gms-maroon); opacity:.55 }
.fp-handle { background:var(--gms-surface); border:1.5px solid var(--gms-maroon); display:grid; place-items:center; cursor:pointer; z-index:8; box-shadow:0 1px 3px rgba(0,0,0,.12); touch-action:none }
.fp-rot { width:23px; height:23px; border-radius:50%; color:var(--gms-maroon) }
.fp-rot:hover { background:var(--gms-maroon); color:#fff }

/* Resize handle */
.fp-resize { position:absolute; right:-6px; bottom:-6px; width:13px; height:13px; border-radius:3px; cursor:nwse-resize; background:var(--gms-maroon); border-color:var(--gms-maroon); z-index:8 }
.fp-resize:hover { background:#6e1830 }

/* Table body */
.fp-tablebody { position:relative; border:1.5px solid rgba(38,34,30,.14); background:rgba(245,240,235,.85); box-shadow:0 2px 7px rgba(38,34,30,.13); display:grid; place-items:center; z-index:2 }
.sel .fp-tablebody { border-color:var(--gms-maroon); box-shadow:0 0 0 2px rgba(138,31,61,.15),0 2px 7px rgba(38,34,30,.13) }
.fp-tname { display:flex; flex-direction:column; align-items:center; gap:1px; text-align:center; line-height:1; pointer-events:none }
.fp-tname b { font-size:12px; color:var(--gms-text); font-weight:700; letter-spacing:.2px }
.fp-tseat { font-size:8.5px; color:var(--gms-text-3); font-weight:700; font-variant-numeric:tabular-nums }
.fp-stand { font-size:7.5px; color:var(--gms-text-3); text-transform:uppercase; letter-spacing:.6px; font-style:normal }
.fp-tablebody.cocktail .fp-tname b { font-size:10px }

/* ── Chairs ── */
.fp-chair { position:absolute; left:50%; top:50%; width:19px; height:19px; border-radius:5px 5px 6px 6px; border:1.5px solid rgba(38,34,30,.14); background:var(--gms-surface); display:grid; place-items:center; box-shadow:0 1px 2px rgba(38,34,30,.12); z-index:3; transition:border-color .1s,box-shadow .1s; cursor:pointer; padding:0 }
.fp-chair::before { content:""; position:absolute; top:-3px; left:3px; right:3px; height:3px; border-radius:3px; background:rgba(38,34,30,.14) }
.fp-chair:hover { border-color:var(--gms-maroon); box-shadow:0 0 0 2px rgba(138,31,61,.12); z-index:4 }
.fp-chair.on { border-color:transparent; background:transparent; box-shadow:none }
.fp-chair.on::before { display:none }
.fp-chair-avatar { width:22px; height:22px; border-radius:50%; display:grid; place-items:center; box-shadow:0 1px 3px rgba(38,34,30,.28) }
.fp-chair-lbl { font-size:7px; font-weight:800; letter-spacing:.2px; line-height:1; color:#fff; pointer-events:none }

/* ── Features ── */
.fp-feat { position:relative; display:grid; place-items:center; z-index:2 }
.sel .fp-feat { outline:2px solid var(--gms-maroon); outline-offset:2px }
.fp-flabel { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; pointer-events:none }
.fp-feat.stage { background:#241d1b; color:#e9e2d6; border:1.5px solid #14100e; box-shadow:inset 0 -3px 0 rgba(0,0,0,.25),0 2px 8px rgba(0,0,0,.2) }
.fp-feat.dance { border:2px solid var(--gms-gold); color:#8a6a2a; background:repeating-linear-gradient(45deg,#f3ecdd 0 9px,#efe6d2 9px 18px) }
.fp-feat.bar { background:rgba(196,151,58,.12); border:1.5px solid var(--gms-gold); color:#8a6a2a }
.fp-feat.entrance { background:transparent; border:none; box-shadow:none; color:var(--gms-text-3); border-bottom:3px solid var(--gms-text-2) }
.fp-feat.entrance .fp-flabel { font-size:8.5px }
.fp-door { position:absolute; left:0; bottom:0; width:100%; height:170%; border:1.4px dashed var(--gms-text-3); border-radius:0 0 100% 0; border-top:none; border-left:none; opacity:.5; pointer-events:none }
.fp-feat.wall { background:var(--gms-text-2); border:none; box-shadow:none; color:transparent }
.fp-feat.pillar { background:#e8e2da; border:1.5px solid var(--gms-text-3); color:var(--gms-text-3); background-image:linear-gradient(45deg,transparent 46%,var(--gms-text-3) 46% 54%,transparent 54%),linear-gradient(-45deg,transparent 46%,var(--gms-text-3) 46% 54%,transparent 54%) }
.fp-feat.pillar .fp-flabel { display:none }

/* Empty state */
.fp-empty-overlay { position:absolute; inset:0; display:grid; place-items:center; pointer-events:none; z-index:10 }
.fp-empty-card { background:var(--gms-surface); border:1px solid var(--gms-border); border-radius:16px; box-shadow:0 8px 32px rgba(0,0,0,.12); padding:26px 30px; text-align:center; pointer-events:auto }
.fp-empty-t { font-family:var(--gms-font-display); font-size:24px; color:var(--gms-text) }
.fp-empty-s { font-size:13px; color:var(--gms-text-2); margin:6px 0 14px }

/* ── Right panel ── */
.fp-right { background:var(--gms-bg); border-left:1px solid var(--gms-border); display:flex; flex-direction:column; gap:0; min-height:0; overflow:hidden }

/* ── Inspector ── */
.fp-insp { flex:none; display:flex; flex-direction:column; overflow:hidden; background:var(--gms-surface); border-bottom:1px solid var(--gms-border) }
.fp-insp-h { display:flex; align-items:center; gap:10px; padding:12px 14px; border-bottom:1px solid var(--gms-border) }
.fp-insp-kind { font-size:12.5px; font-weight:700; color:var(--gms-text); text-transform:capitalize }
.fp-insp-acts { margin-left:auto; display:flex; gap:2px }
.fp-insp-btn { width:30px; height:30px; border-radius:8px; border:1px solid var(--gms-border); background:var(--gms-surface); display:grid; place-items:center; cursor:pointer; color:var(--gms-text-3); transition:.12s }
.fp-insp-btn:hover { color:var(--gms-maroon); border-color:var(--gms-maroon) }
.fp-insp-body { padding:14px; overflow-y:auto; max-height:46vh }

.fp-field { margin-bottom:13px }
.fp-label { display:block; font-size:11.5px; font-weight:600; color:var(--gms-text-2); margin-bottom:6px }
.fp-input { width:100%; padding:8px 11px; border:1px solid var(--gms-border); border-radius:8px; font:inherit; font-size:13px; color:var(--gms-text); background:var(--gms-surface); outline:none; transition:.12s }
.fp-input:focus { border-color:var(--gms-maroon) }

.fp-seg { display:flex; gap:0; background:var(--gms-bg); border:1px solid var(--gms-border); border-radius:10px; padding:3px }
.fp-seg button { flex:1; padding:6px 10px; border-radius:7px; font-size:12.5px; font-weight:600; color:var(--gms-text-2); background:none; border:none; cursor:pointer; transition:.12s }
.fp-seg button.on { background:var(--gms-surface); color:var(--gms-text); box-shadow:0 1px 3px rgba(0,0,0,.08) }

.fp-rotrow { display:flex; gap:5px; flex-wrap:wrap }
.fp-rot-chip { padding:5px 9px; min-width:34px; text-align:center; border-radius:8px; border:1px solid var(--gms-border); background:var(--gms-surface); font-size:12px; font-weight:600; color:var(--gms-text-2); cursor:pointer; transition:.12s }
.fp-rot-chip.on { background:var(--gms-maroon); color:#fff; border-color:var(--gms-maroon) }
.fp-rot-chip:hover:not(.on) { border-color:var(--gms-text-3); color:var(--gms-text) }

.fp-tierpills { display:flex; gap:5px; flex-wrap:wrap }
.fp-tp { font-size:11px; font-weight:700; letter-spacing:.3px; padding:5px 10px; border-radius:8px; border:1px solid var(--gms-border); background:var(--gms-surface); color:var(--gms-text-2); cursor:pointer; transition:.12s }
.fp-tp:hover { border-color:var(--gms-text-3); color:var(--gms-text) }
.fp-tp.on { border-color:var(--gms-text); background:var(--gms-text); color:#fff }

.fp-insp-foot { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:4px; padding-top:13px; border-top:1px solid var(--gms-border) }
.fp-occ { font-size:12px; font-weight:600; color:var(--gms-maroon); font-variant-numeric:tabular-nums }

/* ── Guest roster ── */
.fp-roster { flex:1; display:flex; flex-direction:column; min-height:0; overflow:hidden }
.fp-roster-h { padding:13px 14px 11px; border-bottom:1px solid var(--gms-border); display:flex; flex-direction:column; gap:9px }
.fp-roster-t { font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px }
.fp-roster-n { font-size:11px; font-weight:600; color:var(--gms-text-3); font-variant-numeric:tabular-nums }
.fp-roster-seg { display:flex; gap:0; background:var(--gms-surface); border:1px solid var(--gms-border); border-radius:10px; padding:3px; width:100% }
.fp-roster-seg button { flex:1; padding:5px 12px; border-radius:7px; font-size:12.5px; font-weight:600; color:var(--gms-text-2); background:none; border:none; cursor:pointer; transition:.12s }
.fp-roster-seg button.on { background:var(--gms-surface); color:var(--gms-text); box-shadow:0 1px 3px rgba(0,0,0,.08) }
.fp-search-wrap { display:flex; align-items:center; gap:8px; background:var(--gms-surface); border:1px solid var(--gms-border); border-radius:8px; padding:6px 10px; color:var(--gms-text-3) }
.fp-search { flex:1; border:none; background:none; font:inherit; font-size:12.5px; color:var(--gms-text); outline:none }
.fp-search::placeholder { color:var(--gms-text-3) }
.fp-roster-list { flex:1; overflow-y:auto; padding:6px }
.fp-gitem { display:flex; align-items:center; gap:10px; padding:7px 8px; border-radius:10px; cursor:pointer; transition:.1s; border:1px solid transparent; user-select:none; background:none; text-align:left; width:100% }
.fp-gitem:hover { background:var(--gms-surface); border-color:var(--gms-border); box-shadow:0 1px 3px rgba(0,0,0,.05) }
.fp-gitem.on { background:rgba(138,31,61,.06); border-color:var(--gms-maroon) }
.fp-gitem.seated { opacity:.6 }
.fp-gitem.seated:hover { opacity:1 }
.fp-gmeta { flex:1; min-width:0 }
.fp-gname { font-size:12.5px; font-weight:600; line-height:1.2; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--gms-text) }
.fp-gsub { font-size:10.5px; color:var(--gms-text-3); display:flex; align-items:center; gap:5px; margin-top:1px }
.fp-gdot { width:7px; height:7px; border-radius:50%; flex:none }
.fp-ggrip { color:var(--gms-text-3); flex:none; opacity:.5 }
.fp-gitem:hover .fp-ggrip { opacity:1 }
.fp-gat { flex:none; font-size:10px; font-weight:700; color:var(--gms-maroon); background:rgba(138,31,61,.08); border-radius:7px; padding:3px 7px; max-width:64px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
.fp-no-guests { padding:20px 14px; color:var(--gms-text-3); font-size:13px; text-align:center }

/* ── View overrides ── */
.fp-view { display:flex !important; flex-direction:column !important; height:calc(100vh - var(--gms-topbar-h)) !important; overflow:hidden !important; padding:0 !important }
</style>
