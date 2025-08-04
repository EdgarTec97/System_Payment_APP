<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserVerification;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Show the email verification notice.
     */
    public function show()
    {
        return view('auth.verify-email');
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(Request $request, $token)
    {
        $verification = UserVerification::findValidByToken($token, 'email');

        if (!$verification) {
            return redirect()->route('login')
                           ->withErrors(['email' => 'El enlace de verificación es inválido o ha expirado.']);
        }

        $user = $verification->user;

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')
                           ->with('success', 'Tu email ya está verificado. Puedes iniciar sesión.');
        }

        // Mark email as verified
        $user->markEmailAsVerified();
        $verification->markAsUsed();

        return redirect()->route('login')
                        ->with('success', 'Email verificado exitosamente. Ahora puedes iniciar sesión.');
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser válido.',
            'email.exists' => 'No encontramos una cuenta con este email.',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            return redirect()->back()
                           ->withErrors(['email' => 'Este email ya está verificado.']);
        }

        // Invalidate previous verifications
        $user->verifications()
             ->where('type', 'email')
             ->where('is_used', false)
             ->update(['is_used' => true]);

        // Create new verification
        $verification = UserVerification::createForUser($user, 'email');

        // Dispatch event to send verification email
        event(new \App\Events\UserRegistered($user, $verification));

        return redirect()->back()
                        ->with('success', 'Se ha enviado un nuevo enlace de verificación a tu email.');
    }
}

