<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class PortalTotpController extends Controller
{
    public function show(Request $request, Guest $guest)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'This portal link has expired or is invalid.');
        }

        $event = Event::where('active_flag', true)->first();
        if (!$event) abort(404, 'No active event found.');

        $pivot = $guest->events()->where('event_id', $event->id)->first()?->pivot;
        if (!$pivot) abort(404, 'No event attendance found.');

        $google2fa = new Google2FA();
        $secret = $pivot->totp_secret;
        $isSetup = !empty($secret) && $request->session()->get("portal_totp_scanned_{$guest->id}");
        $qrSvg = null;

        if (empty($secret)) {
            $secret = $google2fa->generateSecretKey();
            $guest->events()->updateExistingPivot($event->id, ['totp_secret' => $secret]);
        }

        if (!$isSetup) {
            $qrUrl = $google2fa->getQRCodeUrl(
                config('app.name', 'GMS'),
                $guest->email ?? $guest->name,
                $secret
            );

            $renderer = new ImageRenderer(
                new RendererStyle(250),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $qrSvg = $writer->writeString($qrUrl);
        }

        return Inertia::render('Portal/TotpVerify', [
            'guestId'   => $guest->id,
            'guestName' => $guest->name,
            'eventName' => $event->name,
            'isSetup'   => $isSetup,
            'qrSvg'     => $qrSvg,
            'secret'    => $isSetup ? null : $secret,
            'portalUrl' => $request->fullUrl(),
        ]);
    }

    public function verify(Request $request, Guest $guest)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $event = Event::where('active_flag', true)->first();
        if (!$event) abort(404);

        $pivot = $guest->events()->where('event_id', $event->id)->first()?->pivot;
        if (!$pivot || !$pivot->totp_secret) abort(422, 'TOTP not configured.');

        $google2fa = new Google2FA();
        $google2fa->setWindow(2);
        $valid = $google2fa->verifyKey($pivot->totp_secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        $request->session()->put("portal_totp_verified_{$guest->id}", true);
        $request->session()->put("portal_totp_scanned_{$guest->id}", true);

        return redirect()->to(
            \Illuminate\Support\Facades\URL::signedRoute('portal.dashboard', ['guest' => $guest->id])
        );
    }
}
