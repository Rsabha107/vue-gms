<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\AccommodationRequest;
use App\Models\FlightRequest;
use App\Models\Guest;
use App\Models\RoomBlock;
use App\Models\ServiceLevel;
use App\Models\TransportRequest;
use App\Services\Gms\GmsMockData;
use Carbon\Carbon;
use Inertia\Inertia;

class GmsDashboardController extends Controller
{
    private const MODULE_MAP = [
        'T1' => ['flights', 'accomm', 'seating', 'transport', 'arrival'],
        'T2' => ['flights', 'accomm', 'seating', 'transport', 'arrival'],
        'T3' => ['accomm', 'seating', 'transport', 'arrival'],
        'T4' => ['seating', 'transport'],
        'T5' => ['seating'],
    ];

    public function index()
    {
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;
        $tiers = ServiceLevel::orderBy('rank')->get();
        $today = Carbon::today();

        // Countdown
        $eventStart = isset($event['dateStart']) ? Carbon::parse($event['dateStart']) : null;
        $eventEnd = isset($event['dateEnd']) ? Carbon::parse($event['dateEnd']) : null;
        $daysUntil = $eventStart ? max(0, $today->diffInDays($eventStart, false)) : null;

        // Resolve status IDs from invitation_statuses table
        $statusIds = \App\Models\InvitationStatus::pluck('id', 'name');
        $confirmedStatusId = $statusIds['confirmed'] ?? $statusIds['accepted'] ?? null;
        $invitedStatusId = $statusIds['invited'] ?? $statusIds['sent'] ?? null;
        $notInvitedStatusId = $statusIds['not_invited'] ?? null;
        $acceptedStatusId = $statusIds['accepted'] ?? null;

        $confirmedIds = collect([$confirmedStatusId, $acceptedStatusId])->filter()->unique()->toArray();

        // All confirmed guests with service relationships
        $confirmedGuests = Guest::with([
                'tierInfo',
                'flightRequests' => fn($q) => $q->with('status')->where('event_id', $eventId)->where(function ($q2) {
                    $q2->where('source', '!=', 'portal')->orWhere('initiated_by', '!=', 'guest');
                }),
                'accommodationRequests' => fn($q) => $q->with('status')->where('event_id', $eventId)->where(function ($q2) {
                    $q2->where('source', '!=', 'portal')->orWhere('initiated_by', '!=', 'guest');
                }),
                'transportRequests' => fn($q) => $q->with('status')->where('event_id', $eventId)->where(function ($q2) {
                    $q2->where('source', '!=', 'portal')->orWhere('initiated_by', '!=', 'guest');
                }),
                'seats',
                'arrivalDepartureRequests',
            ])
            ->whereHas('events', fn($q) => $q->where('events.id', $eventId)
                ->whereIn('guest_event.status_id', $confirmedIds))
            ->get();

        // All guests on event (any status) for invitation stats
        $allEventGuests = Guest::whereHas('events', fn($q) => $q->where('events.id', $eventId))
            ->with(['events' => fn($q) => $q->where('events.id', $eventId)])
            ->get();

        $invitationStats = [
            'total' => $allEventGuests->count(),
            'confirmed' => $confirmedGuests->count(),
            'pending' => $allEventGuests->filter(fn($g) => in_array($g->events->first()?->pivot?->status_id, [$invitedStatusId, $statusIds['pending'] ?? null]))->count(),
            'notInvited' => $allEventGuests->filter(fn($g) => $g->events->first()?->pivot?->status_id == $notInvitedStatusId)->count(),
        ];

        // ── Module coverage ──
        $moduleCoverage = $this->computeModuleCoverage($confirmedGuests, $eventId);

        // ── Guest readiness ──
        $guestReadiness = $this->computeGuestReadiness($confirmedGuests);

        // ── Service gaps ──
        $serviceGaps = $this->computeServiceGaps($confirmedGuests);

        // ── Alerts ──
        $alerts = $this->computeAlerts($confirmedGuests, $moduleCoverage, $invitationStats, $eventId, $daysUntil, $statusIds);

        // ── Portal pending ──
        $portalPending = [
            'flights' => FlightRequest::where('event_id', $eventId)->where('source', 'portal')->where('initiated_by', 'guest')->whereNull('fulfilled_by_id')->count(),
            'accomm' => AccommodationRequest::where('event_id', $eventId)->where('source', 'portal')->where('initiated_by', 'guest')->whereNull('fulfilled_by_id')->count(),
            'transport' => TransportRequest::where('event_id', $eventId)->where('source', 'portal')->where('initiated_by', 'guest')->whereNull('fulfilled_by_id')->count(),
        ];

        // ── Suggestions ──
        $suggestions = $this->computeSuggestions($moduleCoverage, $portalPending, $daysUntil, $eventId, $confirmedGuests);

        // ── Timeline (next 14 days) ──
        $timeline = $this->computeTimeline($eventId, $today);

        // ── Matches ──
        $matches = array_slice(GmsMockData::getMatches(), 0, 4);

        return Inertia::render('Gms/Dashboard', [
            'event'           => $event,
            'countdown'       => $daysUntil,
            'tiers'           => $tiers,
            'invitationStats' => $invitationStats,
            'moduleCoverage'  => $moduleCoverage,
            'serviceGaps'     => $serviceGaps,
            'alerts'          => $alerts,
            'guestReadiness'  => $guestReadiness,
            'timeline'        => $timeline,
            'suggestions'     => $suggestions,
            'portalPending'   => $portalPending,
            'matches'         => $matches,
        ]);
    }

    private function computeModuleCoverage($confirmedGuests, $eventId): array
    {
        $modules = [
            'flights'   => ['icon' => 'plane',    'name' => 'Flights',            'href' => '/gms/flights'],
            'accomm'    => ['icon' => 'building',  'name' => 'Accommodation',      'href' => '/gms/accommodation'],
            'transport' => ['icon' => 'car',       'name' => 'Transport',          'href' => '/gms/transport'],
            'seating'   => ['icon' => 'grid',      'name' => 'Seating',            'href' => '/gms/seating'],
            'arrival'   => ['icon' => 'arrivals',  'name' => 'Arrival & Dep.',     'href' => '/gms/arrival-departure'],
        ];

        $coverage = [];
        foreach ($modules as $key => $meta) {
            $need = 0;
            $covered = 0;
            $statusBreakdown = ['new' => 0, 'confirmed' => 0, 'pending' => 0, 'change' => 0];

            foreach ($confirmedGuests as $g) {
                $tierModules = self::MODULE_MAP[$g->tier] ?? [];
                if (!in_array($key, $tierModules)) continue;
                $need++;

                $status = $this->guestServiceStatus($g, $key);
                if ($status !== 'missing') {
                    $covered++;
                    if (isset($statusBreakdown[$status])) $statusBreakdown[$status]++;
                }
            }

            $coverage[$key] = array_merge($meta, [
                'need'    => $need,
                'covered' => $covered,
                'pct'     => $need > 0 ? round($covered / $need * 100) : 0,
                'statuses' => $statusBreakdown,
            ]);
        }

        return $coverage;
    }

    private function guestServiceStatus($guest, string $module): string
    {
        switch ($module) {
            case 'flights':
                $req = $guest->flightRequests->filter(fn($f) => ($f->status->name ?? '') !== 'cancelled')->first();
                if (!$req) return 'missing';
                return $req->status->name ?? 'new';
            case 'accomm':
                $req = $guest->accommodationRequests->filter(fn($a) => ($a->status->name ?? '') !== 'cancelled')->first();
                if (!$req) return 'missing';
                return $req->status->name ?? 'new';
            case 'transport':
                $req = $guest->transportRequests->filter(fn($t) => ($t->status->name ?? '') !== 'cancelled')->first();
                if (!$req) return 'missing';
                return $req->status->name ?? 'pending';
            case 'seating':
                return $guest->seats->isNotEmpty() ? 'confirmed' : 'missing';
            case 'arrival':
                $req = $guest->arrivalDepartureRequests->whereNotIn('status', ['cancelled'])->first();
                if (!$req) return 'missing';
                return $req->status ?? 'new';
            default:
                return 'missing';
        }
    }

    private function computeGuestReadiness($confirmedGuests): array
    {
        return $confirmedGuests->map(function ($g) {
            $tierModules = self::MODULE_MAP[$g->tier] ?? [];
            $services = [];
            $required = 0;
            $fulfilled = 0;

            foreach (['flights', 'accomm', 'transport', 'seating', 'arrival'] as $mod) {
                if (!in_array($mod, $tierModules)) {
                    $services[$mod] = 'na';
                    continue;
                }
                $required++;
                $status = $this->guestServiceStatus($g, $mod);
                if ($status === 'missing') {
                    $services[$mod] = 'missing';
                } elseif ($status === 'confirmed') {
                    $services[$mod] = 'confirmed';
                    $fulfilled++;
                } else {
                    $services[$mod] = 'pending';
                    $fulfilled++;
                }
            }

            return [
                'id'        => $g->id,
                'name'      => $g->name,
                'tier'      => $g->tier,
                'tierName'  => $g->tierInfo->name ?? $g->tier,
                'tierColor' => $g->tierInfo->color ?? '#6b7280',
                'services'  => $services,
                'required'  => $required,
                'fulfilled' => $fulfilled,
                'score'     => $required > 0 ? round($fulfilled / $required * 100) : 100,
            ];
        })
        ->sortBy('score')
        ->values()
        ->toArray();
    }

    private function guestSummary($guest): array
    {
        return ['name' => $guest->name, 'tier' => $guest->tier, 'tierName' => $guest->tierInfo->name ?? $guest->tier, 'tierColor' => $guest->tierInfo->color ?? '#6b7280'];
    }

    private function computeServiceGaps($confirmedGuests): array
    {
        $modules = [
            'flights'   => ['icon' => 'plane',    'name' => 'Flights',        'href' => '/gms/flights'],
            'accomm'    => ['icon' => 'building',  'name' => 'Accommodation',  'href' => '/gms/accommodation'],
            'transport' => ['icon' => 'car',       'name' => 'Transport',      'href' => '/gms/transport'],
            'seating'   => ['icon' => 'grid',      'name' => 'Seating',        'href' => '/gms/seating'],
        ];

        $gaps = [];
        $totalGaps = 0;
        $guestsWithGaps = collect();

        foreach ($modules as $key => $meta) {
            $missing = $confirmedGuests->filter(fn($g) => in_array($key, self::MODULE_MAP[$g->tier] ?? []) && $this->guestServiceStatus($g, $key) === 'missing');
            $gaps[$key] = array_merge($meta, [
                'count'  => $missing->count(),
                'guests' => $missing->map(fn($g) => $this->guestSummary($g))->values()->toArray(),
            ]);
            $totalGaps += $missing->count();
            $guestsWithGaps = $guestsWithGaps->merge($missing);
        }

        return [
            'totalGaps'       => $totalGaps,
            'uniqueGuests'    => $guestsWithGaps->unique('id')->count(),
            'modules'         => $gaps,
        ];
    }

    private function computeAlerts($confirmedGuests, $moduleCoverage, $invitationStats, $eventId, $daysUntil, $statusIds = null): array
    {
        $alerts = [];
        $isUrgent = $daysUntil !== null && $daysUntil <= 30;

        // Portal pending
        $portalFlights = FlightRequest::where('event_id', $eventId)->where('source', 'portal')->where('initiated_by', 'guest')->whereNull('fulfilled_by_id')->with(['guest.tierInfo'])->get();
        $portalAccomm = AccommodationRequest::where('event_id', $eventId)->where('source', 'portal')->where('initiated_by', 'guest')->whereNull('fulfilled_by_id')->with(['guest.tierInfo'])->get();
        $portalTransport = TransportRequest::where('event_id', $eventId)->where('source', 'portal')->where('initiated_by', 'guest')->whereNull('fulfilled_by_id')->with(['guest.tierInfo'])->get();
        $portalAll = $portalFlights->merge($portalAccomm)->merge($portalTransport);
        if ($portalAll->isNotEmpty()) {
            $grouped = [];
            foreach ($portalAll as $r) {
                $gName = $r->guest->name ?? 'Unknown';
                if (!isset($grouped[$gName])) {
                    $grouped[$gName] = [
                        'name'      => $gName,
                        'tier'      => $r->guest->tier ?? '',
                        'tierName'  => $r->guest->tierInfo->name ?? '',
                        'tierColor' => $r->guest->tierInfo->color ?? '#6b7280',
                        'items'     => [],
                    ];
                }
                $type = $r instanceof FlightRequest ? 'Flight' : ($r instanceof AccommodationRequest ? 'Accommodation' : 'Transport');
                $icon = $r instanceof FlightRequest ? 'plane' : ($r instanceof AccommodationRequest ? 'building' : 'car');
                $href = $r instanceof FlightRequest ? '/gms/flights' : ($r instanceof AccommodationRequest ? '/gms/accommodation' : '/gms/transport');
                $grouped[$gName]['items'][] = [
                    'type'      => $type,
                    'icon'      => $icon,
                    'code'      => $r->code,
                    'submitted' => $r->created_at?->format('d M'),
                    'href'      => $href,
                ];
            }
            $alerts[] = [
                'severity' => 'high',
                'icon'     => 'globe',
                'message'  => $portalAll->count() . " guest portal request" . ($portalAll->count() > 1 ? 's' : '') . " awaiting action",
                'count'    => $portalAll->count(),
                'names'    => $portalAll->take(3)->map(fn($r) => $r->guest->name ?? '')->unique()->toArray(),
                'grouped'  => true,
                'guests'   => array_values($grouped),
                'href'     => '/gms/flights',
                'cta'      => 'Review requests',
            ];
        }

        // Pending invitations
        $invitedId = $statusIds['invited'] ?? $statusIds['sent'] ?? null;
        $notInvitedId = $statusIds['not_invited'] ?? null;
        $pendingId = $statusIds['pending'] ?? null;
        $pendingInvIds = collect([$invitedId, $notInvitedId, $pendingId])->filter()->toArray();
        $pendingInvGuests = $pendingInvIds ? Guest::whereHas('events', fn($q) => $q->where('events.id', $eventId)
                ->whereIn('guest_event.status_id', $pendingInvIds))
            ->with('tierInfo')
            ->get() : collect();
        if ($pendingInvGuests->isNotEmpty()) {
            $invStatusLabels = collect($pendingInvIds)->mapWithKeys(fn($id) => [$id => \App\Models\InvitationStatus::find($id)?->name ?? 'pending'])->toArray();
            $alerts[] = [
                'severity' => $isUrgent ? 'high' : 'medium',
                'icon'     => 'mail',
                'message'  => $pendingInvGuests->count() . " invitation" . ($pendingInvGuests->count() > 1 ? 's' : '') . " pending response",
                'count'    => $pendingInvGuests->count(),
                'names'    => $pendingInvGuests->take(3)->pluck('name')->toArray(),
                'grouped'  => true,
                'guests'   => $pendingInvGuests->map(function ($g) use ($eventId, $invStatusLabels) {
                    $pivot = $g->events->firstWhere('id', $eventId)?->pivot;
                    $statusLabel = $pivot ? ($invStatusLabels[$pivot->status_id] ?? 'pending') : 'pending';
                    return [
                        'name'      => $g->name,
                        'tier'      => $g->tier,
                        'tierName'  => $g->tierInfo->name ?? $g->tier,
                        'tierColor' => $g->tierInfo->color ?? '#6b7280',
                        'items'     => [['type' => ucfirst($statusLabel), 'icon' => 'mail', 'code' => '', 'href' => '/gms/invitations']],
                    ];
                })->values()->toArray(),
                'href'     => '/gms/invitations',
                'cta'      => 'View invitations',
            ];
        }

        // Unconfirmed bookings
        $unconfirmedRaw = collect();

        // Flights: status is now via invitation_statuses FK
        $flightStatusIds = \App\Models\InvitationStatus::whereIn('name', ['new', 'change'])->pluck('id');
        $flightItems = FlightRequest::where('event_id', $eventId)
            ->whereIn('status_id', $flightStatusIds)
            ->where(function ($q) { $q->where('source', '!=', 'portal')->orWhere('initiated_by', '!=', 'guest'); })
            ->with(['guest.tierInfo', 'status'])
            ->get()
            ->map(fn($r) => [
                'guestName' => $r->guest->name ?? '', 'tier' => $r->guest->tier ?? '',
                'tierName' => $r->guest->tierInfo->name ?? '', 'tierColor' => $r->guest->tierInfo->color ?? '#6b7280',
                'type' => 'Flight', 'icon' => 'plane', 'code' => $r->code,
                'status' => $r->status->name ?? 'new', 'href' => '/gms/flights',
            ]);
        $unconfirmedRaw = $unconfirmedRaw->merge($flightItems);

        // Accommodation: now uses integer status_id FK
        $accommStatusIds = \App\Models\InvitationStatus::whereIn('name', ['new', 'change'])->pluck('id');
        $accommItems = AccommodationRequest::where('event_id', $eventId)
            ->whereIn('status_id', $accommStatusIds)
            ->where(function ($q) { $q->where('source', '!=', 'portal')->orWhere('initiated_by', '!=', 'guest'); })
            ->with(['guest.tierInfo', 'status'])
            ->get()
            ->map(fn($r) => [
                'guestName' => $r->guest->name ?? '', 'tier' => $r->guest->tier ?? '',
                'tierName' => $r->guest->tierInfo->name ?? '', 'tierColor' => $r->guest->tierInfo->color ?? '#6b7280',
                'type' => 'Accommodation', 'icon' => 'building', 'code' => $r->code,
                'status' => $r->status->name ?? 'new', 'href' => '/gms/accommodation',
            ]);
        $unconfirmedRaw = $unconfirmedRaw->merge($accommItems);

        // Transport: now uses integer status_id FK
        $transportStatusIds = \App\Models\InvitationStatus::whereIn('name', ['pending'])->pluck('id');
        $transportItems = TransportRequest::where('event_id', $eventId)
            ->whereIn('status_id', $transportStatusIds)
            ->where(function ($q) { $q->where('source', '!=', 'portal')->orWhere('initiated_by', '!=', 'guest'); })
            ->with(['guest.tierInfo', 'status'])
            ->get()
            ->map(fn($r) => [
                'guestName' => $r->guest->name ?? '', 'tier' => $r->guest->tier ?? '',
                'tierName' => $r->guest->tierInfo->name ?? '', 'tierColor' => $r->guest->tierInfo->color ?? '#6b7280',
                'type' => 'Transport', 'icon' => 'car', 'code' => $r->code,
                'status' => $r->status->name ?? 'pending', 'href' => '/gms/transport',
            ]);
        $unconfirmedRaw = $unconfirmedRaw->merge($transportItems);
        if ($unconfirmedRaw->isNotEmpty()) {
            $grouped = [];
            foreach ($unconfirmedRaw as $item) {
                $name = $item['guestName'];
                if (!isset($grouped[$name])) {
                    $grouped[$name] = [
                        'name'      => $name,
                        'tier'      => $item['tier'],
                        'tierName'  => $item['tierName'],
                        'tierColor' => $item['tierColor'],
                        'items'     => [],
                    ];
                }
                $grouped[$name]['items'][] = [
                    'type'   => $item['type'],
                    'icon'   => $item['icon'],
                    'code'   => $item['code'],
                    'status' => $item['status'],
                    'href'   => $item['href'],
                ];
            }
            $alerts[] = [
                'severity' => 'low',
                'icon'     => 'clock',
                'message'  => $unconfirmedRaw->count() . " booking" . ($unconfirmedRaw->count() > 1 ? 's' : '') . " awaiting confirmation",
                'count'    => $unconfirmedRaw->count(),
                'names'    => $unconfirmedRaw->take(3)->pluck('guestName')->unique()->toArray(),
                'grouped'  => true,
                'guests'   => array_values($grouped),
                'href'     => '/gms/flights',
                'cta'      => 'Review',
            ];
        }

        // Sort by severity
        $order = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($alerts, fn($a, $b) => ($order[$a['severity']] ?? 4) <=> ($order[$b['severity']] ?? 4));

        return array_slice($alerts, 0, 6);
    }

    private function computeSuggestions($moduleCoverage, $portalPending, $daysUntil, $eventId, $confirmedGuests): array
    {
        $suggestions = [];

        // Missing flights for high-tier guests
        $platinumMissing = $confirmedGuests->filter(fn($g) => in_array($g->tier, ['T1', 'T2']) && $this->guestServiceStatus($g, 'flights') === 'missing')->count();
        if ($platinumMissing > 0 && $daysUntil !== null) {
            $suggestions[] = ['icon' => 'alert-triangle', 'text' => "{$platinumMissing} Platinum guest" . ($platinumMissing > 1 ? 's' : '') . " still have no flights — event is in {$daysUntil} days", 'severity' => 'warn'];
        }

        // Room block cutoff approaching
        $urgentBlocks = RoomBlock::where('event_id', $eventId)
            ->whereRaw('picked_up < allotment')
            ->whereNotNull('cutoff_date')
            ->where('cutoff_date', '<=', Carbon::today()->addDays(14))
            ->with('hotel')
            ->get();
        foreach ($urgentBlocks->take(2) as $block) {
            $daysLeft = Carbon::today()->diffInDays(Carbon::parse($block->cutoff_date), false);
            $remaining = $block->allotment - $block->picked_up;
            $hotelName = $block->hotel->name ?? $block->hotel_name;
            $suggestions[] = ['icon' => 'building', 'text' => "Room block at {$hotelName} has {$remaining} rooms left — cutoff in {$daysLeft} days", 'severity' => 'info'];
        }

        // Portal requests pending
        $totalPortal = array_sum($portalPending);
        if ($totalPortal > 0) {
            $suggestions[] = ['icon' => 'globe', 'text' => "{$totalPortal} portal request" . ($totalPortal > 1 ? 's' : '') . " awaiting action from your team", 'severity' => 'info'];
        }

        // All green for a tier
        foreach (['T3' => 'Gold', 'T4' => 'Silver'] as $tierId => $tierName) {
            $tierGuests = $confirmedGuests->filter(fn($g) => $g->tier === $tierId);
            if ($tierGuests->isEmpty()) continue;
            $allCovered = $tierGuests->every(function ($g) {
                $mods = self::MODULE_MAP[$g->tier] ?? [];
                foreach ($mods as $mod) {
                    if ($this->guestServiceStatus($g, $mod) === 'missing') return false;
                }
                return true;
            });
            if ($allCovered) {
                $suggestions[] = ['icon' => 'check', 'text' => "All {$tierName}-tier guests are fully serviced", 'severity' => 'good'];
            }
        }

        // Coverage milestone
        $totalNeed = collect($moduleCoverage)->sum('need');
        $totalCovered = collect($moduleCoverage)->sum('covered');
        if ($totalNeed > 0) {
            $overallPct = round($totalCovered / $totalNeed * 100);
            if ($overallPct >= 90 && $overallPct < 100) {
                $suggestions[] = ['icon' => 'check', 'text' => "Overall service coverage is at {$overallPct}% — nearly there", 'severity' => 'good'];
            }
        }

        return array_slice($suggestions, 0, 5);
    }

    private function computeTimeline($eventId, $today): array
    {
        $endWindow = $today->copy()->addDays(14);
        $timeline = [];

        // Matches
        $matches = GmsMockData::getMatches();
        foreach ($matches as $m) {
            $date = $m['date'] ?? null;
            if (!$date) continue;
            $d = Carbon::parse($date);
            if ($d->gte($today) && $d->lte($endWindow)) {
                $timeline[] = [
                    'date' => $d->format('Y-m-d'),
                    'type' => 'match',
                    'icon' => 'trophy',
                    'label' => ($m['team_a_name'] ?? '') . ' vs ' . ($m['team_b_name'] ?? ''),
                    'time' => $m['time'] ?? '',
                    'meta' => $m['venue']['name'] ?? '',
                ];
            }
        }

        // Accommodation check-ins
        $checkIns = AccommodationRequest::where('event_id', $eventId)
            ->whereNotIn('status_id', ['cancelled'])
            ->whereBetween('check_in', [$today, $endWindow])
            ->with('guest')
            ->get();
        foreach ($checkIns as $ci) {
            $timeline[] = [
                'date' => $ci->check_in->format('Y-m-d'),
                'type' => 'checkin',
                'icon' => 'building',
                'label' => ($ci->guest->name ?? 'Guest') . ' check-in',
                'time' => '',
                'meta' => $ci->hotel_name ?? '',
            ];
        }

        // Transport pickups
        $transports = TransportRequest::where('event_id', $eventId)
            ->whereNotIn('status_id', ['cancelled'])
            ->whereNotNull('datetime')
            ->with('guest')
            ->get()
            ->filter(function ($t) use ($today, $endWindow) {
                $dt = Carbon::parse($t->datetime);
                return $dt->gte($today) && $dt->lte($endWindow);
            });
        foreach ($transports as $t) {
            $dt = Carbon::parse($t->datetime);
            $timeline[] = [
                'date' => $dt->format('Y-m-d'),
                'type' => 'transport',
                'icon' => 'car',
                'label' => ($t->guest->name ?? 'Guest') . ' — ' . $t->type,
                'time' => $dt->format('H:i'),
                'meta' => $t->pickup_location . ' → ' . $t->dropoff_location,
            ];
        }

        usort($timeline, fn($a, $b) => strcmp($a['date'] . ($a['time'] ?? ''), $b['date'] . ($b['time'] ?? '')));

        return $timeline;
    }
}
