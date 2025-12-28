<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class InvitationController extends Controller
{
    /**
     * Affiche la page d'acceptation de l'invitation
     */
    public function show(string $token)
    {
        $invitation = Invitation::findByToken($token);

        if (!$invitation) {
            return redirect()->route('login')
                ->with('error', 'Cette invitation n\'existe pas.');
        }

        if ($invitation->isAccepted()) {
            return redirect()->route('login')
                ->with('info', 'Cette invitation a déjà été acceptée.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('login')
                ->with('error', 'Cette invitation a expiré.');
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = User::where('email', $invitation->email)->first();

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'existingUser' => $existingUser,
        ]);
    }

    /**
     * Accepte l'invitation
     */
    public function accept(Request $request, string $token)
    {
        $invitation = Invitation::findByToken($token);

        if (!$invitation || !$invitation->isValid()) {
            return redirect()->route('login')
                ->with('error', 'Cette invitation n\'est plus valide.');
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            // Utilisateur existant - l'ajouter à l'entreprise
            $user = $existingUser;
        } else {
            // Nouvel utilisateur - valider et créer le compte
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(), // Email vérifié car invitation
            ]);
        }

        // Associer l'utilisateur à l'entreprise
        if (!$user->companies->contains($invitation->company_id)) {
            $user->companies()->attach($invitation->company_id);
        }

        // Assigner le rôle
        $user->assignRole($invitation->role, $invitation->company);

        // Marquer l'invitation comme acceptée
        $invitation->markAsAccepted();

        // Connecter l'utilisateur
        Auth::login($user);

        // Rediriger vers le panel de l'entreprise
        return redirect("/admin/{$invitation->company->slug}")
            ->with('success', "Bienvenue chez {$invitation->company->name} !");
    }
}
