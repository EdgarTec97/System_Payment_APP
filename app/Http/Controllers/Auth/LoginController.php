<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('email', 'remember'));
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Check if user is active
            if (!$user->is_active) {
                Auth::logout();
                return redirect()->back()
                    ->withErrors(['email' => 'Tu cuenta está desactivada. Contacta al administrador.'])
                    ->withInput($request->only('email'));
            }

            // Check if email is verified
            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                return redirect()->back()
                    ->withErrors(['email' => 'Debes verificar tu email antes de iniciar sesión.'])
                    ->withInput($request->only('email'));
            }

            // Update last login information
            $user->updateLastLogin($request->ip());

            $request->session()->regenerate();

            return $this->redirectToDashboard($user);
        }

        return redirect()->back()
            ->withErrors(['email' => 'Las credenciales no coinciden con nuestros registros.'])
            ->withInput($request->only('email'));
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Has cerrado sesión exitosamente.');
    }

    /**
     * Get a validator for an incoming login request.
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);
    }

    /**
     * Redirect user to appropriate dashboard based on role.
     */
    protected function redirectToDashboard($user)
    {
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('support')) {
            return redirect()->route('support.dashboard');
        } else {
            return redirect()->route('dashboard');
        }
    }
}
