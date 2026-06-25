# Flight Request Form - Testing Guide

## ✅ What Changed

Added **return flight fields** to the guest portal flight request form:

### Frontend (`Portal/Dashboard.vue`)
- **Trip Type Selector**: Toggle between "Round Trip" and "One-Way"
- **Outbound Flight Section**: Departure city, arrival city, date, time
- **Return Flight Section**: Return date, return time (only shown for round trips)
- **Travel Details Section**: Cabin class, passengers, special requests

### Backend (`PortalServiceController.php`)
- Validates `trip_type` (one_way or round_trip)
- Validates `return_date` (required if round trip, must be after departure date)
- Validates `return_time` (optional)
- Creates **Inbound leg** automatically for round-trip requests

---

## 🧪 Test Steps

### Test 1: Round-Trip Flight Request

1. Open portal: `https://vue-gms.test/portal/dashboard/1?expires=...`
2. Click **"Request Flight"** button
3. Select **"Round Trip"** (default)
4. Fill outbound flight:
   - Departure City: `London`
   - Arrival City: `Doha`
   - Departure Date: `2026-07-15`
   - Preferred Time: `14:00`
5. Fill return flight:
   - Return Date: `2026-07-22`
   - Preferred Time: `09:00`
6. Fill travel details:
   - Cabin Class: `Business`
   - Passengers: `2`
   - Special Requests: `Vegetarian meals for both passengers`
7. Click **"Submit Request"**

**Expected Result:**
- ✅ Green toast: "Flight request submitted successfully"
- ✅ Modal closes
- ✅ In GMS → Flights: New request `FL-002` appears with 2 legs (Outbound + Inbound)

### Test 2: One-Way Flight Request

1. Click **"Request Flight"**
2. Select **"One-Way"**
3. Return flight section **disappears**
4. Fill outbound flight only
5. Submit

**Expected Result:**
- ✅ Green toast: "Flight request submitted successfully"
- ✅ In GMS → Flights: New request with 1 leg (Outbound only)

### Test 3: Validation - Missing Return Date

1. Select **"Round Trip"**
2. Fill outbound flight
3. **Skip** return date
4. Try to submit

**Expected Result:**
- ❌ Red toast with validation error
- Form highlights return date field

### Test 4: Validation - Return Date Before Departure

1. Select **"Round Trip"**
2. Departure Date: `2026-07-20`
3. Return Date: `2026-07-18` (before departure)
4. Submit

**Expected Result:**
- ❌ Validation error: "Return date must be after departure date"

---

## 📊 Database Storage

**Flight Request:**
```
code: FL-003
status: new
pax: 2
source: portal
initiated_by: guest
```

**Outbound Leg:**
```
dir: Outbound
from_city: London
to_city: Doha
date: 2026-07-15
dep: 14:00
cls: Business
sort: 1
```

**Inbound Leg (if round trip):**
```
dir: Inbound
from_city: Doha
to_city: London
date: 2026-07-22
dep: 09:00
cls: Business
sort: 2
```

---

## 🎨 UI Improvements

- **Trip type toggle buttons** styled to match portal design
- **Section headers** ("Outbound Flight", "Return Flight", "Travel Details") for clarity
- **Conditional rendering** of return fields based on trip type
- **Form validation** prevents invalid date ranges
- **Loading states** on submit button ("Submitting...")

---

## 🔍 Verify in GMS Admin

After submitting from portal:

1. Login to GMS admin
2. Navigate to **Modules → Flights**
3. Look for new request with **globe badge** (portal source)
4. Click to view details:
   - Should show 2 legs for round trip
   - Should show 1 leg for one-way
   - Guest name and reference should match portal user

---

## ✨ Next Steps (Optional)

Consider adding:
- Contact phone number field
- Airline preference dropdown
- Frequent flyer number field
- Companion names (if passengers > 1)
- Departure airport selector (for cities with multiple airports)
