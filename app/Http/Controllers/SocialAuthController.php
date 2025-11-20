<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the provider authentication page.
     *
     * @param string $provider
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($provider)
    {
        // Validate provider
        if (!in_array($provider, ['google', 'facebook'])) {
            return redirect()->route('login')->with('error', 'Fournisseur non pris en charge.');
        }

        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Erreur lors de la connexion avec ' . ucfirst($provider) . '.');
        }
    }

    /**
     * Obtain the user information from the provider.
     *
     * @param string $provider
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback($provider)
    {
        // Validate provider
        if (!in_array($provider, ['google', 'facebook'])) {
            return redirect()->route('login')->with('error', 'Fournisseur non pris en charge.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Erreur lors de la connexion avec ' . ucfirst($provider) . '. Veuillez réessayer.');
        }

        // Check if user already exists with this provider
        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($user) {
            // Update avatar URL if changed
            if ($socialUser->getAvatar() && $user->avatar_url !== $socialUser->getAvatar()) {
                $user->update(['avatar_url' => $socialUser->getAvatar()]);
            }

            Auth::login($user, true);
            return redirect()->intended('/');
        }

        // Check if user exists with same email
        $existingUser = User::where('email', $socialUser->getEmail())->first();

        if ($existingUser) {
            // Link social account to existing user
            $existingUser->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar(),
            ]);

            Auth::login($existingUser, true);
            return redirect()->intended('/');
        }

        // Create new user
        $names = $this->extractNames($socialUser->getName());

        $newUser = User::create([
            'first_name' => $names['first_name'],
            'last_name' => $names['last_name'],
            'email' => $socialUser->getEmail(),
            'password' => Hash::make(Str::random(24)), // Random password
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar_url' => $socialUser->getAvatar(),
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(), // Auto-verify social login emails
        ]);

        // Assign customer role using Spatie
        $newUser->assignRole('customer');

        Auth::login($newUser, true);

        return redirect()->intended('/')->with('success', 'Bienvenue sur Glowing Cosmetics!');
    }

    /**
     * Extract first and last name from full name
     *
     * @param string $fullName
     * @return array
     */
    private function extractNames($fullName)
    {
        $parts = explode(' ', $fullName, 2);

        return [
            'first_name' => $parts[0] ?? 'User',
            'last_name' => $parts[1] ?? '',
        ];
    }
}
