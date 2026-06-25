<?php

namespace App\Services\Gms;

use App\Models\Event;

class GmsMockData
{
    /**
     * Get all active events
     */
    public static function getEvents(): array
    {
        return Event::active()
            ->orderBy('date_start', 'desc')
            ->get()
            ->map(function ($event) {
                return [
                    'id'       => $event->id,
                    'name'     => $event->name,
                    'subtitle' => $event->subtitle,
                    'location' => $event->location,
                    'venue'    => $event->venue,
                    'dates'    => $event->formatted_dates,
                    'dateStart' => $event->date_start->format('Y-m-d'),
                    'dateEnd'   => $event->date_end->format('Y-m-d'),
                    'logo'     => $event->logo,
                ];
            })
            ->toArray();
    }

    /**
     * Get current event (from session or first active event)
     */
    public static function getEvent(): array
    {
        $eventId = session('gms_current_event_id');
        
        if ($eventId) {
            $event = Event::find($eventId);
            if ($event) {
                return [
                    'id'       => $event->id,
                    'name'     => $event->name,
                    'subtitle' => $event->subtitle,
                    'location' => $event->location,
                    'venue'    => $event->venue,
                    'dates'    => $event->formatted_dates,
                    'dateStart' => $event->date_start->format('Y-m-d'),
                    'dateEnd'   => $event->date_end->format('Y-m-d'),
                    'logo'     => $event->logo,
                    'portal_enabled' => $event->portal_enabled ?? false,
                    'portal_auth_mode' => $event->portal_auth_mode ?? 'signed_url',
                ];
            }
        }

        // Fallback to first active event
        $event = Event::active()->orderBy('date_start', 'desc')->first();
        
        if ($event) {
            session(['gms_current_event_id' => $event->id]);
            return [
                'id'       => $event->id,
                'name'     => $event->name,
                'subtitle' => $event->subtitle,
                'location' => $event->location,
                'venue'    => $event->venue,
                'dates'    => $event->formatted_dates,
                'dateStart' => $event->date_start->format('Y-m-d'),
                'dateEnd'   => $event->date_end->format('Y-m-d'),
                'logo'     => $event->logo,
                'portal_enabled' => $event->portal_enabled ?? false,
                'portal_auth_mode' => $event->portal_auth_mode ?? 'signed_url',
            ];
        }

        // Fallback if no events in database
        return [
            'id'       => null,
            'name'     => "Doha Cup '26",
            'subtitle' => 'International Football Tournament',
            'location' => 'Lusail, Qatar',
            'venue'    => 'Lusail Stadium',
            'dates'    => 'Jan 15 – Feb 28, 2026',
            'dateStart' => '2026-01-15',
            'dateEnd'   => '2026-02-28',
            'logo'     => null,
            'portal_enabled' => false,
            'portal_auth_mode' => 'signed_url',
        ];
    }

    public static function getTiers(): array
    {
        return [
            ['id' => 'T1', 'name' => 'Platinum', 'color' => '#5b4a8a', 'bg' => '#ece9f3', 'rank' => 1, 'facilities' => ['VIP Royal Lounge', 'Chauffeur Escort', 'Presidential Suite', 'Fine Dining', 'Dedicated Host', 'Airport Fast-Track']],
            ['id' => 'T2', 'name' => 'Platinum', 'color' => '#5b4a8a', 'bg' => '#ece9f3', 'rank' => 2, 'facilities' => ['Executive Lounge', 'Private Driver', 'Luxury Suite', 'Premium Dining', 'Personal Host']],
            ['id' => 'T3', 'name' => 'Gold',     'color' => '#9a7430', 'bg' => '#f3ecdd', 'rank' => 3, 'facilities' => ['Business Lounge', 'Shuttle Service', 'Premium Room', 'Restaurant Access']],
            ['id' => 'T4', 'name' => 'Silver',   'color' => '#7a756c', 'bg' => '#eceae6', 'rank' => 4, 'facilities' => ['Lounge Access', 'Group Shuttle']],
            ['id' => 'T5', 'name' => 'Silver',   'color' => '#7a756c', 'bg' => '#eceae6', 'rank' => 5, 'facilities' => ['Press Box', 'Media Kit', 'Photo Access']],
        ];
    }

    public static function getGroups(): array
    {
        return [
            ['id' => 'GRP-LOC', 'name' => 'LOC',              'label' => 'Local Organising Committee'],
            ['id' => 'GRP-MOI', 'name' => 'MOI',              'label' => 'Ministry of Interior'],
            ['id' => 'GRP-FIFA', 'name' => 'FIFA',            'label' => 'FIFA Delegation'],
            ['id' => 'GRP-VVIP', 'name' => 'State Guests',    'label' => 'Head of State / Royal'],
            ['id' => 'GRP-CORP', 'name' => 'Corporate',       'label' => 'Corporate Partners'],
            ['id' => 'GRP-MEDIA', 'name' => 'Media',          'label' => 'Accredited Media'],
            ['id' => 'GRP-SPORT', 'name' => 'Sports Figures', 'label' => 'Athletes & Officials'],
        ];
    }

    public static function getHosts(): array
    {
        return [
            ['id' => 'H01', 'name' => 'Khalid Al-Hamadi',   'role' => 'Protocol Director'],
            ['id' => 'H02', 'name' => 'Nour Al-Rashidi',    'role' => 'VIP Relations Manager'],
            ['id' => 'H03', 'name' => 'Sara Al-Jassim',     'role' => 'State Guest Coordinator'],
            ['id' => 'H04', 'name' => 'Mohammed Al-Dosari', 'role' => 'Corporate Relations'],
        ];
    }

    public static function getHotels(): array
    {
        return [
            ['id' => 'HOT-01', 'name' => 'Four Seasons Doha',         'area' => 'West Bay',   'stars' => 5],
            ['id' => 'HOT-02', 'name' => 'Mandarin Oriental Doha',    'area' => 'Msheireb',   'stars' => 5],
            ['id' => 'HOT-03', 'name' => 'St. Regis Doha',            'area' => 'West Bay',   'stars' => 5],
            ['id' => 'HOT-04', 'name' => 'Banyan Tree Doha',          'area' => 'Lusail',     'stars' => 5],
            ['id' => 'HOT-05', 'name' => 'Marsa Malaz Kempinski',     'area' => 'Pearl Island','stars' => 5],
            ['id' => 'HOT-06', 'name' => 'InterContinental Doha',     'area' => 'West Bay',   'stars' => 5],
            ['id' => 'HOT-07', 'name' => 'Waldorf Astoria Lusail',    'area' => 'Lusail',     'stars' => 5],
        ];
    }

    public static function getVenues(): array
    {
        return \App\Models\Venue::all()->map(function ($venue) {
            return [
                'id' => $venue->id,
                'name' => $venue->name,
                'city' => $venue->city,
                'country' => $venue->country,
                'capacity' => $venue->capacity,
                'type' => $venue->type,
                'active_flag' => $venue->active_flag,
            ];
        })->toArray();
    }

    public static function getMatches(): array
    {
        return \App\Models\GameMatch::with('venue')
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->map(function ($match) {
                return [
                    'id' => 'M' . str_pad($match->id, 2, '0', STR_PAD_LEFT), // M01, M02, etc.
                    'venueId' => 'V' . $match->venue_id,
                    'venueName' => $match->venue->name ?? '',
                    'featured' => $match->featured,
                    'name' => $match->label,
                    'stageCode' => strtoupper(str_replace(' ', ' ', $match->stage ?? '')),
                    'stageLabel' => $match->label,
                    'homeTeam' => $match->team_a_name,
                    'homeCode' => $match->team_a_flag,
                    'awayTeam' => $match->team_b_name,
                    'awayCode' => $match->team_b_flag,
                    'date' => $match->date ? $match->date->format('D d M Y') : '',
                    'kickoff' => $match->time,
                    'stage' => $match->stage,
                    'seatsLeft' => $match->capacity - $match->sold,
                    'seatsTotal' => $match->capacity,
                ];
            })
            ->toArray();
    }

    public static function getGuests(): array
    {
        return [
            ['id' => 'G001', 'name' => 'H.H. Sheikh Tamim bin Hamad Al-Thani', 'firstName' => 'Tamim',     'lastName' => 'Al-Thani',   'title' => 'Emir of Qatar',          'tier' => 'T1', 'group' => 'GRP-VVIP',  'nationality' => 'QA', 'status' => 'confirmed', 'email' => 'protocol@diwan.gov.qa',      'phone' => '+974 4441 0000', 'host' => 'H03', 'hotel' => null,     'dietaryNotes' => 'Halal', 'notes' => 'Full state protocol required'],
            ['id' => 'G002', 'name' => 'Emmanuel Macron',                       'firstName' => 'Emmanuel',  'lastName' => 'Macron',     'title' => 'President of France',    'tier' => 'T1', 'group' => 'GRP-VVIP',  'nationality' => 'FR', 'status' => 'confirmed', 'email' => 'macron@elysee.fr',           'phone' => '+33 1 4220 1111', 'host' => 'H03', 'hotel' => 'HOT-01', 'dietaryNotes' => '', 'notes' => 'Security detail of 12'],
            ['id' => 'G003', 'name' => 'Gianni Infantino',                      'firstName' => 'Gianni',    'lastName' => 'Infantino',  'title' => 'FIFA President',         'tier' => 'T1', 'group' => 'GRP-FIFA',  'nationality' => 'CH', 'status' => 'confirmed', 'email' => 'g.infantino@fifa.org',       'phone' => '+41 43 222 7777', 'host' => 'H01', 'hotel' => 'HOT-02', 'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G004', 'name' => 'Prince William',                        'firstName' => 'William',   'lastName' => 'Windsor',    'title' => 'Prince of Wales',        'tier' => 'T1', 'group' => 'GRP-VVIP',  'nationality' => 'GB', 'status' => 'confirmed', 'email' => 'pw@royal.gov.uk',            'phone' => '+44 20 7930 4832', 'host' => 'H03', 'hotel' => 'HOT-03', 'dietaryNotes' => '', 'notes' => 'Palace protocol briefing required'],
            ['id' => 'G005', 'name' => 'Sheikh Mohammed bin Rashid Al Maktoum', 'firstName' => 'Mohammed',  'lastName' => 'Al Maktoum', 'title' => 'VP & PM of UAE',         'tier' => 'T1', 'group' => 'GRP-VVIP',  'nationality' => 'AE', 'status' => 'confirmed', 'email' => 'pm@uae.gov.ae',              'phone' => '+971 4 330 0000', 'host' => 'H03', 'hotel' => 'HOT-04', 'dietaryNotes' => 'Halal', 'notes' => ''],
            ['id' => 'G006', 'name' => 'Olaf Scholz',                           'firstName' => 'Olaf',      'lastName' => 'Scholz',     'title' => 'Chancellor of Germany',  'tier' => 'T2', 'group' => 'GRP-VVIP',  'nationality' => 'DE', 'status' => 'confirmed', 'email' => 'kanzler@bundesregierung.de', 'phone' => '+49 30 400 00', 'host' => 'H02', 'hotel' => 'HOT-05', 'dietaryNotes' => 'Vegetarian', 'notes' => ''],
            ['id' => 'G007', 'name' => 'Arsène Wenger',                         'firstName' => 'Arsène',    'lastName' => 'Wenger',     'title' => 'FIFA Chief of Football', 'tier' => 'T2', 'group' => 'GRP-FIFA',  'nationality' => 'FR', 'status' => 'confirmed', 'email' => 'a.wenger@fifa.org',          'phone' => '+41 43 222 8000', 'host' => 'H04', 'hotel' => 'HOT-06', 'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G008', 'name' => 'Kylian Mbappé',                         'firstName' => 'Kylian',    'lastName' => 'Mbappé',     'title' => 'Professional Footballer','tier' => 'T3', 'group' => 'GRP-SPORT', 'nationality' => 'FR', 'status' => 'confirmed', 'email' => 'kylian@mbappe.com',          'phone' => '+33 6 00 00 0001', 'host' => 'H04', 'hotel' => 'HOT-07', 'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G009', 'name' => 'Cristiano Ronaldo',                     'firstName' => 'Cristiano', 'lastName' => 'Ronaldo',    'title' => 'Professional Footballer','tier' => 'T3', 'group' => 'GRP-SPORT', 'nationality' => 'PT', 'status' => 'confirmed', 'email' => 'cr7@example.com',            'phone' => '+351 21 000 0000', 'host' => 'H04', 'hotel' => 'HOT-07', 'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G010', 'name' => 'Lewis Hamilton',                        'firstName' => 'Lewis',     'lastName' => 'Hamilton',   'title' => 'F1 Champion',            'tier' => 'T3', 'group' => 'GRP-SPORT', 'nationality' => 'GB', 'status' => 'invited',   'email' => 'lewis@hamiltonteam.com',     'phone' => '+44 20 0000 0000', 'host' => 'H04', 'hotel' => null,     'dietaryNotes' => 'Vegan', 'notes' => ''],
            ['id' => 'G011', 'name' => 'António Guterres',                      'firstName' => 'António',   'lastName' => 'Guterres',   'title' => 'UN Secretary-General',   'tier' => 'T1', 'group' => 'GRP-VVIP',  'nationality' => 'PT', 'status' => 'pending',   'email' => 'sg@un.org',                  'phone' => '+1 212 963 1234', 'host' => 'H03', 'hotel' => null,     'dietaryNotes' => '', 'notes' => 'Awaiting RSVP'],
            ['id' => 'G012', 'name' => 'Pedro Sánchez',                         'firstName' => 'Pedro',     'lastName' => 'Sánchez',    'title' => 'PM of Spain',            'tier' => 'T2', 'group' => 'GRP-VVIP',  'nationality' => 'ES', 'status' => 'confirmed', 'email' => 'pm@lamoncloa.es',            'phone' => '+34 91 335 3535', 'host' => 'H02', 'hotel' => 'HOT-01', 'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G013', 'name' => 'Thomas Müller',                         'firstName' => 'Thomas',    'lastName' => 'Müller',     'title' => 'Professional Footballer','tier' => 'T3', 'group' => 'GRP-SPORT', 'nationality' => 'DE', 'status' => 'confirmed', 'email' => 'mueller@example.com',        'phone' => '+49 89 0000 000', 'host' => 'H04', 'hotel' => 'HOT-06', 'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G014', 'name' => 'Tim Cook',                              'firstName' => 'Tim',       'lastName' => 'Cook',       'title' => 'CEO, Apple Inc.',        'tier' => 'T3', 'group' => 'GRP-CORP',  'nationality' => 'US', 'status' => 'confirmed', 'email' => 'tcook@apple.com',            'phone' => '+1 408 996 1010', 'host' => 'H04', 'hotel' => 'HOT-02', 'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G015', 'name' => 'Prince Albert II of Monaco',            'firstName' => 'Albert',    'lastName' => 'Grimaldi',   'title' => 'Sovereign Prince',       'tier' => 'T1', 'group' => 'GRP-VVIP',  'nationality' => 'MC', 'status' => 'confirmed', 'email' => 'protocol@palais.mc',         'phone' => '+377 93 25 1831', 'host' => 'H03', 'hotel' => 'HOT-03', 'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G016', 'name' => 'Fatima Al-Qahtani',                     'firstName' => 'Fatima',    'lastName' => 'Al-Qahtani', 'title' => 'Minister of Culture',    'tier' => 'T2', 'group' => 'GRP-LOC',   'nationality' => 'QA', 'status' => 'confirmed', 'email' => 'f.qahtani@mocd.gov.qa',      'phone' => '+974 4441 9000', 'host' => 'H01', 'hotel' => null,     'dietaryNotes' => 'Halal', 'notes' => ''],
            ['id' => 'G017', 'name' => 'Ahmed Al-Rashed',                       'firstName' => 'Ahmed',     'lastName' => 'Al-Rashed',  'title' => 'Senior Correspondent',   'tier' => 'T5', 'group' => 'GRP-MEDIA', 'nationality' => 'QA', 'status' => 'confirmed', 'email' => 'a.rashed@aljazeera.net',     'phone' => '+974 4489 4511', 'host' => null,  'hotel' => 'HOT-07', 'dietaryNotes' => 'Halal', 'notes' => ''],
            ['id' => 'G018', 'name' => 'Sarah Chen',                            'firstName' => 'Sarah',     'lastName' => 'Chen',       'title' => 'Sports Editor',          'tier' => 'T5', 'group' => 'GRP-MEDIA', 'nationality' => 'CN', 'status' => 'confirmed', 'email' => 's.chen@reuters.com',         'phone' => '+852 2847 8888', 'host' => null,  'hotel' => 'HOT-07', 'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G019', 'name' => 'Carlos Alcaraz',                        'firstName' => 'Carlos',    'lastName' => 'Alcaraz',    'title' => 'Professional Athlete',   'tier' => 'T4', 'group' => 'GRP-SPORT', 'nationality' => 'ES', 'status' => 'invited',   'email' => 'carlos@alcaraz.es',          'phone' => '+34 968 000 000', 'host' => 'H04', 'hotel' => null,     'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G020', 'name' => 'Neymar Jr.',                            'firstName' => 'Neymar',    'lastName' => 'Santos Jr.',  'title' => 'Professional Footballer','tier' => 'T3', 'group' => 'GRP-SPORT', 'nationality' => 'BR', 'status' => 'declined',  'email' => 'neymar@njrteam.com',         'phone' => '+55 11 0000 0000', 'host' => 'H04', 'hotel' => null,    'dietaryNotes' => '', 'notes' => 'Declined – injury'],
            ['id' => 'G021', 'name' => 'Lina Al-Amri',                         'firstName' => 'Lina',      'lastName' => 'Al-Amri',    'title' => 'LOC Chairperson',        'tier' => 'T1', 'group' => 'GRP-LOC',   'nationality' => 'QA', 'status' => 'confirmed', 'email' => 'l.amri@loc.qa',              'phone' => '+974 4449 0001', 'host' => 'H01', 'hotel' => null,     'dietaryNotes' => 'Halal', 'notes' => ''],
            ['id' => 'G022', 'name' => 'Jürgen Klopp',                          'firstName' => 'Jürgen',    'lastName' => 'Klopp',      'title' => 'Head of Football, Red Bull','tier' => 'T3', 'group' => 'GRP-SPORT', 'nationality' => 'DE', 'status' => 'confirmed', 'email' => 'klopp@example.com',         'phone' => '+49 89 0000 001', 'host' => 'H04', 'hotel' => 'HOT-06', 'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G023', 'name' => 'Pep Guardiola',                         'firstName' => 'Pep',       'lastName' => 'Guardiola',  'title' => 'Head Coach',             'tier' => 'T3', 'group' => 'GRP-SPORT', 'nationality' => 'ES', 'status' => 'confirmed', 'email' => 'pep@guardiola.com',          'phone' => '+44 161 000 0000', 'host' => 'H04', 'hotel' => 'HOT-06', 'dietaryNotes' => '', 'notes' => ''],
            ['id' => 'G024', 'name' => 'Rafael Al-Mansouri',                    'firstName' => 'Rafael',    'lastName' => 'Al-Mansouri', 'title' => 'Deputy Minister',       'tier' => 'T2', 'group' => 'GRP-MOI',   'nationality' => 'QA', 'status' => 'confirmed', 'email' => 'r.mansouri@moi.gov.qa',      'phone' => '+974 4440 0050', 'host' => 'H01', 'hotel' => null,     'dietaryNotes' => 'Halal', 'notes' => ''],
            ['id' => 'G025', 'name' => 'Zinedine Zidane',                       'firstName' => 'Zinedine',  'lastName' => 'Zidane',     'title' => 'Football Legend',        'tier' => 'T3', 'group' => 'GRP-SPORT', 'nationality' => 'FR', 'status' => 'pending',   'email' => 'zizou@example.com',          'phone' => '+33 6 00 00 0002', 'host' => 'H04', 'hotel' => null,    'dietaryNotes' => '', 'notes' => 'Invitation pending response'],
        ];
    }

    public static function getSeatingTemplates(): array
    {
        return [
            [
                'id'      => 'ST1',
                'venueId' => 'V1',
                'name'    => 'Lusail — Full VIP Tribune',
                'blocks'  => [
                    [
                        'id' => 'A', 'label' => 'Block A', 'tier' => 'VVIP', 'color' => '#8a1f3d',
                        'rows' => [
                            ['label' => '1', 'seats' => 4,  'aisles' => [2]],
                            ['label' => '2', 'seats' => 14, 'aisles' => [7]],
                            ['label' => '3', 'seats' => 14, 'aisles' => [7]],
                            ['label' => '4', 'seats' => 16, 'aisles' => [8], 'walkway' => true],
                        ],
                    ],
                    [
                        'id' => 'B', 'label' => 'Block B', 'tier' => 'VVIP', 'color' => '#4b5563',
                        'rows' => [
                            ['label' => '1', 'seats' => 6,  'aisles' => [3]],
                            ['label' => '2', 'seats' => 12, 'aisles' => [6]],
                            ['label' => '3', 'seats' => 14, 'aisles' => [7]],
                            ['label' => '4', 'seats' => 14, 'aisles' => [7]],
                        ],
                    ],
                    [
                        'id' => 'C', 'label' => 'Block C', 'tier' => 'VIP', 'color' => '#374151',
                        'rows' => [
                            ['label' => '1', 'seats' => 12, 'aisles' => [6]],
                            ['label' => '2', 'seats' => 16, 'aisles' => [8]],
                            ['label' => '3', 'seats' => 18, 'aisles' => [6, 12]],
                            ['label' => '4', 'seats' => 18, 'aisles' => [6, 12]],
                            ['label' => '5', 'seats' => 20, 'aisles' => [6, 14]],
                        ],
                    ],
                ],
            ],
            [
                'id'      => 'ST2',
                'venueId' => 'V1',
                'name'    => 'Lusail — West Wing (compact)',
                'blocks'  => [
                    [
                        'id' => 'A', 'label' => 'Block A', 'tier' => 'VVIP', 'color' => '#8a1f3d',
                        'rows' => [
                            ['label' => '1', 'seats' => 4,  'aisles' => [2]],
                            ['label' => '2', 'seats' => 14, 'aisles' => [7]],
                            ['label' => '3', 'seats' => 14, 'aisles' => [7]],
                            ['label' => '4', 'seats' => 16, 'aisles' => [8], 'walkway' => true],
                        ],
                    ],
                    [
                        'id' => 'B', 'label' => 'Block B', 'tier' => 'VVIP', 'color' => '#4b5563',
                        'rows' => [
                            ['label' => '1', 'seats' => 6,  'aisles' => [3]],
                            ['label' => '2', 'seats' => 12, 'aisles' => [6]],
                            ['label' => '3', 'seats' => 14, 'aisles' => [7]],
                        ],
                    ],
                ],
            ],
            [
                'id'      => 'ST3',
                'venueId' => 'V2',
                'name'    => 'Al Bayt — Royal Tribune',
                'blocks'  => [
                    [
                        'id' => 'A', 'label' => 'Royal Box', 'tier' => 'VVIP', 'color' => '#8a1f3d',
                        'rows' => [
                            ['label' => '1', 'seats' => 8,  'aisles' => [4]],
                            ['label' => '2', 'seats' => 12, 'aisles' => [6]],
                            ['label' => '3', 'seats' => 12, 'aisles' => [6]],
                        ],
                    ],
                    [
                        'id' => 'B', 'label' => 'Tribune East', 'tier' => 'VIP', 'color' => '#4b5563',
                        'rows' => [
                            ['label' => '1', 'seats' => 14, 'aisles' => [7]],
                            ['label' => '2', 'seats' => 16, 'aisles' => [8]],
                            ['label' => '3', 'seats' => 16, 'aisles' => [8], 'walkway' => true],
                            ['label' => '4', 'seats' => 18, 'aisles' => [9]],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function seatsFromTemplate(array $template): array
    {
        $seats = [];
        foreach ($template['blocks'] as $block) {
            $rowList = $block['rows'] ?? [];
            foreach ($rowList as $row) {
                $rowLabel = (string)$row['label'];
                $rowId    = str_pad($rowLabel, 2, '0', STR_PAD_LEFT);
                for ($c = 1; $c <= $row['seats']; $c++) {
                    $col = str_pad((string)$c, 2, '0', STR_PAD_LEFT);
                    $seats[] = [
                        'id'         => $block['id'] . '-' . $rowId . '-' . $col,
                        'block'      => $block['id'],
                        'blockLabel' => $block['label'],
                        'blockTier'  => $block['tier'] ?? '',
                        'blockColor' => $block['color'] ?? '#8a1f3d',
                        'row'        => $rowLabel,
                        'rowLabel'   => $rowLabel,
                        'col'        => $c,
                        'status'     => 'available',
                        'guestId'    => null,
                        'resLabel'   => null,
                        'hidden'     => false,
                    ];
                }
            }
        }
        return $seats;
    }

    public static function getMatchSeeds(): array
    {
        return [
            'M01' => 'ST1',
            'M02' => 'ST1',
            'M07' => 'ST1',
            'M08' => 'ST1',
        ];
    }

    public static function getMatchSeats(): array
    {
        $templates  = collect(self::getSeatingTemplates())->keyBy('id');
        $seeds      = self::getMatchSeeds();
        $matchSeats = [];

        foreach ($seeds as $matchId => $templateId) {
            $tpl   = $templates[$templateId];
            $seats = self::seatsFromTemplate($tpl);

            // Seed demo assignments (IDs now use padded row labels: A-01-01, A-02-03, etc.)
            if ($matchId === 'M01') {
                self::assignSeat($seats, 'A-01-01', 'G001');
                self::assignSeat($seats, 'A-01-02', 'G002');
                self::assignSeat($seats, 'A-01-03', 'G004');
                self::assignSeat($seats, 'A-01-04', 'G005');
                self::assignSeat($seats, 'A-02-01', 'G003');
                self::assignSeat($seats, 'A-02-02', 'G015');
                self::assignSeat($seats, 'A-02-05', 'G006');
                self::assignSeat($seats, 'A-02-09', 'G007');
                self::assignSeat($seats, 'A-03-03', 'G008');
                self::assignSeat($seats, 'A-03-04', 'G009');
                self::assignSeat($seats, 'A-04-01', 'G010');
                self::assignSeat($seats, 'A-04-02', 'G011');
                self::reserveSeat($seats, 'A-02-03', 'LOC');
                self::reserveSeat($seats, 'A-02-04', 'LOC');
                self::reserveSeat($seats, 'A-02-06', 'LOC');
                self::reserveSeat($seats, 'A-03-01', 'MOI');
                self::reserveSeat($seats, 'A-03-02', 'MOI');
                self::assignSeat($seats, 'B-01-01', 'G012');
                self::assignSeat($seats, 'B-01-02', 'G013');
                self::assignSeat($seats, 'B-02-03', 'G014');
                self::reserveSeat($seats, 'B-02-01', 'QFA');
                self::reserveSeat($seats, 'B-02-02', 'QFA');
                self::assignSeat($seats, 'C-01-02', 'G016');
                self::assignSeat($seats, 'C-01-05', 'G017');
                self::assignSeat($seats, 'C-02-01', 'G018');
                self::assignSeat($seats, 'C-02-08', 'G019');
                self::reserveSeat($seats, 'C-01-07', 'AFC');
                self::reserveSeat($seats, 'C-01-08', 'AFC');
            }
            if ($matchId === 'M02') {
                self::assignSeat($seats, 'A-01-01', 'G002');
                self::assignSeat($seats, 'A-01-02', 'G007');
                self::assignSeat($seats, 'A-02-01', 'G008');
                self::reserveSeat($seats, 'A-02-03', 'MOI');
                self::reserveSeat($seats, 'A-02-04', 'MOI');
            }
            $matchSeats[$matchId] = $seats;
        }
        return $matchSeats;
    }

    private static function assignSeat(array &$seats, string $seatId, string $guestId): void
    {
        foreach ($seats as &$s) {
            if ($s['id'] === $seatId) {
                $s['status']  = 'assigned';
                $s['guestId'] = $guestId;
                break;
            }
        }
        unset($s);
    }

    private static function reserveSeat(array &$seats, string $seatId, string $resLabel): void
    {
        foreach ($seats as &$s) {
            if ($s['id'] === $seatId) {
                $s['status']   = 'reserved';
                $s['resLabel'] = $resLabel;
                break;
            }
        }
        unset($s);
    }

    public static function getFlightRequests(): array
    {
        return [
            ['id'=>'FLT-001','guestId'=>'G002','guestName'=>'Emmanuel Macron',   'status'=>'confirmed','changeRequest'=>false,'pnr'=>'PJK7X2',
             'flightNo'=>'QR 2025','inboundFlight'=>'QR 2025','outboundFlight'=>'QR 2026',
             'route'=>'CDG → DOH','origin'=>'CDG','originCity'=>'Paris','destination'=>'DOH','destCity'=>'Doha',
             'class'=>'Business','pax'=>3,'airline'=>'Qatar Airways','duration'=>'7h 15m',
             'date'=>'2026-01-14','time'=>'09:30','arrival'=>'2026-01-14','arrivalTime'=>'18:45',
             'outboundDate'=>'2026-01-20','outboundTime'=>'22:00','outboundArrival'=>'2026-01-21','outboundArrivalTime'=>'04:15',
             'inboundTerminal'=>'Arrival — Hamad International (HIA)','outboundTerminal'=>'Departure — Hamad International (HIA)',
             'submitted'=>'2025-12-10 14:30','notes'=>'Diplomatic passport, no queue'],

            ['id'=>'FLT-002','guestId'=>'G004','guestName'=>'Prince William',    'status'=>'new',      'changeRequest'=>true, 'pnr'=>'RX49WL',
             'flightNo'=>'QR 5',    'inboundFlight'=>'QR 5',    'outboundFlight'=>'QR 6',
             'route'=>'LHR → DOH','origin'=>'LHR','originCity'=>'London','destination'=>'DOH','destCity'=>'Doha',
             'class'=>'First','pax'=>6,'airline'=>'Qatar Airways','duration'=>'6h 50m',
             'date'=>'2026-01-14','time'=>'21:00','arrival'=>'2026-01-15','arrivalTime'=>'07:00',
             'outboundDate'=>'2026-01-21','outboundTime'=>'09:00','outboundArrival'=>'2026-01-21','outboundArrivalTime'=>'14:55',
             'inboundTerminal'=>'Arrival — Royal Terminal','outboundTerminal'=>'Departure — Royal Terminal',
             'submitted'=>'2025-12-15 10:00','notes'=>'Security pre-clear required – time change requested'],

            ['id'=>'FLT-003','guestId'=>'G006','guestName'=>'Olaf Scholz',       'status'=>'confirmed','changeRequest'=>false,'pnr'=>'GH2MNQ',
             'flightNo'=>'LH 688', 'inboundFlight'=>'LH 688', 'outboundFlight'=>'LH 689',
             'route'=>'FRA → DOH','origin'=>'FRA','originCity'=>'Frankfurt','destination'=>'DOH','destCity'=>'Doha',
             'class'=>'Business','pax'=>4,'airline'=>'Lufthansa','duration'=>'6h 30m',
             'date'=>'2026-01-15','time'=>'11:00','arrival'=>'2026-01-15','arrivalTime'=>'19:30',
             'outboundDate'=>'2026-01-21','outboundTime'=>'23:30','outboundArrival'=>'2026-01-22','outboundArrivalTime'=>'05:00',
             'inboundTerminal'=>'Arrival — Hamad International (HIA)','outboundTerminal'=>'Departure — Hamad International (HIA)',
             'submitted'=>'2025-12-18 09:15','notes'=>''],

            ['id'=>'FLT-004','guestId'=>'G007','guestName'=>'Arsène Wenger',     'status'=>'new',      'changeRequest'=>false,'pnr'=>'7BKT3F',
             'flightNo'=>'QR 47',  'inboundFlight'=>'QR 47',  'outboundFlight'=>'QR 48',
             'route'=>'GVA → DOH','origin'=>'GVA','originCity'=>'Geneva','destination'=>'DOH','destCity'=>'Doha',
             'class'=>'Business','pax'=>2,'airline'=>'Qatar Airways','duration'=>'5h 50m',
             'date'=>'2026-01-14','time'=>'14:20','arrival'=>'2026-01-14','arrivalTime'=>'22:10',
             'outboundDate'=>'2026-01-20','outboundTime'=>'01:30','outboundArrival'=>'2026-01-20','outboundArrivalTime'=>'06:20',
             'inboundTerminal'=>'Arrival — Hamad International (HIA)','outboundTerminal'=>'Departure — Hamad International (HIA)',
             'submitted'=>'2026-01-02 11:00','notes'=>'Awaiting confirmation'],

            ['id'=>'FLT-005','guestId'=>'G008','guestName'=>'Kylian Mbappé',     'status'=>'new',      'changeRequest'=>false,'pnr'=>'MP5VZ9',
             'flightNo'=>'QR 39',  'inboundFlight'=>'QR 39',  'outboundFlight'=>'QR 40',
             'route'=>'CDG → DOH','origin'=>'CDG','originCity'=>'Paris','destination'=>'DOH','destCity'=>'Doha',
             'class'=>'First','pax'=>2,'airline'=>'Qatar Airways','duration'=>'7h 10m',
             'date'=>'2026-01-17','time'=>'22:00','arrival'=>'2026-01-18','arrivalTime'=>'07:10',
             'outboundDate'=>'2026-01-22','outboundTime'=>'03:00','outboundArrival'=>'2026-01-22','outboundArrivalTime'=>'09:10',
             'inboundTerminal'=>'Arrival — Hamad International (HIA)','outboundTerminal'=>'Departure — Hamad International (HIA)',
             'submitted'=>'2026-01-05 16:45','notes'=>''],

            ['id'=>'FLT-006','guestId'=>'G012','guestName'=>'Pedro Sánchez',     'status'=>'new',      'changeRequest'=>false,'pnr'=>'SN8YCJ',
             'flightNo'=>'IB 3168','inboundFlight'=>'IB 3168','outboundFlight'=>'IB 3169',
             'route'=>'MAD → DOH','origin'=>'MAD','originCity'=>'Madrid','destination'=>'DOH','destCity'=>'Doha',
             'class'=>'Business','pax'=>3,'airline'=>'Iberia','duration'=>'6h 20m',
             'date'=>'2026-01-14','time'=>'08:00','arrival'=>'2026-01-14','arrivalTime'=>'18:20',
             'outboundDate'=>'2026-01-21','outboundTime'=>'20:00','outboundArrival'=>'2026-01-22','outboundArrivalTime'=>'02:20',
             'inboundTerminal'=>'Arrival — Hamad International (HIA)','outboundTerminal'=>'Departure — Hamad International (HIA)',
             'submitted'=>'2026-01-08 08:00','notes'=>''],

            ['id'=>'FLT-007','guestId'=>'G009','guestName'=>'Cristiano Ronaldo', 'status'=>'confirmed','changeRequest'=>false,'pnr'=>'CR7DOH',
             'flightNo'=>'QR 271', 'inboundFlight'=>'QR 271', 'outboundFlight'=>'QR 272',
             'route'=>'RUH → DOH','origin'=>'RUH','originCity'=>'Riyadh','destination'=>'DOH','destCity'=>'Doha',
             'class'=>'First','pax'=>1,'airline'=>'Qatar Airways','duration'=>'1h 30m',
             'date'=>'2026-01-17','time'=>'17:00','arrival'=>'2026-01-17','arrivalTime'=>'18:30',
             'outboundDate'=>'2026-01-22','outboundTime'=>'19:00','outboundArrival'=>'2026-01-22','outboundArrivalTime'=>'20:30',
             'inboundTerminal'=>'Arrival — Hamad International (HIA)','outboundTerminal'=>'Departure — Hamad International (HIA)',
             'submitted'=>'2025-12-20 12:00','notes'=>''],

            ['id'=>'FLT-008','guestId'=>'G014','guestName'=>'Tim Cook',          'status'=>'cancelled','changeRequest'=>false,'pnr'=>'TCK209',
             'flightNo'=>'AA 209', 'inboundFlight'=>'AA 209', 'outboundFlight'=>'AA 210',
             'route'=>'JFK → DOH','origin'=>'JFK','originCity'=>'New York','destination'=>'DOH','destCity'=>'Doha',
             'class'=>'Business','pax'=>2,'airline'=>'American Airlines','duration'=>'13h 25m',
             'date'=>'2026-01-13','time'=>'22:30','arrival'=>'2026-01-15','arrivalTime'=>'05:55',
             'outboundDate'=>'2026-01-21','outboundTime'=>'08:00','outboundArrival'=>'2026-01-21','outboundArrivalTime'=>'13:25',
             'inboundTerminal'=>'Arrival — Hamad International (HIA)','outboundTerminal'=>'Departure — Hamad International (HIA)',
             'submitted'=>'2025-12-05 17:00','notes'=>'Cancelled – rescheduled separately'],
        ];
    }

    public static function getAccommodationRequests(): array
    {
        return [
            ['id' => 'ACC-001', 'guestId' => 'G002', 'guestName' => 'Emmanuel Macron',    'status' => 'confirmed', 'hotel' => 'HOT-01', 'hotelName' => 'Four Seasons Doha',      'roomType' => 'Presidential Suite', 'checkIn' => '2026-01-14', 'checkOut' => '2026-01-20', 'nights' => 6, 'notes' => 'Private entrance requested'],
            ['id' => 'ACC-002', 'guestId' => 'G004', 'guestName' => 'Prince William',     'status' => 'confirmed', 'hotel' => 'HOT-03', 'hotelName' => 'St. Regis Doha',         'roomType' => 'Royal Suite',       'checkIn' => '2026-01-14', 'checkOut' => '2026-01-16', 'nights' => 2, 'notes' => 'Security sweep required prior to arrival'],
            ['id' => 'ACC-003', 'guestId' => 'G006', 'guestName' => 'Olaf Scholz',        'status' => 'confirmed', 'hotel' => 'HOT-05', 'hotelName' => 'Marsa Malaz Kempinski', 'roomType' => 'Diplomatic Suite',  'checkIn' => '2026-01-15', 'checkOut' => '2026-01-17', 'nights' => 2, 'notes' => ''],
            ['id' => 'ACC-004', 'guestId' => 'G007', 'guestName' => 'Arsène Wenger',      'status' => 'new',       'hotel' => 'HOT-06', 'hotelName' => 'InterContinental Doha', 'roomType' => 'Executive Suite',   'checkIn' => '2026-01-14', 'checkOut' => '2026-01-19', 'nights' => 5, 'notes' => 'Awaiting FIFA allocation confirmation'],
            ['id' => 'ACC-005', 'guestId' => 'G008', 'guestName' => 'Kylian Mbappé',      'status' => 'confirmed', 'hotel' => 'HOT-07', 'hotelName' => 'Waldorf Astoria Lusail','roomType' => 'Deluxe Suite',       'checkIn' => '2026-01-17', 'checkOut' => '2026-01-20', 'nights' => 3, 'notes' => ''],
            ['id' => 'ACC-006', 'guestId' => 'G009', 'guestName' => 'Cristiano Ronaldo',  'status' => 'change',    'hotel' => 'HOT-07', 'hotelName' => 'Waldorf Astoria Lusail','roomType' => 'Penthouse Suite',    'checkIn' => '2026-01-17', 'checkOut' => '2026-01-19', 'nights' => 2, 'notes' => 'Room upgrade requested'],
            ['id' => 'ACC-007', 'guestId' => 'G012', 'guestName' => 'Pedro Sánchez',      'status' => 'new',       'hotel' => 'HOT-01', 'hotelName' => 'Four Seasons Doha',      'roomType' => 'Diplomatic Suite',  'checkIn' => '2026-01-14', 'checkOut' => '2026-01-16', 'nights' => 2, 'notes' => ''],
            ['id' => 'ACC-008', 'guestId' => 'G015', 'guestName' => 'Prince Albert II',   'status' => 'confirmed', 'hotel' => 'HOT-03', 'hotelName' => 'St. Regis Doha',         'roomType' => 'Royal Suite',       'checkIn' => '2026-01-14', 'checkOut' => '2026-01-16', 'nights' => 2, 'notes' => ''],
        ];
    }

    public static function getTransportRequests(): array
    {
        return [
            ['id' => 'TRN-001', 'guestId' => 'G002', 'guestName' => 'Emmanuel Macron',    'status' => 'confirmed', 'type' => 'VIP Escort',   'vehicle' => 'Mercedes S-Class',   'pickupLocation' => 'HIA Terminal 1',       'dropoffLocation' => 'Four Seasons Doha',    'datetime' => '2026-01-14 19:00', 'driver' => 'Ahmed Hassan',     'notes' => 'Police escort + outriders'],
            ['id' => 'TRN-002', 'guestId' => 'G004', 'guestName' => 'Prince William',     'status' => 'confirmed', 'type' => 'Motorcade',    'vehicle' => 'Range Rover Fleet',  'pickupLocation' => 'HIA VIP Terminal',     'dropoffLocation' => 'St. Regis Doha',       'datetime' => '2026-01-14 21:00', 'driver' => 'Khalid Al-Muqrin', 'notes' => 'Full motorcade – 5 vehicles'],
            ['id' => 'TRN-003', 'guestId' => 'G006', 'guestName' => 'Olaf Scholz',        'status' => 'confirmed', 'type' => 'VIP Transfer', 'vehicle' => 'BMW 7 Series',       'pickupLocation' => 'HIA Terminal 1',       'dropoffLocation' => 'Kempinski Doha',       'datetime' => '2026-01-15 20:00', 'driver' => 'Mohammed Al-Kindi','notes' => ''],
            ['id' => 'TRN-004', 'guestId' => 'G003', 'guestName' => 'Gianni Infantino',   'status' => 'confirmed', 'type' => 'VIP Transfer', 'vehicle' => 'Lexus LX 600',       'pickupLocation' => 'Mandarin Oriental',    'dropoffLocation' => 'Lusail Stadium',       'datetime' => '2026-01-15 16:00', 'driver' => 'Jassim Al-Nasr',   'notes' => 'Stadium loop pass required'],
            ['id' => 'TRN-005', 'guestId' => 'G008', 'guestName' => 'Kylian Mbappé',      'status' => 'pending',   'type' => 'Private Car',  'vehicle' => 'Mercedes V-Class',   'pickupLocation' => 'Waldorf Astoria Lusail','dropoffLocation' => 'Lusail Stadium',      'datetime' => '2026-01-18 17:00', 'driver' => 'TBD',              'notes' => 'Awaiting driver assignment'],
            ['id' => 'TRN-006', 'guestId' => 'G001', 'guestName' => 'H.H. Sheikh Tamim',  'status' => 'confirmed', 'type' => 'Royal Convoy', 'vehicle' => 'State Fleet',        'pickupLocation' => 'Amiri Diwan',          'dropoffLocation' => 'Lusail Stadium',       'datetime' => '2026-01-15 17:30', 'driver' => 'Protocol Office',  'notes' => 'Full security closure protocol'],
        ];
    }

    public static function getArrivalDepartureRequests(): array
    {
        return \App\Models\ArrivalDepartureRequest::with('guest:id,first_name,last_name')
            ->orderBy('datetime')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'guestId' => $request->guest_id,
                    'guestName' => $request->guest->first_name . ' ' . $request->guest->last_name,
                    'status' => $request->status,
                    'type' => $request->type,
                    'flightNo' => $request->flight_no,
                    'terminal' => $request->terminal,
                    'datetime' => $request->datetime->format('Y-m-d H:i'),
                    'lounge' => $request->lounge,
                    'greeter' => $request->greeter,
                    'notes' => $request->notes,
                ];
            })
            ->toArray();
    }

    public static function getEmailTemplates(): array
    {
        return \App\Models\EmailTemplate::orderBy('name')->get()->toArray();
    }

    public static function getDashboardStats(): array
    {
        $guests = self::getGuests();
        $total     = count($guests);
        $confirmed = count(array_filter($guests, fn($g) => $g['status'] === 'confirmed'));
        $pending   = count(array_filter($guests, fn($g) => $g['status'] === 'pending'));
        $invited   = count(array_filter($guests, fn($g) => $g['status'] === 'invited'));
        $declined  = count(array_filter($guests, fn($g) => $g['status'] === 'declined'));

        $matchSeats   = self::getMatchSeats();
        $totalSeats   = 0;
        $assignedSeats = 0;
        foreach ($matchSeats as $seats) {
            foreach ($seats as $seat) {
                if (!$seat['hidden']) {
                    $totalSeats++;
                    if (in_array($seat['status'], ['assigned', 'ticket'])) $assignedSeats++;
                }
            }
        }
        $fillPct = $totalSeats > 0 ? round($assignedSeats / $totalSeats * 100) : 0;

        return [
            'totalGuests'    => $total,
            'confirmed'      => $confirmed,
            'pending'        => $pending,
            'invited'        => $invited,
            'declined'       => $declined,
            'flightRequests' => count(self::getFlightRequests()),
            'accommodation'  => count(self::getAccommodationRequests()),
            'transport'      => count(self::getTransportRequests()),
            'adRequests'     => count(self::getArrivalDepartureRequests()),
            'seatedGuests'   => $assignedSeats,
            'totalSeats'     => $totalSeats,
            'fillPct'        => $fillPct,
            'matchesWithSeating' => count($matchSeats),
            'totalMatches'   => count(self::getMatches()),
        ];
    }
}
