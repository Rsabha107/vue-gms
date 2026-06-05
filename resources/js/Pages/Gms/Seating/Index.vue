<script setup>
import { ref, computed, inject, nextTick } from 'vue'
import axios from 'axios'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'
import GmsPill from '@/Components/Gms/GmsPill.vue'
import GmsModal from '@/Components/Gms/GmsModal.vue'
import GmsSeatingConfigurator from '@/Components/Gms/GmsSeatingConfigurator.vue'

defineOptions({ layout: GmsLayout, inheritAttrs: false })

const props = defineProps({
    matches:    { type: Array,  default: () => [] },
    templates:  { type: Array,  default: () => [] },
    matchSeeds: { type: Object, default: () => ({}) },
    matchSeats: { type: Object, default: () => ({}) },
    venues:     { type: Array,  default: () => [] },
    guests:     { type: Array,  default: () => [] },
    tiers:      { type: Array,  default: () => [] },
    event:      { type: Object, default: () => ({}) },
})

const toast = inject('toast')

// ── Organisations ────────────────────────────────────────────────
const ORGS = [
    { code: 'LOC',      name: 'Local Organising Committee', color: '#3a6a8a' },
    { code: 'MOI',      name: 'Ministry of Interior',       color: '#3f7d52' },
    { code: 'QFA',      name: 'Qatar Football Association', color: '#a9844a' },
    { code: 'AFC',      name: 'Asian Football Confed.',     color: '#7a5a8a' },
    { code: 'PROTOCOL', name: 'Protocol Office',            color: '#b06038' },
    { code: 'MEDIA',    name: 'Media & Broadcast',          color: '#566b8a' },
]
const orgOf    = code => ORGS.find(o => o.code === code)
const orgColor = code => { const o = orgOf(code); return o ? o.color : 'var(--gms-gold)' }

// ── View state machine: 'list' | 'map' ───────────────────────────
const view        = ref('list')
const activeMatch = ref(null)

// ── Local mutable copies ──────────────────────────────────────────
const localMatches = ref(props.matches.map(m => ({ ...m })))
const seatState    = ref({ ...props.matchSeats })

// ── Map state ─────────────────────────────────────────────────────
const mapTab    = ref('map')   // 'map' | 'planner' | 'list'
const mode      = ref('assign') // 'assign' | 'reserve' | 'swap' | 'hide'
const zoom      = ref(1)
const Z_MIN     = 0.6
const Z_MAX     = 1.6
const fBlock    = ref('all')
const fStatus   = ref('all')
const gq        = ref('')  // guest panel search
const pq        = ref('')  // planner highlight search

// ── Floating guest panel ─────────────────────────────────────────
const floatPanel = ref(false)
const panelPos   = ref(null)  // null = default position, {x,y} = dragged

// ── Interaction state ────────────────────────────────────────────
const pickGuest  = ref(null)  // guestId selected for assignment
const pendingSeat= ref(null)  // seatId waiting for guest click
const swapFirst  = ref(null)  // first seat in swap mode
const resSel     = ref([])    // seat ids selected for bulk-reserve
const resOrg     = ref('LOC')
const resCustom  = ref('')

// ── Hover popover ─────────────────────────────────────────────────
const hover = ref(null)  // {id, rect, below}
let hoverTimer = null

// ── Pinned contact card ───────────────────────────────────────────
const pinSeat = ref(null)
const pinPos  = ref({ x: 0, y: 0 })

// ── Flash animation ───────────────────────────────────────────────
const flash = ref(null)
let flashTimer = null

// ── Template picker ───────────────────────────────────────────────
const templateModal = ref(false)
const chosenTplId   = ref('')

// ── Navigation ────────────────────────────────────────────────────
function openMatch(m) {
    activeMatch.value  = m
    mapTab.value       = 'map'
    mode.value         = 'assign'
    fBlock.value       = 'all'
    fStatus.value      = 'all'
    zoom.value         = 1
    pickGuest.value    = null
    pendingSeat.value  = null
    swapFirst.value    = null
    resSel.value       = []
    floatPanel.value   = false
    panelPos.value     = null
    hover.value        = null
    pinSeat.value      = null
    view.value         = 'map'
}

function backToList() {
    view.value        = 'list'
    activeMatch.value = null
    hover.value       = null
    pinSeat.value     = null
}

function openTemplatePicker(m) {
    activeMatch.value   = m
    chosenTplId.value   = props.templates[0]?.id ?? ''
    templateModal.value = true
}

// ── Derived seats ─────────────────────────────────────────────────
const activeSeats = computed(() => {
    if (!activeMatch.value) return []
    return seatState.value[activeMatch.value.id] ?? []
})

const seatLookup = computed(() => {
    const m = {}
    for (const s of activeSeats.value) m[s.id] = s
    return m
})
const seatById = id => seatLookup.value[id]

// ── Seat blocks (ordered arrays for rendering) ────────────────────
const seatBlocks = computed(() => {
    const tpl = localTemplates.value.find(t => t.id === props.matchSeeds[activeMatch.value?.id])
    const blocks = {}

    if (tpl) {
        for (const b of tpl.blocks) {
            const rowDefs = {}
            const rowOrder = []
            for (const row of (b.rows || [])) {
                rowOrder.push(String(row.label))
                rowDefs[String(row.label)] = {
                    aisles:  row.aisles  || [],
                    walkway: row.walkway || false,
                    seats:   row.seats   || 0,
                }
            }
            blocks[b.id] = {
                id: b.id, label: b.label, tier: b.tier || '', color: b.color,
                rows: {}, rowOrder, rowDefs,
            }
        }
    }

    for (const seat of activeSeats.value) {
        if (!blocks[seat.block]) {
            blocks[seat.block] = {
                id: seat.block, label: seat.blockLabel, tier: seat.blockTier || '',
                color: seat.blockColor, rows: {}, rowOrder: [], rowDefs: {},
            }
        }
        if (!blocks[seat.block].rows[seat.row]) {
            blocks[seat.block].rows[seat.row] = []
            if (!blocks[seat.block].rowOrder.includes(seat.row)) blocks[seat.block].rowOrder.push(seat.row)
        }
        blocks[seat.block].rows[seat.row].push(seat)
    }

    return Object.values(blocks).map(b => ({
        ...b,
        rowsArray: b.rowOrder.map(rl => ({
            label:   rl,
            seats:   (b.rows[rl] || []).sort((a, z) => a.col - z.col),
            aisles:  (b.rowDefs[rl] || {}).aisles  || [],
            walkway: (b.rowDefs[rl] || {}).walkway || false,
        })),
    }))
})

const visibleBlocks = computed(() =>
    fBlock.value === 'all' ? seatBlocks.value : seatBlocks.value.filter(b => b.id === fBlock.value)
)

// ── Stats ─────────────────────────────────────────────────────────
const seatStats = computed(() => {
    const all = activeSeats.value
    const vis = all.filter(s => !s.hidden)
    return {
        total:    vis.length,
        assigned: vis.filter(s => s.status === 'assigned' || s.status === 'ticket').length,
        reserved: vis.filter(s => s.status === 'reserved').length,
        available:vis.filter(s => s.status === 'available').length,
        ticket:   vis.filter(s => s.status === 'ticket').length,
        hidden:   all.filter(s => s.hidden).length,
    }
})

// ── Seat filter ───────────────────────────────────────────────────
function matchesFilt(s) {
    if (fBlock.value !== 'all' && s.block !== fBlock.value) return false
    if (fStatus.value === 'unassigned') return s.status === 'available' || s.status === 'reserved'
    if (fStatus.value === 'assigned')   return s.status === 'assigned'  || s.status === 'ticket'
    return true
}

// ── Mutations ─────────────────────────────────────────────────────
function mutateSeat(seatId, update) {
    const seats = seatState.value[activeMatch.value.id]
    if (!seats) return
    const idx = seats.findIndex(s => s.id === seatId)
    if (idx === -1) return
    seats[idx] = { ...seats[idx], ...update }
    seatState.value = { ...seatState.value }
    axios.post(`/gms/seating/${activeMatch.value.id}/seats/${seatId}`, { ...update, _method: 'POST' }).catch(() => {})
}

function assignSeat(seatId, guestId)    { mutateSeat(seatId, { status: 'assigned', guestId, resLabel: null }) }
function unassignSeat(seatId)           { mutateSeat(seatId, { status: 'available', guestId: null, resLabel: null }) }
function issueTicket(seatId)            { mutateSeat(seatId, { status: 'ticket' }) }
function revokeTicket(seatId)           { mutateSeat(seatId, { status: 'assigned' }) }
function reserveSeats(ids, label)       { ids.forEach(id => mutateSeat(id, { status: 'reserved', resLabel: label, guestId: null })) }
function releaseSeats(ids)              { ids.forEach(id => mutateSeat(id, { status: 'available', resLabel: null, guestId: null })) }
function setSeatsHidden(ids, hidden)    { ids.forEach(id => mutateSeat(id, { hidden })) }

function swapSeats(sid1, sid2) {
    const s1 = seatById(sid1), s2 = seatById(sid2)
    if (!s1 || !s2) return
    const g1 = s1.guestId, st1 = s1.status
    const g2 = s2.guestId, st2 = s2.status
    mutateSeat(sid1, { guestId: g2, status: st2 })
    mutateSeat(sid2, { guestId: g1, status: st1 })
}

// ── Mode switching ────────────────────────────────────────────────
function switchMode(m) {
    mode.value      = mode.value === m ? 'assign' : m
    swapFirst.value = null
    pickGuest.value = null
    pendingSeat.value = null
    resSel.value    = []
    hover.value     = null
}

// ── Zoom ──────────────────────────────────────────────────────────
const zoomBy = d => { zoom.value = Math.min(Z_MAX, Math.max(Z_MIN, Math.round((zoom.value + d) * 10) / 10)) }

// ── Seat interaction ──────────────────────────────────────────────
function seatClick(seat, event) {
    if (seat.hidden && mode.value !== 'hide') return

    if (mode.value === 'hide') {
        setSeatsHidden([seat.id], !seat.hidden)
        toast(seat.hidden ? `${seat.id} restored` : `${seat.id} hidden`)
        return
    }

    if (mode.value === 'swap') {
        if (seat.status !== 'assigned' && seat.status !== 'ticket') { toast('Pick two assigned seats to swap'); return }
        if (!swapFirst.value)               { swapFirst.value = seat.id; return }
        if (swapFirst.value === seat.id)    { swapFirst.value = null; return }
        swapSeats(swapFirst.value, seat.id)
        toast('Seats swapped')
        swapFirst.value = null
        return
    }

    if (mode.value === 'reserve') {
        if (seat.status === 'assigned' || seat.status === 'ticket') { toast('That seat is already assigned to a guest'); return }
        resSel.value = resSel.value.includes(seat.id)
            ? resSel.value.filter(x => x !== seat.id)
            : [...resSel.value, seat.id]
        return
    }

    // assign mode
    if (seat.status === 'assigned' || seat.status === 'ticket') {
        hover.value = null
        if (pinSeat.value === seat.id) { pinSeat.value = null; return }
        const r = event?.currentTarget?.getBoundingClientRect()
        if (r) {
            pinPos.value = {
                x: Math.min(r.left, window.innerWidth - 296),
                y: Math.min(r.bottom + 10, window.innerHeight - 240),
            }
        }
        pinSeat.value = seat.id
        return
    }

    if (seat.status === 'available' || seat.status === 'reserved') {
        if (pickGuest.value) {
            const g = guestById(pickGuest.value)
            assignSeat(seat.id, pickGuest.value)
            toast(`${g?.name?.split(' ').slice(-1)} → ${seat.id}`)
            pickGuest.value  = null
            pendingSeat.value = null
        } else {
            pendingSeat.value = pendingSeat.value === seat.id ? null : seat.id
        }
    }
}

function guestClick(g) {
    if (pendingSeat.value) {
        assignSeat(pendingSeat.value, g.id)
        toast(`${g.name.split(' ').slice(-1)} → ${pendingSeat.value}`)
        pendingSeat.value = null
    } else {
        pickGuest.value = pickGuest.value === g.id ? null : g.id
    }
}

// ── Hover popover ─────────────────────────────────────────────────
function seatEnter(seat, event) {
    if (mode.value !== 'assign' || seat.hidden) return
    clearTimeout(hoverTimer)
    const r = event.currentTarget.getBoundingClientRect()
    hover.value = { id: seat.id, rect: { left: r.left, top: r.top, bottom: r.bottom, width: r.width }, below: r.top < 210 }
}
function seatLeave() { clearTimeout(hoverTimer); hoverTimer = setTimeout(() => hover.value = null, 130) }
function popKeep()   { clearTimeout(hoverTimer) }
function popLeave()  { clearTimeout(hoverTimer); hoverTimer = setTimeout(() => hover.value = null, 130) }

// ── Bulk reserve ──────────────────────────────────────────────────
const resLabel = computed(() => (resCustom.value.trim() || resOrg.value).toUpperCase())

function doReserve() {
    if (!resSel.value.length) return
    reserveSeats(resSel.value, resLabel.value)
    toast(`${resSel.value.length} seat${resSel.value.length > 1 ? 's' : ''} reserved for ${resLabel.value}`)
    resSel.value = []
    resCustom.value = ''
}
function doRelease() {
    if (!resSel.value.length) return
    releaseSeats(resSel.value)
    toast(`${resSel.value.length} seat${resSel.value.length > 1 ? 's' : ''} released`)
    resSel.value = []
}

// ── Reservation summary ───────────────────────────────────────────
const resCounts = computed(() => {
    const m = {}
    for (const s of activeSeats.value) {
        if (s.status === 'reserved' && s.resLabel) m[s.resLabel] = (m[s.resLabel] || 0) + 1
    }
    return m
})

// ── Locate seat from list → map ───────────────────────────────────
function locateSeat(seatId) {
    mapTab.value  = 'map'
    fBlock.value  = 'all'
    pinSeat.value = null
    hover.value   = null
    flash.value   = seatId
    nextTick(() => {
        const el  = document.querySelector(`[data-seat="${seatId}"]`)
        const box = document.querySelector('.gms-smap')
        if (el && box) {
            const er = el.getBoundingClientRect(), br = box.getBoundingClientRect()
            box.scrollLeft += (er.left - br.left) - box.clientWidth / 2 + er.width / 2
        }
        el?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
    })
    clearTimeout(flashTimer)
    flashTimer = setTimeout(() => flash.value = null, 1700)
}

// ── Panel drag ────────────────────────────────────────────────────
function startPanelDrag(e) {
    const panel = e.currentTarget.closest('.gms-gpanel')
    if (!panel) return
    const r = panel.getBoundingClientRect()
    const offX = e.clientX - r.left, offY = e.clientY - r.top
    const move = ev => panelPos.value = { x: Math.max(8, ev.clientX - offX), y: Math.max(72, ev.clientY - offY) }
    const up   = () => { window.removeEventListener('mousemove', move); window.removeEventListener('mouseup', up) }
    window.addEventListener('mousemove', move)
    window.addEventListener('mouseup', up)
    e.preventDefault()
}

function startPopDrag(e) {
    const offX = e.clientX - pinPos.value.x, offY = e.clientY - pinPos.value.y
    const move = ev => pinPos.value = { x: Math.max(8, Math.min(ev.clientX - offX, window.innerWidth - 272)), y: Math.max(64, ev.clientY - offY) }
    const up   = () => { window.removeEventListener('mousemove', move); window.removeEventListener('mouseup', up) }
    window.addEventListener('mousemove', move)
    window.addEventListener('mouseup', up)
    e.preventDefault()
}

// ── Local templates (editable copy) ──────────────────────────────
const localTemplates = ref(props.templates.map(t => JSON.parse(JSON.stringify(t))))

// ── Configurator state ────────────────────────────────────────────
const configuring     = ref(false)   // true = show configurator
const configIsNew     = ref(false)
const configTplId     = ref(null)    // templateId being edited (null = new)
const configReturnTo  = ref('map')   // 'map' | 'list' after save

function openConfigurator(mode, templateId = null) {
    configIsNew.value    = mode === 'new'
    configTplId.value    = templateId
    configReturnTo.value = 'map'
    configuring.value    = true
}

function onConfigBack() {
    configuring.value = false
}

function onConfigSaved(saved) {
    const idx = localTemplates.value.findIndex(t => t.id === saved.id)
    if (idx !== -1) {
        localTemplates.value[idx] = saved
    } else {
        localTemplates.value.push(saved)
    }
    // Regenerate seat map if the current match uses this template
    if (activeMatch.value) {
        const currentTplId = localMatches.value.find(m => m.id === activeMatch.value.id)?.templateId
            || props.matchSeeds[activeMatch.value.id]
        if (currentTplId === saved.id) {
            const tpl = localTemplates.value.find(t => t.id === saved.id)
            if (tpl) {
                const seats = []
                for (const b of tpl.blocks) {
                    for (const row of (b.rows || [])) {
                        const rowLabel = String(row.label)
                        const rowId    = rowLabel.padStart(2, '0')
                        for (let c = 1; c <= row.seats; c++) {
                            const col = String(c).padStart(2, '0')
                            const id  = `${b.id}-${rowId}-${col}`
                            // preserve existing seat state if seat still exists
                            const existing = seatState.value[activeMatch.value.id]?.find(s => s.id === id)
                            seats.push(existing ?? {
                                id, block: b.id, blockLabel: b.label, blockTier: b.tier || '', blockColor: b.color,
                                row: rowLabel, rowLabel, col: c,
                                status: 'available', guestId: null, resLabel: null, hidden: false,
                            })
                        }
                    }
                }
                seatState.value = { ...seatState.value, [activeMatch.value.id]: seats }
            }
        }
    }
    configuring.value = false
    toast(saved.isNew ? 'Template created.' : 'Template saved.')
}

// ── Helpers ───────────────────────────────────────────────────────
const guestById = id => props.guests.find(g => g.id === id)
const tierFor   = id => props.tiers.find(t => t.id === id)
const venueFor  = id => props.venues.find(v => v.id === id)
const tplName   = tid => localTemplates.value.find(t => t.id === tid)?.name ?? null

function tierColor(tierId) {
    return tierFor(tierId)?.color ?? 'var(--gms-text-3)'
}

function matchMonth(d) { return d ? new Date(d).toLocaleDateString('en-GB', { month: 'short' }).toUpperCase() : '' }
function matchDay(d)   { return d ? new Date(d).getDate() : '' }

function matchStats(m) {
    const seats   = seatState.value[m.id] ?? []
    const visible = seats.filter(s => !s.hidden)
    return {
        total:    visible.length,
        assigned: visible.filter(s => s.status === 'assigned' || s.status === 'ticket').length,
    }
}
function fillPct(m) {
    const s = matchStats(m)
    return s.total ? Math.round(s.assigned / s.total * 100) : 0
}

// ── Planner seat data ─────────────────────────────────────────────
function pseatInfo(seat) {
    const g  = seat.guestId ? guestById(seat.guestId) : null
    const oc = (seat.status === 'reserved' && seat.resLabel) ? orgColor(seat.resLabel) : null
    let name, dot, style
    if (seat.status === 'assigned' || seat.status === 'ticket') {
        name = g ? g.name : '—'
        dot  = g ? tierColor(g.tier) : null
    } else if (seat.status === 'reserved') {
        name  = seat.resLabel || 'Reserved'
        style = oc ? { '--gms-pbar': oc, borderColor: oc, background: oc + '18' } : null
    } else {
        name = 'Open'
    }
    if (seat.hidden) { name = 'Hidden'; dot = null; style = null }
    return { name, dot, style, isTicket: seat.status === 'ticket', oc }
}

function pseatClass(seat) {
    const dim      = !matchesFilt(seat)
    const pq2      = pq.value.trim().toLowerCase()
    const g        = seat.guestId ? guestById(seat.guestId) : null
    const hay      = ((g?.name) || seat.resLabel || '').toLowerCase()
    const dimByPq  = pq2 && !hay.includes(pq2)
    const isPick   = (mode.value === 'assign' && pendingSeat.value === seat.id) || (mode.value === 'swap' && swapFirst.value === seat.id)
    const isResSel = mode.value === 'reserve' && resSel.value.includes(seat.id)
    return [
        'gms-pseat',
        seat.hidden ? 'hidden' : seat.status,
        isPick    ? 'pick'    : '',
        isResSel  ? 'ressel'  : '',
        (dim || dimByPq) ? 'dim'  : '',
        flash.value  === seat.id ? 'flash'  : '',
        pinSeat.value === seat.id ? 'pinned' : '',
    ].filter(Boolean).join(' ')
}

function smapSeatClass(seat) {
    const dim      = !matchesFilt(seat)
    const isPick   = (mode.value === 'assign' && pendingSeat.value === seat.id) || (mode.value === 'swap' && swapFirst.value === seat.id)
    const isResSel = mode.value === 'reserve' && resSel.value.includes(seat.id)
    return [
        'gms-seat',
        seat.hidden ? 'hidden' : seat.status,
        isPick    ? 'pick'   : '',
        isResSel  ? 'ressel' : '',
        dim       ? 'dim'    : '',
        flash.value   === seat.id ? 'flash'  : '',
        pinSeat.value === seat.id ? 'pinned' : '',
    ].filter(Boolean).join(' ')
}

function smapSeatStyle(seat) {
    if (seat.status === 'reserved' && seat.resLabel) {
        const c = orgColor(seat.resLabel)
        return { background: c + '44', borderColor: c }
    }
    return {}
}

// ── Guest panel ───────────────────────────────────────────────────
const glist = computed(() => props.guests.filter(g =>
    g.status === 'confirmed' || g.status === 'accepted' || g.status === 'invited'
))
const filteredGuests = computed(() => {
    const q = gq.value.trim().toLowerCase()
    return glist.value.filter(g => !q || g.name.toLowerCase().includes(q))
})
const seatOf = gid => activeSeats.value.find(s => s.guestId === gid)

// ── List sub-tabs ─────────────────────────────────────────────────
const listSubTab   = ref('assign')
const assignedSeats = computed(() => activeSeats.value.filter(s => s.status === 'assigned' || s.status === 'ticket'))
const reservedSeats = computed(() => activeSeats.value.filter(s => s.status === 'reserved'))

// ── Hover popover actions helper ──────────────────────────────────
const hoverSeat = computed(() => hover.value ? seatById(hover.value.id) : null)

// ── Template application ──────────────────────────────────────────
function assignTemplate() {
    if (!chosenTplId.value) return
    const tpl = localTemplates.value.find(t => t.id === chosenTplId.value)
    if (!tpl) return
    const seats = []
    for (const b of tpl.blocks) {
        for (const row of (b.rows || [])) {
            const rowLabel = String(row.label)
            const rowId    = rowLabel.padStart(2, '0')
            for (let c = 1; c <= row.seats; c++) {
                const col = String(c).padStart(2, '0')
                seats.push({
                    id: `${b.id}-${rowId}-${col}`,
                    block: b.id, blockLabel: b.label, blockTier: b.tier || '', blockColor: b.color,
                    row: rowLabel, rowLabel, col: c,
                    status: 'available', guestId: null, resLabel: null, hidden: false,
                })
            }
        }
    }
    seatState.value[activeMatch.value.id] = seats
    seatState.value = { ...seatState.value }
    const idx = localMatches.value.findIndex(m => m.id === activeMatch.value.id)
    if (idx !== -1) localMatches.value[idx] = { ...localMatches.value[idx], templateId: chosenTplId.value }
    templateModal.value = false
    openMatch(activeMatch.value)
    toast('Template applied.')
}
</script>

<template>
  <!-- ══════════════════════════════════════════════════════════════
       LAYOUT CONFIGURATOR (shown in place of match list or map)
  ══════════════════════════════════════════════════════════════ -->
  <GmsSeatingConfigurator
    v-if="configuring"
    :template="configIsNew ? null : localTemplates.find(t => t.id === configTplId)"
    :venue-id="configIsNew ? (venueFor(activeMatch?.venueId)?.id ?? '') : (localTemplates.find(t=>t.id===configTplId)?.venueId ?? '')"
    :venue-name="venueFor(activeMatch?.venueId)?.name ?? ''"
    @back="onConfigBack"
    @saved="onConfigSaved"
  />

  <!-- ══════════════════════════════════════════════════════════════
       MATCH LIST
  ══════════════════════════════════════════════════════════════ -->
  <div v-else-if="view === 'list'" class="gms-view">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Seating</h1>
        <p style="font-size:13px;color:var(--gms-text-3);margin-top:2px;">
          Manage seat assignments per match. Each match draws from a venue seating template.
        </p>
      </div>
    </div>

    <!-- Stats row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:22px;">
      <div v-for="[label, val, accent] in [
        ['Matches',       localMatches.length,                                                                           'var(--gms-maroon)'],
        ['Templates',     templates.length,                                                                              '#3b82f6'],
        ['With template', localMatches.filter(m=>m.templateId||seatState[m.id]?.length).length,                        'var(--gms-gold)'],
        ['Need template', localMatches.filter(m=>!m.templateId&&!seatState[m.id]?.length).length,                      '#d97706'],
      ]" :key="label" class="gms-stat-card">
        <div class="gms-stat-value" :style="{color:accent}">{{ val }}</div>
        <div class="gms-stat-label">{{ label }}</div>
      </div>
    </div>

    <!-- Match table -->
    <div class="gms-card">
      <div class="gms-card-body-0">
        <table class="gms-table">
          <thead>
            <tr>
              <th>Match</th>
              <th>Venue</th>
              <th>Template</th>
              <th>Date</th>
              <th style="min-width:160px;">Fill</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="m in localMatches"
              :key="m.id"
              style="cursor:pointer;"
              @click="m.templateId || seatState[m.id]?.length ? openMatch(m) : openTemplatePicker(m)"
            >
              <td>
                <div style="display:flex;align-items:center;gap:11px;">
                  <div class="gms-match-date-badge" style="width:40px;padding:5px 4px;flex-shrink:0;">
                    <div class="month">{{ matchMonth(m.date) }}</div>
                    <div class="day" style="font-size:18px;">{{ matchDay(m.date) }}</div>
                  </div>
                  <div>
                    <div style="font-weight:600;font-size:13.5px;">{{ m.name }}</div>
                    <div style="font-size:11.5px;color:var(--gms-text-3);">{{ m.stage }} · {{ m.kickoff }}</div>
                  </div>
                </div>
              </td>
              <td style="font-size:13px;color:var(--gms-text-2);">{{ venueFor(m.venueId)?.name ?? m.venueId }}</td>
              <td>
                <span v-if="m.templateId || seatState[m.id]?.length"
                  class="gms-pill" style="background:var(--gms-invited-bg);color:var(--gms-invited-fg);font-size:10.5px;font-weight:600;">
                  {{ tplName(m.templateId || matchSeeds[m.id]) ?? 'Custom' }}
                </span>
                <span v-else class="gms-pill" style="background:var(--gms-pending-bg);color:var(--gms-pending-fg);font-size:10.5px;">
                  Pick a template
                </span>
              </td>
              <td style="font-size:13px;color:var(--gms-text-2);">{{ m.date }}</td>
              <td>
                <div v-if="m.templateId || seatState[m.id]?.length" class="gms-fill-bar">
                  <div class="gms-fill-bar-track">
                    <div class="gms-fill-bar-fill" :style="{width: fillPct(m) + '%'}" />
                  </div>
                  <span class="gms-fill-pct">{{ fillPct(m) }}%</span>
                </div>
                <span v-else style="font-size:12.5px;color:var(--gms-text-3);">—</span>
              </td>
              <td>
                <div style="display:flex;gap:6px;justify-content:flex-end;">
                  <button class="gms-btn gms-btn-sm"
                    @click.stop="m.templateId || seatState[m.id]?.length ? openMatch(m) : openTemplatePicker(m)">
                    {{ m.templateId || seatState[m.id]?.length ? 'Manage seating' : 'Set up' }}
                    <GmsIcon name="chevron-right" :size="13" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════
       SEAT MAP VIEW
  ══════════════════════════════════════════════════════════════ -->
  <div v-else-if="view === 'map'" class="gms-view">

    <!-- Header ─────────────────────────────────────────────────────── -->
    <div class="gms-seat-header">

      <!-- Back link -->
      <div class="gms-seat-back" @click="backToList">← Seating</div>

      <!-- Title row: title left, buttons right -->
      <div class="gms-seat-title-row">
        <div class="gms-seat-title-area">
          <h1 class="gms-seat-title">{{ activeMatch?.name }}</h1>
          <div class="gms-seat-subtitle">
            {{ activeMatch?.stage }} · {{ venueFor(activeMatch?.venueId)?.name ?? activeMatch?.venueId }} · {{ activeMatch?.date }} · {{ activeMatch?.kickoff }} ·
            {{ seatStats.assigned }}/{{ seatStats.total }} assigned ·
            <span class="gms-seat-tpl-name">{{ tplName(localMatches.find(m=>m.id===activeMatch?.id)?.templateId || matchSeeds[activeMatch?.id]) ?? 'No template' }}</span>
          </div>
        </div>

        <!-- Action buttons -->
        <div class="gms-seat-actions">
          <button class="gms-btn gms-btn-sm"
            @click="openConfigurator('edit', localMatches.find(m=>m.id===activeMatch?.id)?.templateId || matchSeeds[activeMatch?.id])">
            <GmsIcon name="settings" :size="13" /> Edit layout
          </button>
          <button class="gms-btn gms-btn-sm" @click="openTemplatePicker(activeMatch)">
            <GmsIcon name="map" :size="13" /> Change template
          </button>
          <button class="gms-btn gms-btn-sm" @click="toast('Add block not available in this view')">
            <GmsIcon name="plus" :size="13" /> Add block
          </button>
          <button class="gms-btn gms-btn-sm" @click="toast('Snapshot saved to downloads')">
            <GmsIcon name="eye" :size="13" /> Snapshot
          </button>
          <button class="gms-btn gms-btn-sm" @click="toast('Seating exported to Excel')">
            <GmsIcon name="download" :size="13" /> Export
          </button>
          <button class="gms-btn gms-btn-sm" :class="mode==='reserve' ? 'gms-btn-primary' : ''" @click="switchMode('reserve')">
            <GmsIcon name="ticket" :size="13" /> {{ mode === 'reserve' ? 'Reserving…' : 'Reserve' }}
          </button>
          <button class="gms-btn gms-btn-sm" :class="mode==='swap' ? 'gms-btn-primary' : ''" @click="switchMode('swap')">
            <GmsIcon name="arrow-right" :size="13" /> {{ mode === 'swap' ? 'Swapping…' : 'Swap' }}
          </button>
          <button class="gms-btn gms-btn-sm" :class="mode==='hide' ? 'gms-btn-primary' : ''" @click="switchMode('hide')">
            <GmsIcon name="eye" :size="13" /> {{ mode === 'hide' ? 'Hiding…' : 'Hide' }}
          </button>
        </div>
      </div>

    </div>
      <!-- Tabs -->
      <div class="gms-seg" style="width: fit-content; margin-bottom: 18px;">
        <button :class="['gms-seat-tab', mapTab==='map'     ? 'on' : '']" @click="mapTab='map'">Seat map</button>
        <button :class="['gms-seat-tab', mapTab==='planner' ? 'on' : '']" @click="mapTab='planner'">Planner</button>
        <button :class="['gms-seat-tab', mapTab==='list'    ? 'on' : '']" @click="mapTab='list'">List view</button>
      </div>
    <!-- ── LIST VIEW TAB ────────────────────────────────────── -->
    <template v-if="mapTab === 'list'">
      <div style="display:flex;gap:8px;margin-bottom:12px;">
        <button class="gms-btn gms-btn-sm" :class="listSubTab==='assign'?'gms-btn-primary':''" @click="listSubTab='assign'">
          Assignments <span style="opacity:.6;margin-left:4px;">{{ assignedSeats.length }}</span>
        </button>
        <button class="gms-btn gms-btn-sm" :class="listSubTab==='reserve'?'gms-btn-primary':''" @click="listSubTab='reserve'">
          Reservations <span style="opacity:.6;margin-left:4px;">{{ reservedSeats.length }}</span>
        </button>
      </div>
      <div style="font-size:12px;color:var(--gms-text-3);margin-bottom:10px;">Click a row to jump to its seat on the map.</div>

      <!-- Assignments -->
      <div v-if="listSubTab === 'assign'" class="gms-card">
        <div class="gms-card-body-0">
          <table class="gms-table">
            <thead><tr><th>Guest</th><th>Seat</th><th>VAPP</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <tr v-for="seat in assignedSeats" :key="seat.id" style="cursor:pointer;" @click="locateSeat(seat.id)">
                <td>
                  <div v-if="guestById(seat.guestId)" style="display:flex;align-items:center;gap:8px;">
                    <GmsAvatar :name="guestById(seat.guestId).name" size="sm" />
                    <div>
                      <div style="font-size:13px;font-weight:600;">{{ guestById(seat.guestId).name }}</div>
                      <div style="font-size:11px;color:var(--gms-text-3);">{{ guestById(seat.guestId).title }}</div>
                    </div>
                  </div>
                  <span v-else class="gms-muted gms-small">—</span>
                </td>
                <td><span class="gms-mono" style="font-size:12px;">{{ seat.id }}</span></td>
                <td><span class="gms-mono gms-muted" style="font-size:12px;">{{ seat.status === 'ticket' ? 'V-' + (800 + seat.col) : '—' }}</span></td>
                <td>
                  <span class="gms-pill" :style="seat.status==='ticket'
                    ? 'background:#dcfce7;color:#15803d;font-size:10.5px;'
                    : 'background:var(--gms-maroon-light);color:var(--gms-maroon);font-size:10.5px;'">
                    {{ seat.status === 'ticket' ? 'Ticket issued' : 'Assigned' }}
                  </span>
                </td>
                <td>
                  <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" title="Locate on map" @click.stop="locateSeat(seat.id)">
                    <GmsIcon name="map" :size="13" />
                  </button>
                </td>
              </tr>
              <tr v-if="!assignedSeats.length">
                <td colspan="5"><div class="gms-empty gms-empty-title" style="padding:24px;">No assigned seats yet</div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reservations -->
      <div v-else class="gms-card">
        <div class="gms-card-body-0">
          <table class="gms-table">
            <thead><tr><th>Block</th><th>Seat</th><th>Label</th></tr></thead>
            <tbody>
              <tr v-for="seat in reservedSeats" :key="seat.id" style="cursor:pointer;" @click="locateSeat(seat.id)">
                <td style="font-size:13px;">{{ seat.blockLabel }}</td>
                <td><span class="gms-mono" style="font-size:12px;">{{ seat.id }}</span></td>
                <td>
                  <span class="gms-pill" :style="`background:${orgColor(seat.resLabel)}18;color:${orgColor(seat.resLabel)};border:1px solid ${orgColor(seat.resLabel)}44;font-size:10.5px;font-weight:700;`">
                    {{ seat.resLabel || 'Reserved' }}
                  </span>
                </td>
              </tr>
              <tr v-if="!reservedSeats.length">
                <td colspan="3"><div class="gms-empty gms-empty-title" style="padding:24px;">No reserved seats</div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- ── MAP / PLANNER SHARED CONTROLS ─────────────────── -->
    <template v-else>
      <div class="gms-card" style="padding:18px 20px;">

        <!-- Filter + zoom row -->
        <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
          <!-- Block filter -->
          <div class="gms-seg">
            <button :class="fBlock==='all' ? 'gms-seat-tab on' : ''" @click="fBlock='all'">All blocks</button>
            <button v-for="b in seatBlocks" :key="b.id" :class="fBlock===b.id ? 'gms-seat-tab on' : ''" @click="fBlock=b.id">
              {{ b.label }}
            </button>
          </div>
          <!-- Status filter -->
          <div class="gms-seg">
            <button :class="fStatus==='all'        ? 'gms-seat-tab on':''" @click="fStatus='all'">All</button>
            <button :class="fStatus==='unassigned' ? 'gms-seat-tab on':''" @click="fStatus='unassigned'">Unassigned</button>
            <button :class="fStatus==='assigned'   ? 'gms-seat-tab on':''" @click="fStatus='assigned'">Assigned</button>
          </div>

          <!-- Mode indicator pill -->
          <span v-if="mode==='assign' && (pickGuest || pendingSeat)"
            class="gms-pill" style="background:var(--gms-maroon-light);color:var(--gms-maroon);font-weight:600;font-size:12px;margin-left:auto;">
            {{ pickGuest ? `Picked ${guestById(pickGuest)?.name?.split(' ').slice(-1)} — click a seat` : `Seat ${pendingSeat} — pick a guest` }}
          </span>
          <span v-else-if="mode==='swap'" class="gms-pill"
            style="background:var(--gms-invited-bg);color:var(--gms-invited-fg);font-weight:600;font-size:12px;margin-left:auto;">
            {{ swapFirst ? `Swap ${swapFirst} → pick another seat` : 'Swap mode — pick two assigned seats' }}
          </span>
          <span v-else-if="mode==='hide'" class="gms-pill"
            style="background:var(--gms-surface-3);color:var(--gms-text-2);font-size:12px;margin-left:auto;">
            Hide mode · click to toggle{{ seatStats.hidden ? ` · ${seatStats.hidden} hidden` : '' }}
          </span>

          <!-- Planner tools (right side) -->
          <div class="gms-planner-tools" :style="mode!=='assign'||pickGuest||pendingSeat ? {} : {marginLeft:'auto'}">
            <div v-if="mapTab==='planner' && mode==='assign' && !pickGuest && !pendingSeat"
              class="gms-search-wrap" style="width:180px;">
              <GmsIcon name="search" :size="13" class="gms-search-icon" />
              <input v-model="pq" class="gms-search-input" placeholder="Highlight a guest…" />
            </div>
            <div class="gms-zoomer">
              <button @click="zoomBy(-0.1)" :disabled="zoom <= Z_MIN" title="Zoom out">−</button>
              <button class="gms-zlabel" @click="zoom=1" title="Reset zoom">{{ Math.round(zoom*100) }}%</button>
              <button @click="zoomBy(0.1)"  :disabled="zoom >= Z_MAX" title="Zoom in">+</button>
            </div>
            <button class="gms-btn gms-btn-sm" :class="floatPanel ? 'gms-btn-primary' : ''"
              @click="floatPanel = !floatPanel" title="Toggle guest assignment panel">
              <GmsIcon name="users" :size="13" /> Assign
            </button>
          </div>
        </div>

        <!-- Bulk reserve bar -->
        <div v-if="mode === 'reserve'" class="gms-res-bar">
          <div class="gms-res-bar-row">
            <span class="gms-res-bar-lbl">Reserve for</span>
            <div class="gms-res-orgs">
              <button v-for="org in ORGS" :key="org.code"
                :class="['gms-res-org', (!resCustom.trim() && resOrg===org.code) ? 'on' : '']"
                :title="org.name" @click="resOrg=org.code; resCustom=''">
                <span class="gms-ro-dot" :style="{background:org.color}" /> {{ org.code }}
              </button>
            </div>
            <input v-model="resCustom" class="gms-res-custom" placeholder="or type a label…" maxlength="14" />
          </div>
          <div class="gms-res-bar-row">
            <span class="gms-res-count">{{ resSel.length }} seat{{ resSel.length===1?'':'s' }} selected</span>
            <div style="flex:1;" />
            <button v-if="resSel.length" class="gms-btn gms-btn-sm gms-btn-ghost" @click="doRelease">
              <GmsIcon name="x" :size="13" /> Release
            </button>
            <button class="gms-btn gms-btn-sm" :disabled="!resSel.length" @click="resSel=[]">Clear</button>
            <button class="gms-btn gms-btn-sm gms-btn-primary" :disabled="!resSel.length" @click="doReserve">
              <GmsIcon name="ticket" :size="13" /> Reserve as <b style="margin-left:4px;">{{ resLabel }}</b>
            </button>
          </div>
          <div style="font-size:12px;color:var(--gms-text-3);">Click available seats to add them to the reservation.</div>
        </div>

        <!-- Active reservations summary -->
        <div v-if="Object.keys(resCounts).length" class="gms-res-summary">
          <span style="font-size:11.5px;font-weight:600;color:var(--gms-text-3);">Reserved:</span>
          <span v-for="[code, n] in Object.entries(resCounts)" :key="code"
            class="gms-res-tag" :style="{borderColor: orgColor(code), color: orgColor(code)}">
            <span class="gms-rt-dot" :style="{background: orgColor(code)}" />
            {{ code }} <b style="font-weight:700;opacity:.65;font-size:11px;margin-left:2px;">{{ n }}</b>
          </span>
        </div>

        <!-- Pitch -->
        <div class="gms-pitch">PITCH</div>

        <!-- ══ SEAT MAP (small squares) ═════════════════════ -->
        <div v-if="mapTab === 'map'" class="gms-smap">
          <div class="gms-smap-inner" :style="{zoom}">
            <div class="gms-stand">
              <template v-for="(block, bi) in visibleBlocks" :key="block.id">
                <div class="gms-smap-block">
                  <div class="gms-smap-block-h">
                    {{ block.label }}{{ block.tier ? ' · ' + block.tier : '' }}
                  </div>
                  <template v-for="row in block.rowsArray" :key="row.label">
                    <div class="gms-seat-row" style="gap:4px;margin-bottom:4px;justify-content:center;">
                      <span class="gms-rl">{{ row.label }}</span>
                      <template v-for="seat in row.seats" :key="seat.id">
                        <div
                          :data-seat="seat.id"
                          :class="smapSeatClass(seat)"
                          :style="smapSeatStyle(seat)"
                          :title="seat.id + (seat.guestId ? ' – ' + guestById(seat.guestId)?.name : '') + (seat.resLabel ? ' [' + seat.resLabel + ']' : '')"
                          @click="seatClick(seat, $event)"
                          @mouseenter="seatEnter(seat, $event)"
                          @mouseleave="seatLeave()"
                        />
                        <span v-if="row.aisles.includes(seat.col) && seat.col < (row.seats[row.seats.length-1]?.col ?? 0)" class="gms-aisle-gap" />
                      </template>
                      <span class="gms-rl">{{ row.label }}</span>
                    </div>
                    <div v-if="row.walkway" class="gms-walkway"><span>walkway</span></div>
                  </template>
                </div>
                <!-- walkway between blocks -->
                <div v-if="bi < visibleBlocks.length - 1" class="gms-inter-block-walkway" />
              </template>
            </div>
          </div>
          <!-- Seat map legend -->
          <div class="gms-seat-legend">
            <span class="k"><span class="sw" style="background:var(--gms-surface-2);border-color:var(--gms-border);" />Available</span>
            <span class="k"><span class="sw" style="background:repeating-linear-gradient(45deg,#e2ddd8 0 3px,#d4cec9 3px 6px);" />Reserved</span>
            <span class="k"><span class="sw" style="background:var(--gms-maroon);border-color:var(--gms-maroon);" />Assigned</span>
            <span class="k"><span class="sw" style="background:var(--gms-text);border-color:var(--gms-text);" />Ticket issued</span>
            <span class="k"><span class="sw" style="background:var(--gms-surface-2);border-style:dashed;opacity:.6;" />Hidden{{ seatStats.hidden ? ` (${seatStats.hidden})` : '' }}</span>
          </div>
        </div>

        <!-- ══ PLANNER (large boxes) ═════════════════════ -->
        <div v-else class="gms-pmap">
          <div class="gms-pmap-inner" :style="{zoom}">
            <template v-for="(block, bi) in visibleBlocks" :key="block.id">
              <div class="gms-pblock">
                <div class="gms-pblock-h">
                  {{ block.label }}{{ block.tier ? ' · ' + block.tier : '' }}
                </div>
                <div class="gms-prows">
                  <template v-for="row in block.rowsArray" :key="row.label">
                    <div class="gms-prow">
                      <span class="gms-prl">{{ row.label }}</span>
                      <template v-for="seat in row.seats" :key="seat.id">
                        <div
                          :data-seat="seat.id"
                          :class="pseatClass(seat)"
                          :style="pseatInfo(seat).style ?? {}"
                          @click="seatClick(seat, $event)"
                          @mouseenter="seatEnter(seat, $event)"
                          @mouseleave="seatLeave()"
                        >
                          <span class="gms-pid">{{ seat.id }}</span>
                          <span class="gms-pnm" :style="pseatInfo(seat).oc && !seat.hidden ? {color: pseatInfo(seat).oc} : {}">
                            <span v-if="pseatInfo(seat).dot" class="gms-pdotc" :style="{background: pseatInfo(seat).dot}" />
                            {{ pseatInfo(seat).name }}
                          </span>
                          <span v-if="pseatInfo(seat).isTicket" class="gms-pmark">🎫</span>
                        </div>
                        <span v-if="row.aisles.includes(seat.col) && seat.col < (row.seats[row.seats.length-1]?.col ?? 0)" class="gms-paisle-gap" />
                      </template>
                      <span class="gms-prl">{{ row.label }}</span>
                    </div>
                    <div v-if="row.walkway" class="gms-pwalkway"><span>walkway</span></div>
                  </template>
                </div>
              </div>
              <div v-if="bi < visibleBlocks.length - 1" class="gms-inter-block-walkway" />
            </template>
          </div>
          <!-- Planner legend -->
          <div class="gms-seat-legend">
            <span class="k"><span class="sw" style="border-style:dashed;" />Open</span>
            <span class="k"><span class="sw" style="background:var(--gms-surface-2);border-color:var(--gms-gold);border-left-width:4px;" />Reserved</span>
            <span class="k"><span class="sw" style="background:var(--gms-maroon-light);border-color:var(--gms-maroon);border-left-width:4px;" />Assigned</span>
            <span class="k"><span class="sw" style="background:#f5f2ef;border-color:var(--gms-text);border-left-width:4px;" />Ticket issued</span>
            <span class="k"><span class="sw" style="background:var(--gms-surface-2);border-style:dashed;opacity:.6;" />Hidden{{ seatStats.hidden ? ` (${seatStats.hidden})` : '' }}</span>
          </div>
        </div>

      </div><!-- /card -->
    </template>

    <!-- ══════════════════════════════════════════════════════
         FLOATING GUEST PANEL
    ══════════════════════════════════════════════════════ -->
    <div
      v-if="mapTab !== 'list' && floatPanel"
      class="gms-gpanel"
      :style="panelPos ? {left: panelPos.x + 'px', top: panelPos.y + 'px', right: 'auto'} : {right: '30px', top: '120px'}"
    >
      <div class="gms-gpanel-drag" @mousedown="startPanelDrag">
        <span class="gms-gpanel-drag-title">
          <GmsIcon name="users" :size="14" /> Guests
        </span>
        <button class="gms-gpanel-x" @click="floatPanel=false" title="Hide">
          <GmsIcon name="x" :size="14" />
        </button>
      </div>
      <div class="gms-gpanel-h">
        <div style="display:flex;gap:8px;margin-bottom:10px;">
          <div style="text-align:center;padding:6px 12px;background:var(--gms-maroon-light);border-radius:8px;">
            <div style="font-family:var(--gms-font-display);font-size:22px;color:var(--gms-maroon);line-height:1;">{{ seatStats.assigned }}</div>
            <div style="font-size:10px;font-weight:700;color:var(--gms-text-3);text-transform:uppercase;letter-spacing:.05em;">assigned</div>
          </div>
          <div style="text-align:center;padding:6px 12px;background:#dcfce7;border-radius:8px;">
            <div style="font-family:var(--gms-font-display);font-size:22px;color:#15803d;line-height:1;">
              {{ seatStats.total ? Math.round(seatStats.assigned/seatStats.total*100) : 0 }}%
            </div>
            <div style="font-size:10px;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.05em;">filled</div>
          </div>
        </div>
        <div class="gms-search-wrap">
          <GmsIcon name="search" :size="13" class="gms-search-icon" />
          <input v-model="gq" class="gms-search-input" placeholder="Confirmed guests…" />
        </div>
        <div style="font-size:11.5px;color:var(--gms-text-3);margin-top:8px;line-height:1.4;">
          {{ mode==='reserve' ? 'Switch off Reserve to assign guests.' : 'Click a guest, then a seat — or hover any seat for actions.' }}
        </div>
      </div>
      <div class="gms-gpanel-list">
        <div
          v-for="g in filteredGuests" :key="g.id"
          :class="['gms-gp-item', pickGuest===g.id ? 'pick' : '']"
          @click="guestClick(g)"
        >
          <GmsAvatar :name="g.name" size="sm" />
          <div style="flex:1;min-width:0;">
            <div class="gms-gn">{{ g.name }}</div>
            <div class="gms-gm">{{ seatOf(g.id)?.id ?? 'unassigned' }}</div>
          </div>
          <span v-if="seatOf(g.id)"
            class="gms-pill"
            :style="seatOf(g.id).status==='ticket'
              ? 'background:#dcfce7;color:#15803d;font-size:10px;'
              : 'background:var(--gms-maroon-light);color:var(--gms-maroon);font-size:10px;'">
            {{ seatOf(g.id).status==='ticket' ? 'ticket' : 'seated' }}
          </span>
          <span v-else
            class="gms-pill"
            :style="`background:${tierFor(g.tier)?.bg ?? 'var(--gms-surface-3)'};color:${tierFor(g.tier)?.color ?? 'var(--gms-text-3)'};font-size:10px;`">
            {{ tierFor(g.tier)?.name ?? g.tier }}
          </span>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         HOVER POPOVER
    ══════════════════════════════════════════════════════ -->
    <div
      v-if="hoverSeat"
      class="gms-seat-pop"
      :style="{
        left: hover.rect.left + hover.rect.width/2 + 'px',
        top: hover.below ? hover.rect.bottom + 10 + 'px' : hover.rect.top - 10 + 'px',
        transform: hover.below ? 'translate(-50%,0)' : 'translate(-50%,-100%)',
      }"
      @mouseenter="popKeep"
      @mouseleave="popLeave"
    >
      <span :class="['gms-sp-arrow', hover.below ? 'top' : 'bottom']" />
      <div class="gms-sp-head">
        <span class="gms-sp-id">{{ hoverSeat.id }}</span>
        <span class="gms-sp-status">
          <span class="gms-sp-dot" :style="{background:
            hoverSeat.status==='assigned' ? 'var(--gms-maroon)' :
            hoverSeat.status==='ticket'   ? 'var(--gms-text)' :
            hoverSeat.status==='reserved' ? 'var(--gms-gold)' : 'var(--gms-text-3)'
          }" />
          {{ hoverSeat.status === 'ticket' ? 'Ticket issued' :
             hoverSeat.status.charAt(0).toUpperCase() + hoverSeat.status.slice(1) }}
        </span>
      </div>
      <!-- Guest info -->
      <div v-if="guestById(hoverSeat.guestId)" class="gms-sp-guest">
        <GmsAvatar :name="guestById(hoverSeat.guestId).name" size="sm" />
        <div style="min-width:0;">
          <div class="gms-sp-name">{{ guestById(hoverSeat.guestId).name }}</div>
          <div class="gms-sp-meta" style="color:var(--gms-text-3);font-size:11.5px;">
            <span v-if="tierFor(guestById(hoverSeat.guestId).tier)"
              class="gms-pill"
              :style="`background:${tierFor(guestById(hoverSeat.guestId).tier).bg};color:${tierFor(guestById(hoverSeat.guestId).tier).color};font-size:10px;padding:1px 7px;`">
              {{ tierFor(guestById(hoverSeat.guestId).tier).name }}
            </span>
          </div>
        </div>
      </div>
      <!-- Org info -->
      <div v-if="hoverSeat.status==='reserved' && hoverSeat.resLabel" class="gms-sp-org"
        :style="{borderColor: orgColor(hoverSeat.resLabel), color: orgColor(hoverSeat.resLabel)}">
        <span class="gms-sp-org-code" :style="{background: orgColor(hoverSeat.resLabel)}">{{ hoverSeat.resLabel }}</span>
        <span>{{ orgOf(hoverSeat.resLabel)?.name ?? hoverSeat.resLabel }}</span>
      </div>
      <!-- Actions -->
      <div class="gms-sp-acts">
        <template v-if="hoverSeat.status==='assigned'">
          <button class="gms-btn gms-btn-sm gms-btn-primary" style="flex:1;justify-content:center;"
            @click="issueTicket(hoverSeat.id);toast('Ticket issued 🎫');hover=null">
            <GmsIcon name="ticket" :size="12" /> Ticket
          </button>
          <button class="gms-btn gms-btn-sm" style="flex:1;justify-content:center;"
            @click="unassignSeat(hoverSeat.id);toast('Seat freed');hover=null">
            <GmsIcon name="x" :size="12" /> Unassign
          </button>
        </template>
        <template v-else-if="hoverSeat.status==='ticket'">
          <button class="gms-btn gms-btn-sm" style="flex:1;justify-content:center;"
            @click="revokeTicket(hoverSeat.id);toast('Ticket revoked');hover=null">
            <GmsIcon name="arrow-right" :size="12" /> Revoke ticket
          </button>
          <button class="gms-btn gms-btn-sm" style="flex:1;justify-content:center;"
            @click="unassignSeat(hoverSeat.id);toast('Seat freed');hover=null">
            <GmsIcon name="x" :size="12" /> Unassign
          </button>
        </template>
        <template v-else>
          <button class="gms-btn gms-btn-sm gms-btn-primary" style="flex:1;justify-content:center;"
            @click="pendingSeat=hoverSeat.id;toast('Pick a guest from the Assign panel');floatPanel=true;hover=null">
            <GmsIcon name="users" :size="12" /> Assign guest
          </button>
          <button v-if="hoverSeat.status==='reserved'"
            class="gms-btn gms-btn-sm" style="flex:1;justify-content:center;"
            @click="releaseSeats([hoverSeat.id]);toast('Released');hover=null">
            <GmsIcon name="x" :size="12" /> Release
          </button>
        </template>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         PINNED CONTACT CARD
    ══════════════════════════════════════════════════════ -->
    <div
      v-if="pinSeat && seatById(pinSeat)"
      class="gms-seat-pop pinned"
      :style="{position:'fixed', left: pinPos.x + 'px', top: pinPos.y + 'px', transform: 'none'}"
    >
      <div class="gms-sp-head drag" @mousedown="startPopDrag">
        <span class="gms-sp-id">{{ pinSeat }}</span>
        <span class="gms-sp-status">
          <span class="gms-sp-dot" :style="{background:
            seatById(pinSeat).status==='assigned' ? 'var(--gms-maroon)' :
            seatById(pinSeat).status==='ticket'   ? 'var(--gms-text)' : 'var(--gms-text-3)'
          }" />
          {{ seatById(pinSeat).status === 'ticket' ? 'Ticket issued' : 'Assigned' }}
        </span>
        <button class="gms-sp-close" @click="pinSeat=null" @mousedown.stop title="Close">
          <GmsIcon name="x" :size="13" />
        </button>
      </div>
      <div v-if="guestById(seatById(pinSeat).guestId)" class="gms-sp-guest">
        <GmsAvatar :name="guestById(seatById(pinSeat).guestId).name" size="sm" />
        <div style="min-width:0;">
          <div class="gms-sp-name">{{ guestById(seatById(pinSeat).guestId).name }}</div>
          <div class="gms-sp-meta" style="color:var(--gms-text-3);font-size:11.5px;">
            <span>{{ guestById(seatById(pinSeat).guestId).title }}</span>
            <span v-if="guestById(seatById(pinSeat).guestId).nationality">· {{ guestById(seatById(pinSeat).guestId).nationality }}</span>
          </div>
        </div>
      </div>
      <div class="gms-sp-acts" style="margin-top:10px;">
        <button class="gms-btn gms-btn-sm gms-btn-primary" style="flex:1;justify-content:center;"
          @mousedown.stop
          @click="issueTicket(pinSeat);toast('Ticket issued 🎫');pinSeat=null">
          <GmsIcon name="ticket" :size="12" /> Ticket
        </button>
        <button class="gms-btn gms-btn-sm" style="flex:1;justify-content:center;"
          @mousedown.stop
          @click="unassignSeat(pinSeat);toast('Seat freed');pinSeat=null">
          <GmsIcon name="x" :size="12" /> Unassign
        </button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════
       TEMPLATE PICKER MODAL
  ══════════════════════════════════════════════════════════════ -->
  <GmsModal :open="templateModal" title="Choose Seating Template" @close="templateModal=false">
    <p style="font-size:13px;color:var(--gms-text-2);margin-bottom:14px;">
      Select a template to generate the seat map for this match. Assignments won't affect other matches.
    </p>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <label
        v-for="t in localTemplates"
        :key="t.id"
        :style="[
          {display:'flex',alignItems:'center',gap:10,padding:'12px',borderRadius:'8px',cursor:'pointer',transition:'border-color 120ms',border:'1px solid var(--gms-border)'},
          chosenTplId===t.id ? {borderColor:'var(--gms-maroon)',background:'var(--gms-maroon-light)'} : {}
        ]"
      >
        <input type="radio" v-model="chosenTplId" :value="t.id" />
        <div>
          <div style="font-weight:600;font-size:13.5px;">{{ t.name }}</div>
          <div style="font-size:12px;color:var(--gms-text-3);">
            {{ t.blocks.length }} block{{ t.blocks.length===1?'':'s' }} ·
            {{ t.blocks.reduce((a,b) => a + (b.rows||[]).reduce((x,r) => x + r.seats, 0), 0) }} seats
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;">
            <span v-for="b in t.blocks" :key="b.id"
              style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:5px;border:1px solid var(--gms-border);"
              :style="{background: b.color + '18', color: b.color, borderColor: b.color + '44'}">
              {{ b.label }}
            </span>
          </div>
        </div>
      </label>
    </div>
    <template #footer>
      <button class="gms-btn gms-btn-ghost" @click="templateModal=false">Cancel</button>
      <button class="gms-btn gms-btn-primary" @click="assignTemplate">Apply Template</button>
    </template>
  </GmsModal>
</template>
