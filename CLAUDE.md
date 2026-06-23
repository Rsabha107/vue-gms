# GMS — Guest Management System

A hi-fi **Guest Management System** for VIP protocol at large sporting events (mock event: **Doha Cup '26**, Lusail Stadium). Built as a **Laravel 11 + Inertia.js + Vue 3** application.

The original prototype (`GMS - Standalone.html`, root) is kept for reference only — do not edit it.

---

## Entry point & routing

- Visit **`/gms`** (requires auth) — handled by `routes/gms.php`, included at the bottom of `routes/web.php`.
- All GMS routes use prefix `/gms`, middleware `auth`, name prefix `gms.`.
- Navigation is Inertia-based (no full-page reloads). `Link` from `@inertiajs/vue3` for all internal links.

---

## Directory structure

```
app/
  Http/Controllers/Gms/       ← controllers (one per view + event controller)
  Models/
    Event.php                 ← Event model with venues() relationship
    Venue.php                 ← Venue model
  Services/Gms/GmsMockData.php ← mock data + event fetching

database/
  migrations/
    2026_04_12_191803_create_venues_table.php
    2026_04_12_191803_create_events_table.php
    2026_04_12_191804_create_venue_event_table.php
  seeders/
    VenueSeeder.php           ← venue data
    EventSeeder.php           ← sample events with venue attachments

routes/
  gms.php                     ← all GMS routes

resources/
  css/gms.css                 ← GMS design tokens + all component CSS
  js/
    Layouts/GmsLayout.vue     ← shell: sidebar + topbar + event selector + toast provider
    Components/Gms/           ← shared primitives (see below)
    Pages/Gms/                ← 9 page views (see below)
```

---

## Event Management

**Event Selector** — The sidebar displays a dropdown to switch between active events. The current event is stored in the session and used across all GMS views.

**Database:**
- **Tables:** 
  - `events` (id, name, subtitle, location, venue, date_start, date_end, logo, active_flag, timestamps)
  - `venues` (id, name, city, country, capacity, type, timestamps)
  - `venue_event` (event_id, venue_id) — pivot table for many-to-many relationship
- **Models:** 
  - `App\Models\Event` with `active()` scope, `formatted_dates` accessor, and `venues()` relationship
  - `App\Models\Venue` with `events()` relationship
- **Seeders:** `VenueSeeder` creates 3 venues, `EventSeeder` creates 3 sample events with venue attachments

**Usage:**
1. Click the event pill in sidebar to open dropdown
2. Select an event to switch (stored in session)
3. All GMS pages automatically use selected event
4. Route: `POST /gms/events/switch` (handled by `GmsEventController`)

**Data Flow:**
- `GmsMockData::getEvents()` → fetches all active events from DB
- `GmsMockData::getEvent()` → fetches current event from session (or defaults to first active)
- `AppServiceProvider` shares `gmsEvents` globally with all Inertia pages via `Inertia::share()`
- Controllers pass `event` prop to views via `GmsMockData::getEvent()`

**Setup:** Run `php artisan migrate` and `php artisan db:seed --class=EventSeeder`

---

## Mock data layer (`GmsMockData.php`)

**Current state:** Mixed data sources transitioning to database. Controllers call `GmsMockData::get*()` and pass results as Inertia props.

**Database-backed methods:**
- `getEvents()` — fetches all active events from DB
- `getEvent()` — fetches current event from session (or defaults to first active event)
- `getEmailTemplates()` — fetches all email templates from DB
- `getMatches()` — fetches all matches from DB with venue relationships

**Static array methods (to be migrated):**
- `getTiers()` `getGuests()` `getGroups()` `getHosts()` `getHotels()` `getVenues()` — static arrays
- `getSeatingTemplates()` `seatsFromTemplate($tpl)` `getMatchSeeds()` `getMatchSeats()` — static arrays
- `getFlightRequests()` `getAccommodationRequests()` `getTransportRequests()` `getArrivalDepartureRequests()` — static arrays
- `getDashboardStats()` — static array

**DB phase (next):** replace remaining `get*()` methods with real Eloquent queries. Tables needed: `guests`, `tiers`, `groups`, `hosts`, `hotels`, `seating_templates`, `match_seats`, `flight_requests`, `accommodation_requests`, `transport_requests`, `ad_requests`.

Seat mutations currently use `axios.post` to stub endpoints (`/gms/seating/{matchId}/seats/{seatId}`); swap for real DB writes when ready.

---

## Controllers (`app/Http/Controllers/Gms/`)

| Controller | Route | Inertia page |
|---|---|---|
| GmsDashboardController | `GET /gms` | `Gms/Dashboard` |
| GmsEventController | `POST /gms/events/switch` | (switches current event) |
| GmsEventsController | `GET /gms/events` + CRUD | `Gms/Events/Index` |
| GmsGroupsController | `GET /gms/groups` + CRUD | `Gms/Groups/Index` |
| GmsGuestController | `GET /gms/guests` + CRUD + `POST /gms/guests/import` + `POST /gms/guests/add-to-event` + `POST /gms/guests/remove-from-event` | `Gms/Guests/Index` |
| GmsInvitationController | `GET /gms/invitations` + `POST /gms/invitations/add-guests` + `DELETE /gms/invitations/remove-guest/{id}` | `Gms/Invitations/Index` |
| GmsSeatingController | `GET /gms/seating` | `Gms/Seating/Index` |
| GmsServiceLevelController | `GET /gms/service-levels` + CRUD | `Gms/ServiceLevels/Index` |
| GmsFlightController | `GET /gms/flights` + CRUD | `Gms/Flights/Index` |
| GmsAccommodationController | `GET /gms/accommodation` + CRUD | `Gms/Accommodation/Index` |
| GmsTransportController | `GET /gms/transport` + CRUD | `Gms/Transport/Index` |
| GmsArrivalDepartureController | `GET /gms/arrival-departure` + CRUD | `Gms/ArrivalDeparture/Index` |

Each controller validates on mutations and returns `back()->with('success', …)` (stub until DB is wired).

---

## Layout: `GmsLayout.vue`

Used by all GMS pages via `defineOptions({ layout: GmsLayout })` in each page's `<script setup>`.

Features:**event selector dropdown**, Core nav + Modules nav + Setup nav, user footer.
- **Event Selector** — Click event pill to open dropdown. Switch between active events (stored in session).
- **Topbar** — breadcrumb (auto-derived from URL), ⌘K search trigger, notification button, user avatar.
- **Toast system** — `provide('toast', addToast)`. Inject with `const toast = inject('toast')` in any page/component, call `toast('message')` or `toast('message', 'error'|'info')`.
- **Command palette** — opens on ⌘K / Ctrl+K, navigates to any GMS view.
- **Navigation sections** — Core (Dashboard, Guests, Invitations, Seating, Service Levels), Modules (Flights, Accommodation, Transport, Arrival & Dep.), Setup (Events, Venues, Matches, Groups, Email Templates, Settings).
- Active nav item detected by matching `page.props.ziggy?.location` against each nav item's `href`.
- Breadcrumb text auto-resolved from URL path — no slot needed from pages.

Props accessed:
- `page.props.event` — current event object
- `page.props.gmsEvents` — array of all active events (shared globally via AppServiceProvider)
- `page.props.auth` — authenticated user
- `page.props.ziggy` — routing infot each nav item's `href`.
- Breadcrumb text auto-resolved from URL path — no slot needed from pages.

---

## Shared components (`resources/js/Components/Gms/`)

| Component | Props | Notes |
|---|---|---|
| `GmsIcon` | `name`, `size=16` | ~40 inline SVGs including `loader` for spinners. See `icons` map in file for names. Includes: home, users, mail, eye, edit, trash, more-vertical, calendar, loader, etc. `loader` icon auto-spins with `.gms-icon-spin` class. |
| `GmsBtn` | `variant='ghost'/'primary'/'danger'`, `icon`, `processing=false`, `disabled=false` | Button component with built-in processing state. Shows spinner when `processing=true`. Auto-disables during processing. |
| `GmsAvatar` | `name`, `size='md'` (`sm/md/lg/xl`) | Initials, auto-colored from 10-palette. |
| `GmsPill` | `type='status'/'tier'/'custom'`, `value`, `tiers=[]`, `bg`, `fg` | Status pills use built-in color map; tier pills need `tiers` prop. |
| `GmsFilterDropdown` | `v-model`, `label`, `allLabel`, `options`, `valueKey='id'`, `labelKey='name'` | Reusable chip-style filter dropdown. Supports string or object arrays. Slot: `#item` for custom rendering. Emits `@open`. Auto-closes on outside click. |
| `GmsDrawer` | `open`, `title`, `subtitle` + `@close` | Right-side sliding panel. Slots: default body, `#header-prefix`, `#footer`. |
| `GmsModal` | `open`, `title`, `size=''` (`sm/lg`) + `@close` | Dialog. Slots: default body, `#footer`. **Modal buttons pattern:** Use `form.processing` to disable buttons and show spinner during form submission. See example below. |
| `GmsToast` | `toasts` (array) | Rendered in layout — don't instantiate in pages. |
| `GmsCommandPalette` | `@close` | Rendered in layout — don't instantiate in pages. |

**Modal button processing pattern:**
```vue
<template #footer>
  <button class="gms-btn gms-btn-ghost" @click="closeModal" :disabled="form.processing">Cancel</button>
  <button class="gms-btn gms-btn-primary" @click="save" :disabled="form.processing">
    <GmsIcon v-if="form.processing" name="loader" :size="14" style="margin-right: 6px;" />
    Save
  </button>
</template>
```
The `form.processing` property from Inertia's `useForm()` is automatically `true` during form submission.

---

## Pages (`resources/js/Pages/Gms/`)

### Pattern shared by all pages
```vue
<script setup>
import GmsLayout from '@/Layouts/GmsLayout.vue'
defineOptions({ layout: GmsLayout })
const props = defineProps({ /* Inertia props from controller */ })
const toast = inject('toast')
</script>
```

Data mutations use `useForm` from `@inertiajs/vue3` + `form.post/put/delete(route('gms.…'))`. Optimistic UI: mutate a local `ref` copy immediately, then fire the Inertia call in the background.

### Dashboard (`Gms/Dashboard.vue`)
Stats grid (6 cards), upcoming fixtures list, recent guests, module quick-links grid.
Props: `event`, `stats`, `matches` (first 4), `guests` (first 8), `tiers`.

### Guests (`Gms/Guests/Index.vue`) — Directory view
Central directory of all guests, reused across events. Each guest carries an `attendance` map (`{eventId: {status, added_at, invited_at}}`) from the `guest_event` pivot table.

**Key features:**
- **Events column:** Per-event status chips; current event highlighted in maroon
- **Scope filter:** All / On this event / Not on event — filters the directory by event membership
- **Checkbox multi-select:** Select guests → bottom action bar shows "Add N to {event}" button
- **Per-row Add button:** "+" button on rows not yet on the current event
- **Import:** Bulk upload from Excel/CSV via `GuestsImport` class
- **Profile drawer:** Overview (contact, companions, preferences, service level), Facilities tab, Invitations tab — now shows attendance across all events

**Attendance data model:**
- `guest_event` pivot table: `guest_id`, `event_id`, `status` (not_invited/invited/accepted/declined/confirmed), `added_at`, `invited_at`
- Guest model has `events()` belongsToMany relationship
- Controller returns ALL guests (no event_id filter) with attendance loaded
- Two separate actions: **add to event** (roster membership) vs **send invite** (email/RSVP step)

**Facility Management:** Two-layer system for service customization:
- **Tier baseline:** Facilities inherited from service level
- **Guest overrides:** `facilityOverrides` JSON column with `{added: [], removed: []}` structure
- Final facilities computed via `getFinalFacilitiesAttribute()` in Guest model

Props: `guests` (all, with `attendance` map), `tiers`, `groups`, `hosts`, `hotels`, `event`, `gmsEvents`.
Routes: `gms.guests.index` (GET), `gms.guests.store` (POST), `gms.guests.import` (POST), `gms.guests.addToEvent` (POST), `gms.guests.removeFromEvent` (POST), `gms.guests.update` (PUT), `gms.guests.destroy` (DELETE).

### Invitations (`Gms/Invitations/Index.vue`) — Event roster view
Roster for the current event — everyone who's been added, and where their invitation stands.

**Key features:**
- **Stats strip:** On roster · Not invited · Invited/awaiting · Accepted · Declined
- **Add guests picker:** Modal pulls from directory (guests NOT on event), multi-select, adds as "Not invited"
- **Status vocabulary:** `not_invited` (on roster, no invite), `invited` (sent, awaiting), `accepted`/`confirmed`, `declined`
- **Table columns:** Guest, Service (tier pill), Sessions (accepted match count), Status, Sent/Accepted dates, Passport
- **Row actions:** "Invite" button (not-invited), edit icon (invited+), more menu (accept on behalf, mark confirmed/declined, remove from roster)
- **Guest services overview:** Second tab showing service module statuses for confirmed guests

Props: `roster` (guests on event with invitation data), `directory` (guests NOT on event, for picker), `tiers`, `emailTemplates`, `event`.
Routes: `gms.invitations.index` (GET), `gms.invitations.send` (POST), `gms.invitations.addGuests` (POST), `gms.invitations.removeGuest` (DELETE), `gms.invitations.acceptOnBehalf` (POST), `gms.invitations.markConfirmed` (POST), `gms.invitations.markDeclined` (POST), `gms.invitations.resetToPending` (POST).

### Seating (`Gms/Seating/Index.vue`)
Internal view state machine: `view = 'list' | 'map'`.

**List view:** match cards with date badge, stats (assigned/total), template status pill.
- Click match with template → opens map view.
- Click match without template → opens **TemplatePicker modal**.

**Map view:** Planner tab (seat grid by block/row) + List tab (table of assigned seats).
- Seat color: `available` (warm grey) · `assigned` (maroon) · `reserved` (slate, tinted by org) · `ticket` (green) · `hidden` (dashed border).
- Click seat → **Assign modal** (actions: assign/reserve/release/ticket/hide). Mutation: axios.post to stub endpoint + optimistic local update via `seatState` ref.

Props: `matches`, `templates`, `matchSeeds`, `matchSeats`, `venues`, `guests`, `tiers`, `event`.
Local state: `localMatches`, `seatState` (reactive deep copy of `matchSeats`).

### Service Levels (`Gms/ServiceLevels/Index.vue`)
Tier cards with colour bar, guest count, facility chips. Create/edit modal (name, primary colour, bg colour, facilities textarea). Duplicate and delete actions. Optimistic local updates via `localTiers`.
Props: `tiers`, `guests`, `event`.

### Request-queue modules (Flights, Accommodation, Transport, ArrivalDeparture)
All share the same structure: stats strip, filter toolbar (search + status pills), data table, detail drawer, new-request modal. Optimistic `localReqs` ref.

- **Flights** (`Gms/Flights/Index.vue`) — flight no., route, class, pax, date. Drawer shows departure/arrival detail; footer has Confirm/Cancel buttons.
- **Accommodation** (`Gms/Accommodation/Index.vue`) — hotel, room type, check-in/out, nights.
- **Transport** (`Gms/Transport/Index.vue`) — type, vehicle, pickup, dropoff, driver.
- **Arrival & Departure** (`Gms/ArrivalDeparture/Index.vue`) — arrival/departure type filter toggle on top, flight no., terminal, datetime, lounge, greeter.

To add a new request-queue module: copy one of the above, update route name, props, and form fields; add a controller + route in `gms.php`; add a `getXxxRequests()` method to `GmsMockData`.

### Events (`Gms/Events/Index.vue`)
Event management page under Setup sidebar section. Card-based grid layout with Create/Edit/Delete functionality.

**Features:**
- Card grid (`.gms-ev-grid`) with responsive layout (auto-fill, minmax 290px)
- Each event card shows: logo emoji, name, subtitle, location, venue(s), dates, status pills
- Current event indicator (maroon border + shadow)
- Action menu: Edit and Delete (vertical ellipsis dropdown)
- "Add new" card with dashed border and hover effect
- Create/Edit modal with full form (name, subtitle, location, **multiselect venues**, dates, logo, active flag)
- Delete confirmation modal

**Multiselect Venues:**
- Venues displayed as toggleable chips (`.chip-pick` wrapper, `.pick-chip` items)
- Each chip has checkbox indicator with check icon when selected
- Stored via many-to-many relationship in `venue_event` pivot table
- Controller uses `attach()` for create and `sync()` for update operations
- Frontend uses `venue_ids` array, backend converts to relationship attachments

**UI Elements:**
- `.gms-ev-card` — individual event card with hover effects
- `.gms-ev-card.managing` — highlights current active event
- `.gms-ev-mk` — event logo/icon (emoji) with gradient background
- `.gms-ev-name` — display font title
- `.gms-ev-meta` — metadata grid (location, venue, dates)
- `.gms-ev-status` — status pills (Active/Inactive + Current)
- `.gms-ev-add` — dashed-border "create new" card
- `.chip-pick` — multiselect chip wrapper
- `.pick-chip` — individual venue chip (`.on` when selected)

Props: `events` (all events from DB), `event` (current event), `availableVenues` (list of venues).
Local state: `localEvents` (reactive copy), `eventModal`, `editingEvent`, `deleteModal`, `deletingId`, `actionsMenuOpen`.

**Database:** Fully wired to `events` and `venues` tables with many-to-many relationship via `venue_event` pivot table. CRUD operations persist via `GmsEventsController` → `Event` model. Venue relationships managed via `attach()` and `sync()` methods.

### Matches (`Gms/Matches/Index.vue`)
Match fixture management page under Setup sidebar section. Card-based grid layout with Create/Edit/Delete functionality.

**Features:**
- **Stats strip:** 4 stat cards showing total matches, capacity, seats assigned, and fill percentage
- Card grid (`.gms-match-grid`) with responsive layout (auto-fill, minmax 320px)
- Each match card shows: stage badge, team names with optional country codes, date/time/venue, seat capacity status
- Action menu: Edit and Delete (vertical ellipsis dropdown)
- "Add new" card with dashed border and hover effect
- Create/Edit modal with full form (name, stage code/label, venue, home/away teams with codes, date, kickoff, stage display)
- Delete confirmation modal
- **Mini status bar:** Shows seats assigned (total - left) vs total with visual progress bar

**UI Elements:**
- `.gms-stats-grid` — stats container grid
- `.gms-stat-card` — individual stat card with label/value/subtitle
- `.gms-match-card` — individual match card with hover effects
- `.gms-match-stage` — stage badge (Opening, Group B, QF, SF, Final)
- `.gms-match-teams` — flex layout for home vs away teams
- `.gms-match-flag` — country code chip (e.g., "QA", "JP")
- `.gms-match-vs` — centered "vs" separator
- `.gms-match-meta` — metadata grid (date, time, venue icons)
- `.gms-match-status` — capacity status section with progress bar
- `.gms-match-bar` — progress bar background
- `.gms-match-bar-fill` — maroon fill showing percentage assigned
- `.gms-match-add` — dashed-border "create new" card

Props: `matches` (array from `GmsMockData::getMatches()`), `venues` (for venue dropdown), `event` (current event).
Local state: `localMatches` (reactive copy), `matchModal`, `editingMatch`, `deleteModal`, `deletingId`, `actionsMenuOpen`, `venueMap` (computed), `matchStats` (computed).

**Database:** Currently using mock data from `GmsMockData::getMatches()`. CRUD operations stubbed via `GmsMatchesController`. Ready for DB wiring with `matches` table migration.

### Groups (`Gms/Groups/Index.vue`)
Group management page under Setup sidebar section. Card-based grid layout with Create/Edit/Delete functionality.

**Features:**
- **Stats strip:** 3 stat cards showing total groups, total guests across groups, and average group size
- Card grid (`.gms-groups-grid`) with responsive layout (auto-fill, minmax 280px)
- Each group card shows: icon, short name, full label, guest count, group ID
- Action menu: Edit and Delete (vertical ellipsis dropdown)
- "Add new" card with dashed border and hover effect
- Create/Edit modal with form (ID, short name, full label)
- Delete confirmation modal with validation (prevents deletion of groups with guests)

**UI Elements:**
- `.gms-groups-grid` — groups container grid
- `.gms-group-card` — individual group card with hover effects
- `.gms-group-icon` — group icon with maroon background
- `.gms-group-name` — display font title (short name)
- `.gms-group-label` — full descriptive label
- `.gms-group-meta` — metadata row (guest count, group ID)
- `.gms-group-mi` — metadata item with icon
- `.gms-group-add` — dashed-border "create new" card

Props: `groups` (array from `Group::withCount('guests')`), `event` (current event).
Local state: `localGroups` (reactive copy), `groupModal`, `editingGroup`, `deleteModal`, `deletingId`, `actionsMenuOpen`.

**Database:** Fully wired to `groups` table. CRUD operations persist via `GmsGroupsController` → `Group` model. Model includes `guests()` relationship and prevents deletion of groups with assigned guests.

---

## Design system (`resources/css/gms.css`)

Imported in `resources/js/app.js`. CSS custom properties on `:root` — always use `--gms-*` tokens, never hardcode colors.

Key tokens:
```css
--gms-maroon: #8a1f3d      /* primary brand / Qatar maroon */
--gms-espresso: #241d1b    /* sidebar background */
--gms-gold: #c4973a        /* accent gold */
--gms-bg: #f5f0eb          /* warm page background */
--gms-surface: #ffffff
--gms-text: #1a1210
--gms-text-2: #6b5c53      /* secondary text */
--gms-text-3: #a09488      /* muted / labels */
--gms-border: rgba(26,18,16,0.10)
--gms-sidebar-w: 240px
--gms-topbar-h: 56px
--gms-font-display: 'Instrument Serif', serif
--gms-font-ui: 'Hanken Grotesk', sans-serif
--gms-font-mono: 'IBM Plex Mono', monospace
```

Utility classes: `.gms-btn`, `.gms-btn-primary/ghost/danger`, `.gms-btn-sm/lg/icon`, `.gms-table`, `.gms-card`, `.gms-pill`, `.gms-pill.maroon`, `.gms-pill.good`, `.gms-avatar`, `.gms-search-input`, `.gms-input`, `.gms-select`, `.gms-field`, `.gms-label`, `.gms-form-grid`, `.gms-toolbar`, `.gms-filter-btn`, `.gms-tabs`, `.gms-tab`, `.gms-stat-card`, `.gms-detail-row`, `.gms-empty`, `.gms-section-title`, `.gms-view`, `.gms-view-header`, `.gms-view-title`.

Event card classes: `.gms-ev-grid`, `.gms-ev-card`, `.gms-ev-card.managing`, `.gms-ev-mk`, `.gms-ev-name`, `.gms-ev-subtitle`, `.gms-ev-meta`, `.gms-ev-mi`, `.gms-ev-status`, `.gms-ev-add`.

Match card classes: `.gms-match-grid`, `.gms-match-card`, `.gms-match-stage`, `.gms-match-teams`, `.gms-match-flag`, `.gms-match-name`, `.gms-match-vs`, `.gms-match-meta`, `.gms-match-mi`, `.gms-match-status`, `.gms-match-bar`, `.gms-match-bar-fill`, `.gms-match-add`.

Multiselect chip classes: `.chip-pick` (wrapper), `.pick-chip` (chip button), `.pick-chip.on` (selected state), `.pick-chip-check` (checkbox indicator).

Seat classes: `.gms-seat.available/assigned/reserved/ticket/hidden/selected`, `.gms-seat-block`, `.gms-seat-row`.

---

## Seating model

Seats: `{id, block, blockLabel, blockColor, row, rowLabel, col, status, guestId, resLabel, hidden}`.
Status ∈ `available | reserved | assigned | ticket`. `hidden:true` seats are excluded from stats and render as faint dashed placeholders.

Seating templates: `{id, venueId, name, blocks[]}`. Each block: `{id, label, color, rowLabels[], seatsPerRow}`.
`GmsMockData::seatsFromTemplate($tpl)` generates the flat seat array from a template's blocks.

Each match owns its own seat instance in `matchSeats[matchId]` — assignments never bleed between matches. Matches that haven't picked a template show the TemplatePicker.

---

## Conventions

- **No images.** Avatars are CSS-colored initials (`GmsAvatar`). Icons are inline SVG (`GmsIcon`).
- **Optimistic UI.** Mutate a local `ref` copy first, then fire the Inertia/axios call. On error, `toast('…', 'error')`.
- **Prop mutations forbidden.** Always clone props into a local `ref`: `const localX = ref(props.x.map(i => ({...i})))`.
- **`route()` helper** — available globally via `ZiggyVue` (already registered in `app.js`). TypeScript declaration added to `resources/js/shims.d.ts`.
- **Layout slot** — GMS pages use `defineOptions({ layout: GmsLayout })`. Do **not** use `<template #breadcrumb>` in page components — the layout derives breadcrumbs from the URL automatically.
- **Toast** — call `inject('toast')` at the top of any `<script setup>` inside a GMS page. The provider is in `GmsLayout`.
- **Existing app** — GMS coexists with the Bootstrap/Skote app. The `app.js` entry point is shared; GMS CSS is appended without overriding Bootstrap tokens. Never prefix GMS classes as `gms-*` in the existing Skote pages.
