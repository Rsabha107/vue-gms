# Event Selector Setup

## Database Setup

Run these commands to set up the events table:

```bash
# Run the migration to create the events table
php artisan migrate

# Seed the events table with sample data
php artisan db:seed --class=EventSeeder
```

## Features Implemented

1. **Events Database Table** - Created migration for `events` table with fields:
   - name, subtitle, location, venue
   - date_start, date_end
   - logo, active_flag
   - timestamps

2. **Event Model** - Created `App\Models\Event` with:
   - Formatted dates accessor
   - Active scope for filtering active events

3. **Event Seeder** - Added 3 sample events:
   - Doha Cup '26
   - Arabian Gulf Championship
   - Diplomatic Summit 2026

4. **Event Selector** - Sidebar now shows a dropdown to switch between events:
   - Click on current event to open dropdown
   - Select any event to switch
   - Current event is highlighted with checkmark
   - Event selection is stored in session

5. **Event Switching** - New route and controller:
   - POST `/gms/events/switch`
   - Stores selected event in session
   - All GMS pages automatically use the selected event

## Usage

1. Click on the event pill in the sidebar
2. A dropdown will appear showing all active events
3. Click on any event to switch
4. The page will reload with the new event context
5. All GMS views will now show data for the selected event

## Technical Details

- Events are shared globally via `Inertia::share()` in AppServiceProvider
- Current event is determined by session storage
- Event switching uses Inertia.js form submission for seamless UX
- Toast notification confirms successful event switch
- Dropdown closes automatically after selection
