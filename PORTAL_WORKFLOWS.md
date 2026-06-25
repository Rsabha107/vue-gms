# Guest Portal Workflows & User Journeys

Complete workflows and user scenarios for the GMS Guest Portal system, documenting the hybrid approach that combines protocol team control with optional guest self-service.

---

## Overview

The portal supports three service modes:
- **White Glove** (default) — Protocol team handles everything
- **Portal** — Guest submits own requests via portal
- **Hybrid** — Mix of both approaches per guest/service

---

## Core Workflows

### Workflow A: Traditional White Glove (No Portal)

**Day 1: Invitation sent**
```
Protocol Officer → Invitations → Click "Invite" → Select matches → Send email
  ↓
Guest receives invitation email with RSVP link
  ↓
Guest clicks RSVP, accepts matches
  ↓
Status: "Accepted" in GMS
```

**Day 2-3: Protocol team creates service requests**
```
Protocol Officer → Flights → "New Request" → Fill form → Assign to guest
Protocol Officer → Accommodation → "New Request" → Fill form → Assign to guest
Protocol Officer → Transport → "New Request" → Fill form → Assign to guest
  ↓
All requests show as "Confirmed" in guest's record
```

**No portal access needed** — Guest receives confirmation emails for each service.

---

### Workflow B: Portal-First (Guest Self-Service)

**Day 1: Portal link sent**
```
Protocol Officer → Invitations → ⋮ menu → "Send portal link"
  ↓
Email queued with signed URL (72-hour expiry)
  ↓
Guest receives "Your Guest Portal Access" email
```

**Day 2: Guest accesses portal**
```
Guest clicks portal link in email
  ↓
Signed URL validated → Portal dashboard loads
  ↓
Guest sees:
  - Invitation status (Invited/Accepted/Confirmed)
  - Match schedule (dates, teams, venues)
  - Service requests overview (empty or pre-filled)
  - Contact information
```

**Day 3: Guest submits service requests** *(Future feature)*
```
Guest → "Request Flight" → Fill form → Submit
Guest → "Request Accommodation" → Fill form → Submit
Guest → "Request Transport" → Fill form → Submit
  ↓
Requests appear in GMS with source: "portal", initiated_by: "guest"
  ↓
Protocol Officer reviews → Confirms or modifies → Guest notified
```

**Current state:** Portal is read-only. Service request submission forms are planned for future release.

---

### Workflow C: Hybrid Approach (Recommended)

**Day 1: Initial invitation**
```
Protocol Officer → Invitations → "Invite" → Send RSVP
  ↓
Guest accepts via RSVP link
  ↓
Status: "Accepted"
```

**Day 2: Protocol team pre-fills critical services**
```
Protocol Officer creates flight request (because of diplomatic clearances)
Protocol Officer creates accommodation request (VIP suite reserved)
  ↓
Requests marked as source: "manual", initiated_by: "team"
```

**Day 3: Portal access granted**
```
Protocol Officer → Invitations → ⋮ menu → "Send portal link"
  ↓
Guest logs into portal
  ↓
Guest sees:
  - Flight: "Confirmed" (pre-arranged by protocol)
  - Accommodation: "Confirmed" (pre-arranged)
  - Transport: "Not requested" (guest can submit via future portal form)
  - Arrival & Departure: Empty (guest can submit preferences)
```

**Day 4: Guest requests additional services** *(Future)*
```
Guest → Portal → "Request ground transport for match day"
  ↓
Request appears in GMS → Protocol officer reviews → Confirms
  ↓
Guest sees updated status in portal
```

**Benefit:** Protocol team maintains control over critical services while enabling guest autonomy for preferences.

---

## User Journey: Guest Experience

### Pre-Event (4 weeks before)

**Week 1: Invitation received**
- Email: "You're invited to Doha Cup '26"
- CTA: "Respond to Invitation" → RSVP page
- Guest selects which matches to attend
- Status: `invited` → `accepted`

**Week 2: Portal access sent**
- Email: "Your Guest Portal Access"
- CTA: "Access Your Portal" → Signed URL
- Guest bookmarks portal link
- Status tracked: `portal_sent_at`, `portal_accessed_at`

**Week 3: Services confirmed**
- Guest checks portal daily for updates
- Sees flight details, hotel confirmation
- Downloads itinerary PDF *(future)*
- Receives confirmation emails

**Week 4: Final preparations**
- Portal shows countdown to event
- Last-minute changes handled via portal *(future)* or email/phone
- QR code for stadium access *(future)*

### During Event

**Match Day:**
- Guest checks portal for transport pickup time
- Views seat assignment and match details
- Contacts protocol officer via portal chat *(future)*

### Post-Event

**Portal expires:**
- 72 hours after last access
- Archive available via protocol team on request

---

## User Journey: Protocol Officer Experience

### Initial Setup (One-time)

**Enable portal:**
```
Settings → Modules → Toggle "Guest Self-Service Portal" ON
Select authentication mode: "Signed URL (Basic)"
Click "Save"
```

### Per-Event Workflow

**1. Create event roster**
```
Guests → Select guests → "Add N to {event}"
  ↓
Roster shows all guests for this event
Status: "Not invited" (on roster, no invite sent yet)
```

**2. Send invitations**
```
Invitations → Click "Invite" on guest row → Select matches
Choose email template → Click "Send Invitation"
  ↓
Status: "Invited" → awaiting RSVP
```

**3. Track RSVPs**
```
Invitations → Status filter: "Invited"
  ↓
Guest responds → Auto-updates to "Accepted"
  ↓
Or: ⋮ menu → "Accept on behalf" (manual override)
```

**4. Send portal links** *(optional per guest)*
```
Invitations → ⋮ menu → "Send portal link"
  ↓
Email queued (check terminal: "Processed: PortalAccessMail")
  ↓
Track: portal_sent_at, portal_accessed_at timestamps
```

**5. Manage services**
```
Two approaches:

A) White Glove (default):
   Flights → "New Request" → Assign to guest → Confirm
   Guest never sees portal, just confirmation emails

B) Hybrid:
   Critical services → Created by protocol (source: "manual")
   Optional services → Guest submits via portal (source: "portal")
   Protocol reviews all, confirms/modifies
```

**6. Monitor portal usage**
```
Invitations → Services tab
  ↓
See service request status per guest:
  - Flight: Confirmed (manual)
  - Accommodation: Confirmed (manual)
  - Transport: Pending (portal) ← Guest submitted via portal
  - A&D: Not requested
```

---

## State Transitions

### Guest Invitation Status

```
not_invited (on roster, no email sent)
  ↓ [Send invitation]
invited (awaiting RSVP)
  ↓ [Guest clicks "Accept" or officer clicks "Accept on behalf"]
accepted
  ↓ [Officer clicks "Mark confirmed"]
confirmed (attendance verified)

Alternative paths:
invited → declined (guest clicked "Decline")
invited → not_invited (officer clicks "Reset to not invited")
```

### Portal Access Status

```
(no portal access)
  ↓ [Officer sends portal link]
portal_sent_at: timestamp
  ↓ [Guest clicks link]
portal_accessed_at: timestamp (first access)
portal_last_accessed_at: timestamp (every access)
  ↓ [72 hours elapsed]
portal_token_expires_at: expired
```

### Service Request Status

```
(no request)
  ↓ [Guest submits via portal OR officer creates]
new/pending
  ↓ [Officer reviews and confirms]
confirmed
  ↓ [Changes needed]
change (pending modification)
  ↓ [Cancelled by officer or guest]
cancelled
```

---

## Decision Trees

### When to send portal link?

**YES — Send portal link if:**
- Guest is tech-savvy and prefers self-service
- Multiple services need guest input (dietary, mobility, preferences)
- Event is far in future (guest wants to check status periodically)
- Guest is VIP who wants autonomy and privacy

**NO — Don't send portal link if:**
- Guest expects full white-glove service
- Protocol has already arranged everything
- Guest is elderly/non-technical
- Event is very soon (< 48 hours) — not enough time for portal workflow

**MAYBE — Hybrid approach:**
- Send portal link AFTER critical services confirmed
- Use portal for read-only status tracking
- Guest can email/call for changes (portal forms not yet available)

### Which authentication mode?

**Signed URL (current):**
- ✅ No password needed
- ✅ Fast access
- ✅ Works for all guests
- ⚠️ Link can be shared (but expires in 72h)

**Magic Link (future):**
- ✅ Email verification required each session
- ✅ More secure than signed URL
- ⚠️ Requires guest to check email each time

**Full Authentication (future):**
- ✅ Password-based, most secure
- ✅ Long-term access
- ⚠️ Requires guest to remember password
- ⚠️ Password reset flow needed

---

## Email Triggers

### Invitation Email
**Trigger:** Officer clicks "Invite" on guest row  
**Template:** `guest-invitation.blade.php`  
**Contains:** Event details, match list, RSVP link  
**Queue:** Yes (sent via `Mail::queue()`)

### Portal Access Email
**Trigger:** Officer clicks "Send portal link" in ⋮ menu  
**Template:** `portal-access.blade.php`  
**Contains:** Portal URL (signed), event info, expiry warning  
**Queue:** Yes (sent via `Mail::queue()`)  
**Expiry:** 72 hours default (configurable up to 720h)

### Service Confirmation Email *(future)*
**Trigger:** Protocol officer confirms flight/accommodation/transport request  
**Contains:** Service details, reference number, contact info  
**Queue:** Yes

### Portal Expiry Reminder *(future)*
**Trigger:** 24 hours before portal link expires  
**Contains:** Re-access link with renewed expiry  
**Queue:** Yes

---

## Database Tracking

### Portal Analytics *(future)*

Track in `invitations` table:
- `portal_sent_at` — When link was sent
- `portal_accessed_at` — First access timestamp
- `portal_last_accessed_at` — Most recent access
- `portal_access_count` *(add column)* — Number of logins
- `portal_token_expires_at` — Link expiry

**Query examples:**
```sql
-- Guests who received portal but never accessed
SELECT * FROM invitations 
WHERE portal_sent_at IS NOT NULL 
AND portal_accessed_at IS NULL;

-- Portal usage rate
SELECT 
  COUNT(*) as total_sent,
  COUNT(portal_accessed_at) as accessed,
  ROUND(COUNT(portal_accessed_at) * 100.0 / COUNT(*), 2) as usage_percent
FROM invitations
WHERE portal_sent_at IS NOT NULL;
```

### Service Request Source Tracking

Track in service request tables (`flight_requests`, `accommodation_requests`, etc.):
- `initiated_by` — enum('guest','team')
- `source` — enum('portal','manual','phone','email')
- `assigned_officer_id` — Which protocol officer is handling this

**Query examples:**
```sql
-- How many requests came from portal vs. manual entry?
SELECT source, COUNT(*) as count
FROM flight_requests
GROUP BY source;

-- Officer workload: requests per officer
SELECT u.name, COUNT(fr.id) as flight_requests
FROM users u
LEFT JOIN flight_requests fr ON fr.assigned_officer_id = u.id
GROUP BY u.id, u.name;
```

---

## Future Enhancements

### Phase 1: Read-Only Portal (✅ COMPLETE)
- [x] Portal authentication via signed URLs
- [x] Guest dashboard showing invitation status
- [x] Match schedule display
- [x] Service requests overview (read-only)
- [x] Email template for portal access
- [x] Settings toggle for enabling portal
- [x] "Send portal link" action in Invitations

### Phase 2: Service Request Forms *(next)*
- [ ] Flight request form in portal
- [ ] Accommodation request form
- [ ] Transport request form
- [ ] Arrival & departure preferences form
- [ ] Validation and submission flow
- [ ] Protocol officer review queue

### Phase 3: Interactive Features
- [ ] Real-time status updates (WebSockets)
- [ ] In-portal messaging with protocol officer
- [ ] Document uploads (passport, visa)
- [ ] Itinerary PDF export
- [ ] QR code for stadium access
- [ ] Multi-language support

### Phase 4: Advanced
- [ ] Mobile app (React Native)
- [ ] Push notifications
- [ ] Calendar integration (iCal/Google Calendar)
- [ ] Guest preferences learning (AI-powered)
- [ ] Post-event feedback surveys

---

## Troubleshooting

### Guest can't access portal

**Check:**
1. Portal enabled in Settings → Modules?
2. Guest has email address in GMS?
3. Link expired? (72 hours by default)
4. Guest checking spam folder?
5. Queue worker running? (`php artisan queue:work`)

**Fix:**
- Invitations → ⋮ menu → "Send portal link" (generates new link)
- Check `jobs` table for failed jobs
- Check Laravel logs: `storage/logs/laravel.log`

### Portal shows "403 Forbidden"

**Cause:** Signed URL signature invalid or expired

**Fix:**
- Generate new portal link
- Increase expiry time: Pass `hoursValid` parameter (max 720)
- Check system clock (signature validation is time-sensitive)

### Queue not processing emails

**Symptoms:** 
- "Portal access link queued" toast shows
- Guest never receives email
- Terminal shows no output

**Fix:**
```bash
# Check queue worker is running
php artisan queue:listen

# Check jobs table
SELECT * FROM jobs ORDER BY id DESC LIMIT 10;

# Retry failed jobs
php artisan queue:retry all

# Check mail configuration
php artisan tinker
>>> Mail::to('test@example.com')->send(new \App\Mail\PortalAccessMail(...));
```

### Portal shows old data

**Cause:** Guest viewing cached page

**Fix:**
- Portal uses Inertia.js (no caching by default)
- Guest should hard-refresh (Ctrl+F5)
- Check `portal_last_accessed_at` timestamp in database

---

## Security Considerations

### Signed URL Security

**How it works:**
- Laravel generates HMAC-SHA256 signature from URL + APP_KEY
- Signature included as `signature` query parameter
- `hasValidSignature()` validates on each request
- Tampering invalidates signature

**Protection against:**
- ✅ URL parameter manipulation
- ✅ Link sharing after expiry
- ✅ Replay attacks (expiry + token tracking)

**Vulnerable to:**
- ⚠️ Link sharing within expiry window (guest could share with unauthorized person)
- ⚠️ MITM attacks if not using HTTPS (always use SSL in production)

**Mitigation:**
- Set short expiry (72h default)
- Track `portal_last_accessed_at` to detect unusual activity
- Educate guests not to share links
- Consider magic link mode for higher security

### Data Privacy

**Guest sees only their own data:**
- Portal controller filters by `guest_id` from URL parameter
- No other guest data exposed
- No admin functions accessible from portal

**Audit trail:**
- All portal access logged in `invitations.portal_accessed_at`
- Service requests track `source` and `initiated_by`
- Officer actions logged in service request tables

---

## Testing Scenarios

### Test 1: Happy Path - Portal Access
```
1. Create guest with email address
2. Add guest to event roster
3. Send invitation → guest accepts
4. Enable portal in Settings → Modules
5. Click "Send portal link" in Invitations
6. Check terminal for "Processed: PortalAccessMail"
7. Check guest's email inbox
8. Click portal link in email
9. Verify dashboard loads with guest data
10. Check invitation.portal_accessed_at timestamp
```

### Test 2: Expired Portal Link
```
1. Send portal link with 1-hour expiry (pass hoursValid: 1)
2. Wait 61 minutes
3. Click portal link
4. Verify: "403 This portal link has expired or is invalid"
5. Send new link from Invitations
6. Verify: New link works
```

### Test 3: Guest Without Email
```
1. Create guest without email address
2. Try to send portal link
3. Verify: Error toast "Guest {Name} does not have an email address"
4. Add email to guest profile
5. Try again → Success
```

### Test 4: Queue Worker Down
```
1. Stop queue worker (Ctrl+C in terminal)
2. Send portal link
3. Verify: "Portal access link queued" toast
4. Guest checks email → no email received
5. Check jobs table: `SELECT * FROM jobs;` → 1 pending job
6. Start queue worker: `php artisan queue:work`
7. Watch terminal: Job processes, email sent
8. Guest receives email
```

---

## Performance Considerations

### Email Queue

**Why queue emails?**
- Sending synchronously blocks HTTP request (3-5 seconds per email)
- User waits for SMTP handshake, delivery confirmation
- Portal link generation is instant (signatures are fast)

**Queue configuration:**
- Driver: `database` (jobs table)
- Retry after: 90 seconds
- Max tries: 3
- Timeout: 60 seconds

**Best practices:**
- Always run queue worker in production: `php artisan queue:work --daemon`
- Use Supervisor to auto-restart queue worker
- Monitor failed jobs: `php artisan queue:failed`

### Portal Dashboard Loading

**Data loaded:**
- Guest model (1 query)
- Event model (1 query)
- Invitation with status (1 query + 1 join)
- Matches with venue (1 query + 1 join)
- Service requests: flights, accommodation, transport (3 queries)

**Optimization:**
- Eager load relationships: `with(['tierInfo', 'group', 'venue'])`
- Cache event data (changes rarely)
- Consider Redis for high-traffic events

**Expected load time:** < 500ms

---

## Glossary

**RSVP** — Invitation response (Accept/Decline matches)  
**Signed URL** — Cryptographically authenticated URL with expiry  
**Service Mode** — How guest services are managed (portal/white_glove/hybrid)  
**Queue Worker** — Background process that sends emails asynchronously  
**Protocol Officer** — Staff member managing VIP guests  
**White Glove** — Full-service approach where staff handles everything  
**Roster** — List of guests assigned to an event  
**Portal Token** — Signature in signed URL that prevents tampering
