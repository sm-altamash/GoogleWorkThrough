<?php


namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    public function show2faForm()
    {
        $user = Auth::user();

        if (!$user->google2fa_secret) {
            $google2fa = new Google2FA();
            $user->google2fa_secret = $google2fa->generateSecretKey();
            $user->save();
        }

        $google2fa = new Google2FA();
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        $qrCode = $this->generateQrCode($qrCodeUrl);

        return view('auth.twofa', [
            'qrCode' => $qrCode,
            'secret' => $user->google2fa_secret,
        ]);
    }

    public function verify2fa(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $user = Auth::user();
        $google2fa = new Google2FA();

        if ($google2fa->verifyKey($user->google2fa_secret, $request->code)) {
            session(['2fa_passed' => true]);
            $user->update(['2fa_verified_at' => now()]);
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['code' => 'Invalid verification code.']);
    }

    protected function generateQrCode($url)
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($url));
    }

    public function reset(Request $request)
    {
        $user = $request->user();

        $user->forceFill([
            'google2fa_secret' => null,
            '2fa_verified_at'  => null,
        ])->save();

        session()->forget('2fa_passed');

        return back()->with('status', 'Two‑factor authentication has been reset.');
    }

    public function ajaxVerify(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);
        $user      = $request->user();
        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->code);

        if ($valid) {
            // mark session + DB
            session(['2fa_passed' => true]);
            $user->update(['2fa_verified_at' => now()]);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 422);
    }
}