<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;

class PublicQuoteController extends Controller
{
    public function show(string $token)
    {
        $quote = Quote::with(['customer', 'items.product', 'user'])
            ->where('public_token', $token)
            ->firstOrFail();

        // Vérifier si expiré
        if ($quote->expires_at && $quote->expires_at->isPast() && $quote->status === 'sent') {
            $quote->update(['status' => 'expired']);
        }

        return view('public.quote', compact('quote'));
    }

    public function accept(string $token)
    {
        $quote = Quote::where('public_token', $token)
            ->where('status', 'sent')
            ->firstOrFail();

        if ($quote->expires_at && $quote->expires_at->isPast()) {
            return back()->with('error', 'Ce devis a expiré.');
        }

        $quote->accept();

        return back()->with('success', 'Devis accepté avec succès ! Vous recevrez bientôt la confirmation.');
    }

    public function reject(Request $request, string $token)
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $quote = Quote::where('public_token', $token)
            ->where('status', 'sent')
            ->firstOrFail();

        $quote->reject($request->input('reason'));

        return back()->with('success', 'Merci de votre retour. Nous prenons note de votre refus.');
    }

}
