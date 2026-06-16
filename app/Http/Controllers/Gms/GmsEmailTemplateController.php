<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
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
        $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        // TODO: save to database
        return back()->with('success', 'Email template created.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        // TODO: update in database
        return back()->with('success', 'Email template updated.');
    }

    public function destroy(string $id)
    {
        // TODO: delete from database
        return back()->with('success', 'Email template deleted.');
    }
}
