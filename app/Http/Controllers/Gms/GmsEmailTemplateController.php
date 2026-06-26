<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class GmsEmailTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('Gms/EmailTemplates/Index', [
            'emailTemplates' => GmsMockData::getEmailTemplates(),
            'event'          => GmsMockData::getEvent(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
            'type'    => 'required|string|in:' . implode(',', array_keys(EmailTemplate::TYPES)),
            'cc'      => 'nullable|string|max:500',
            'bcc'     => 'nullable|string|max:500',
            'enabled' => 'boolean',
        ]);

        $key = \Illuminate\Support\Str::slug($validated['name']) . '_' . time();

        EmailTemplate::create([
            'key'        => $key,
            'name'       => $validated['name'],
            'type'       => $validated['type'],
            'subject'    => $validated['subject'],
            'body'       => $validated['body'],
            'cc'         => $validated['cc'] ?? null,
            'bcc'        => $validated['bcc'] ?? null,
            'enabled'    => $validated['enabled'] ?? true,
            'is_default' => false,
        ]);

        return back()->with('success', 'Email template created.');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
            'type'    => 'required|string|in:' . implode(',', array_keys(EmailTemplate::TYPES)),
            'cc'      => 'nullable|string|max:500',
            'bcc'     => 'nullable|string|max:500',
            'enabled' => 'boolean',
        ]);

        $template = EmailTemplate::findOrFail($id);
        $template->update($validated);

        return back()->with('success', 'Email template updated.');
    }

    public function sendTest(Request $request, string $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $request->validate(['email' => 'required|email']);
        $toEmail = $request->input('email');

        if (!$toEmail) {
            return back()->with('error', 'No email address available to send test.');
        }

        $event = GmsMockData::getEvent();

        $sampleData = [
            'guest_name'     => 'John Doe',
            'guest_title'    => 'Senior Correspondent',
            'guest_email'    => $toEmail,
            'event_name'     => $event['name'] ?? "Doha Cup '26",
            'event_date'     => $event['dates'] ?? '9 – 23 Aug 2026',
            'event_location' => $event['location'] ?? 'Doha, Qatar',
            'tier_name'      => 'Platinum',
            'match_list'     => 'Brazil vs Italy · Wed 12 Aug · 18:00',
            'portal_url'     => url('/portal/dashboard/1'),
            'service_details'=> 'QR 304 · LHR → DOH · 9 Aug · Business',
            'rsvp_url'       => url('/rsvp/sample-token'),
        ];

        $extraViewData = [
            'matches' => [
                ['stage' => 'Group B', 'homeTeam' => 'Brazil', 'awayTeam' => 'Italy', 'date' => 'Wed 12 Aug 2026', 'time' => '18:00', 'venue' => 'Al Bayt Stadium'],
                ['stage' => 'Quarter Final', 'homeTeam' => 'Winner Gp A', 'awayTeam' => 'Runner-up Gp B', 'date' => 'Sat 15 Aug 2026', 'time' => '18:00', 'venue' => 'Lusail Stadium'],
            ],
            'serviceFields' => [
                'Ref Code' => 'FL-001',
                'Route'    => 'London → Doha',
                'Class'    => 'Business',
                'Date'     => '9 Aug 2026',
            ],
            'portalUrl' => url('/portal/dashboard/1'),
        ];

        Mail::to($toEmail)->send(
            new TemplatedMail($template, $sampleData, $template->type, $extraViewData)
        );

        return back()->with('success', 'Test email sent to ' . $toEmail);
    }

    public function destroy(string $id)
    {
        $template = EmailTemplate::findOrFail($id);
        
        // Prevent deletion if it's the last template
        if (EmailTemplate::count() <= 1) {
            return back()->with('error', 'Cannot delete the last template.');
        }
        
        $template->delete();
        
        return back()->with('success', 'Email template deleted.');
    }
}
