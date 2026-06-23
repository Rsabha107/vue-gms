<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
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
        ]);

        // Generate a unique key from the name
        $key = \Illuminate\Support\Str::slug($validated['name']) . '_' . time();
        
        EmailTemplate::create([
            'key' => $key,
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
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
        ]);

        $template = EmailTemplate::findOrFail($id);
        $template->update($validated);
        
        return back()->with('success', 'Email template updated.');
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
