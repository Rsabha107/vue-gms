<?php

namespace Database\Seeders;

use App\Models\Guest;
use Illuminate\Database\Seeder;

class GuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guests = [
            ['name' => 'H.H. Sheikh Tamim bin Hamad Al-Thani', 'firstName' => 'Tamim',     'lastName' => 'Al-Thani',   'title' => 'Emir of Qatar',          'guestType' => 'local', 'tier' => 'T1', 'group_id' => 'GRP-VVIP',  'nationality' => 'QA', 'status_id' => 'confirmed', 'email' => 'protocol@diwan.gov.qa',      'phone' => '+974 4441 0000', 'host' => 'H03', 'hotel' => null,     'dietaryNotes' => 'Halal', 'notes' => 'Full state protocol required', 'companions' => 2, 'companionList' => json_encode([
                ['name' => 'Sheikha Jawaher bint Hamad Al-Thani', 'relation' => 'Spouse'],
                ['name' => 'Sheikh Joaan bin Hamad Al-Thani', 'relation' => 'Family']
            ]), 'facilities' => json_encode([
                'flight' => ['cls' => 'Private Jet', 'inb' => 'HIA', 'date' => '2026-12-01', 'status' => 'confirmed'],
                'hotel' => ['hotel' => 'State Guest Palace', 'room' => 'Presidential Suite', 'in' => '2026-12-01', 'out' => '2026-12-15', 'status' => 'confirmed'],
                'transport' => ['car' => 'State Motorcade', 'status' => 'confirmed']
            ])],
            ['name' => 'Emmanuel Macron',                       'firstName' => 'Emmanuel',  'lastName' => 'Macron',     'title' => 'President of France',    'guestType' => 'international', 'tier' => 'T1', 'group_id' => 'GRP-VVIP',  'nationality' => 'FR', 'status_id' => 'confirmed', 'email' => 'macron@elysee.fr',           'phone' => '+33 1 4220 1111', 'host' => 'H03', 'hotel' => 'HOT-01', 'dietaryNotes' => '', 'notes' => 'Security detail of 12', 'companions' => 1, 'companionList' => json_encode([
                ['name' => 'Brigitte Macron', 'relation' => 'Spouse']
            ]), 'facilities' => json_encode([
                'flight' => ['cls' => 'Business', 'inb' => 'CDG', 'date' => '2026-12-02', 'status' => 'confirmed'],
                'hotel' => ['hotel' => 'Four Seasons', 'room' => 'Royal Suite', 'in' => '2026-12-02', 'out' => '2026-12-10', 'status' => 'confirmed'],
                'transport' => ['car' => 'Mercedes S-Class', 'status' => 'confirmed']
            ])],
            ['name' => 'Gianni Infantino',                      'firstName' => 'Gianni',    'lastName' => 'Infantino',  'title' => 'FIFA President',         'guestType' => 'international', 'tier' => 'T1', 'group_id' => 'GRP-FIFA',  'nationality' => 'CH', 'status_id' => 'confirmed', 'email' => 'g.infantino@fifa.org',       'phone' => '+41 43 222 7777', 'host' => 'H01', 'hotel' => 'HOT-02', 'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Prince William',                        'firstName' => 'William',   'lastName' => 'Windsor',    'title' => 'Prince of Wales',        'guestType' => 'international', 'tier' => 'T1', 'group_id' => 'GRP-VVIP',  'nationality' => 'GB', 'status_id' => 'confirmed', 'email' => 'pw@royal.gov.uk',            'phone' => '+44 20 7930 4832', 'host' => 'H03', 'hotel' => 'HOT-03', 'dietaryNotes' => '', 'notes' => 'Palace protocol briefing required', 'companions' => 3, 'companionList' => json_encode([
                ['name' => 'Catherine, Princess of Wales', 'relation' => 'Spouse'],
                ['name' => 'Protection Officer #1', 'relation' => 'Security'],
                ['name' => 'Private Secretary', 'relation' => 'Aide']
            ]), 'facilities' => json_encode([
                'flight' => ['cls' => 'First Class', 'inb' => 'LHR', 'date' => '2026-12-03', 'status' => 'confirmed'],
                'hotel' => ['hotel' => 'St. Regis Doha', 'room' => 'Royal Suite', 'in' => '2026-12-03', 'out' => '2026-12-08', 'status' => 'confirmed'],
                'transport' => ['car' => 'Range Rover', 'status' => 'confirmed']
            ])],
            ['name' => 'Sheikh Mohammed bin Rashid Al Maktoum', 'firstName' => 'Mohammed',  'lastName' => 'Al Maktoum', 'title' => 'VP & PM of UAE',         'guestType' => 'international', 'tier' => 'T1', 'group_id' => 'GRP-VVIP',  'nationality' => 'AE', 'status_id' => 'confirmed', 'email' => 'pm@uae.gov.ae',              'phone' => '+971 4 330 0000', 'host' => 'H03', 'hotel' => 'HOT-04', 'dietaryNotes' => 'Halal', 'notes' => ''],
            ['name' => 'Olaf Scholz',                           'firstName' => 'Olaf',      'lastName' => 'Scholz',     'title' => 'Chancellor of Germany',  'guestType' => 'international', 'tier' => 'T2', 'group_id' => 'GRP-VVIP',  'nationality' => 'DE', 'status_id' => 'confirmed', 'email' => 'kanzler@bundesregierung.de', 'phone' => '+49 30 400 00', 'host' => 'H02', 'hotel' => 'HOT-05', 'dietaryNotes' => 'Vegetarian', 'notes' => ''],
            ['name' => 'Arsène Wenger',                         'firstName' => 'Arsène',    'lastName' => 'Wenger',     'title' => 'FIFA Chief of Football', 'guestType' => 'international', 'tier' => 'T2', 'group_id' => 'GRP-FIFA',  'nationality' => 'FR', 'status_id' => 'confirmed', 'email' => 'a.wenger@fifa.org',          'phone' => '+41 43 222 8000', 'host' => 'H04', 'hotel' => 'HOT-06', 'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Kylian Mbappé',                         'firstName' => 'Kylian',    'lastName' => 'Mbappé',     'title' => 'Professional Footballer','guestType' => 'international', 'tier' => 'T3', 'group_id' => 'GRP-SPORT', 'nationality' => 'FR', 'status_id' => 'confirmed', 'email' => 'kylian@mbappe.com',          'phone' => '+33 6 00 00 0001', 'host' => 'H04', 'hotel' => 'HOT-07', 'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Cristiano Ronaldo',                     'firstName' => 'Cristiano', 'lastName' => 'Ronaldo',    'title' => 'Professional Footballer','guestType' => 'international', 'tier' => 'T3', 'group_id' => 'GRP-SPORT', 'nationality' => 'PT', 'status_id' => 'confirmed', 'email' => 'cr7@example.com',            'phone' => '+351 21 000 0000', 'host' => 'H04', 'hotel' => 'HOT-07', 'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Lewis Hamilton',                        'firstName' => 'Lewis',     'lastName' => 'Hamilton',   'title' => 'F1 Champion',            'guestType' => 'international', 'tier' => 'T3', 'group_id' => 'GRP-SPORT', 'nationality' => 'GB', 'status_id' => 'invited',   'email' => 'lewis@hamiltonteam.com',     'phone' => '+44 20 0000 0000', 'host' => 'H04', 'hotel' => null,     'dietaryNotes' => 'Vegan', 'notes' => ''],
            ['name' => 'António Guterres',                      'firstName' => 'António',   'lastName' => 'Guterres',   'title' => 'UN Secretary-General',   'guestType' => 'international', 'tier' => 'T1', 'group_id' => 'GRP-VVIP',  'nationality' => 'PT', 'status_id' => 'pending',   'email' => 'sg@un.org',                  'phone' => '+1 212 963 1234', 'host' => 'H03', 'hotel' => null,     'dietaryNotes' => '', 'notes' => 'Awaiting RSVP'],
            ['name' => 'Pedro Sánchez',                         'firstName' => 'Pedro',     'lastName' => 'Sánchez',    'title' => 'PM of Spain',            'guestType' => 'international', 'tier' => 'T2', 'group_id' => 'GRP-VVIP',  'nationality' => 'ES', 'status_id' => 'confirmed', 'email' => 'pm@lamoncloa.es',            'phone' => '+34 91 335 3535', 'host' => 'H02', 'hotel' => 'HOT-01', 'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Thomas Müller',                         'firstName' => 'Thomas',    'lastName' => 'Müller',     'title' => 'Professional Footballer','guestType' => 'international', 'tier' => 'T3', 'group_id' => 'GRP-SPORT', 'nationality' => 'DE', 'status_id' => 'confirmed', 'email' => 'mueller@example.com',        'phone' => '+49 89 0000 000', 'host' => 'H04', 'hotel' => 'HOT-06', 'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Tim Cook',                              'firstName' => 'Tim',       'lastName' => 'Cook',       'title' => 'CEO, Apple Inc.',        'guestType' => 'international', 'tier' => 'T3', 'group_id' => 'GRP-CORP',  'nationality' => 'US', 'status_id' => 'confirmed', 'email' => 'tcook@apple.com',            'phone' => '+1 408 996 1010', 'host' => 'H04', 'hotel' => 'HOT-02', 'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Prince Albert II of Monaco',            'firstName' => 'Albert',    'lastName' => 'Grimaldi',   'title' => 'Sovereign Prince',       'guestType' => 'international', 'tier' => 'T1', 'group_id' => 'GRP-VVIP',  'nationality' => 'MC', 'status_id' => 'confirmed', 'email' => 'protocol@palais.mc',         'phone' => '+377 93 25 1831', 'host' => 'H03', 'hotel' => 'HOT-03', 'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Fatima Al-Qahtani',                     'firstName' => 'Fatima',    'lastName' => 'Al-Qahtani', 'title' => 'Minister of Culture',    'guestType' => 'local', 'tier' => 'T2', 'group_id' => 'GRP-LOC',   'nationality' => 'QA', 'status_id' => 'confirmed', 'email' => 'f.qahtani@mocd.gov.qa',      'phone' => '+974 4441 9000', 'host' => 'H01', 'hotel' => null,     'dietaryNotes' => 'Halal', 'notes' => ''],
            ['name' => 'Ahmed Al-Rashed',                       'firstName' => 'Ahmed',     'lastName' => 'Al-Rashed',  'title' => 'Senior Correspondent',   'guestType' => 'local', 'tier' => 'T5', 'group_id' => 'GRP-MEDIA', 'nationality' => 'QA', 'status_id' => 'confirmed', 'email' => 'a.rashed@aljazeera.net',     'phone' => '+974 4489 4511', 'host' => null,  'hotel' => 'HOT-07', 'dietaryNotes' => 'Halal', 'notes' => ''],
            ['name' => 'Sarah Chen',                            'firstName' => 'Sarah',     'lastName' => 'Chen',       'title' => 'Sports Editor',          'guestType' => 'international', 'tier' => 'T5', 'group_id' => 'GRP-MEDIA', 'nationality' => 'CN', 'status_id' => 'confirmed', 'email' => 's.chen@reuters.com',         'phone' => '+852 2847 8888', 'host' => null,  'hotel' => 'HOT-07', 'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Carlos Alcaraz',                        'firstName' => 'Carlos',    'lastName' => 'Alcaraz',    'title' => 'Professional Athlete',   'guestType' => 'international', 'tier' => 'T4', 'group_id' => 'GRP-SPORT', 'nationality' => 'ES', 'status_id' => 'invited',   'email' => 'carlos@alcaraz.es',          'phone' => '+34 968 000 000', 'host' => 'H04', 'hotel' => null,     'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Neymar Jr.',                            'firstName' => 'Neymar',    'lastName' => 'Santos Jr.', 'title' => 'Professional Footballer','guestType' => 'international', 'tier' => 'T3', 'group_id' => 'GRP-SPORT', 'nationality' => 'BR', 'status_id' => 'declined',  'email' => 'neymar@njrteam.com',         'phone' => '+55 11 0000 0000', 'host' => 'H04', 'hotel' => null,     'dietaryNotes' => '', 'notes' => 'Declined – injury'],
            ['name' => 'Lina Al-Amri',                          'firstName' => 'Lina',      'lastName' => 'Al-Amri',    'title' => 'LOC Chairperson',        'guestType' => 'local', 'tier' => 'T1', 'group_id' => 'GRP-LOC',   'nationality' => 'QA', 'status_id' => 'confirmed', 'email' => 'l.amri@loc.qa',              'phone' => '+974 4449 0001', 'host' => 'H01', 'hotel' => null,     'dietaryNotes' => 'Halal', 'notes' => ''],
            ['name' => 'Jürgen Klopp',                          'firstName' => 'Jürgen',    'lastName' => 'Klopp',      'title' => 'Head of Football, Red Bull','guestType' => 'international', 'tier' => 'T3', 'group_id' => 'GRP-SPORT', 'nationality' => 'DE', 'status_id' => 'confirmed', 'email' => 'klopp@example.com',          'phone' => '+49 89 0000 001', 'host' => 'H04', 'hotel' => 'HOT-06', 'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Pep Guardiola',                         'firstName' => 'Pep',       'lastName' => 'Guardiola',  'title' => 'Head Coach',             'guestType' => 'international', 'tier' => 'T3', 'group_id' => 'GRP-SPORT', 'nationality' => 'ES', 'status_id' => 'confirmed', 'email' => 'pep@guardiola.com',          'phone' => '+44 161 000 0000', 'host' => 'H04', 'hotel' => 'HOT-06', 'dietaryNotes' => '', 'notes' => ''],
            ['name' => 'Rafael Al-Mansouri',                    'firstName' => 'Rafael',    'lastName' => 'Al-Mansouri', 'title' => 'Deputy Minister',       'guestType' => 'local', 'tier' => 'T2', 'group_id' => 'GRP-MOI',   'nationality' => 'QA', 'status_id' => 'confirmed', 'email' => 'r.mansouri@moi.gov.qa',      'phone' => '+974 4440 0050', 'host' => 'H01', 'hotel' => null,     'dietaryNotes' => 'Halal', 'notes' => ''],
            ['name' => 'Zinedine Zidane',                       'firstName' => 'Zinedine',  'lastName' => 'Zidane',     'title' => 'Football Legend',        'guestType' => 'international', 'tier' => 'T3', 'group_id' => 'GRP-SPORT', 'nationality' => 'FR', 'status_id' => 'pending',   'email' => 'zizou@example.com',          'phone' => '+33 6 00 00 0002', 'host' => 'H04', 'hotel' => null,     'dietaryNotes' => '', 'notes' => 'Invitation pending response'],
        ];

        foreach ($guests as $index => $guestData) {
            $guestData['reference_number'] = 'G' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            Guest::create($guestData);
        }
    }
}
