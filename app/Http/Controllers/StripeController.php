<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer;
use Stripe\Webhook;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Crée une session Stripe Checkout et redirige le client.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan'         => 'required|in:monthly,yearly',
            'company_slug' => 'required|string',
        ]);

        $company = Company::where('slug', $request->company_slug)->firstOrFail();

        // Vérifier que l'utilisateur appartient à cette company
        if (!auth()->user()->companies()->where('companies.id', $company->id)->exists()) {
            abort(403);
        }

        $priceId = $request->plan === 'yearly'
            ? config('services.stripe.price_yearly')
            : config('services.stripe.price_monthly');

        // Créer ou récupérer le customer Stripe
        if (!$company->stripe_customer_id) {
            $customer = Customer::create([
                'email'    => auth()->user()->email,
                'name'     => $company->name,
                'metadata' => ['company_id' => $company->id],
            ]);
            $company->forceFill(['stripe_customer_id' => $customer->id])->save();
        }

        $session = StripeSession::create([
            'customer'            => $company->stripe_customer_id,
            'mode'                => 'subscription',
            'line_items'          => [[
                'price'    => $priceId,
                'quantity' => 1,
            ]],
            'success_url'         => route('stripe.success', ['tenant' => $company->slug]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'          => route('filament.admin.pages.subscription-expired', ['tenant' => $company->slug]),
            'subscription_data'   => [
                'metadata' => ['company_id' => $company->id],
            ],
            'allow_promotion_codes' => true,
        ]);

        return redirect($session->url);
    }

    /**
     * Page de succès après paiement.
     */
    public function success(Request $request, string $tenant)
    {
        $company = Company::where('slug', $tenant)->firstOrFail();

        // Vérifier que l'utilisateur appartient à cette company
        if (!auth()->user()->companies()->where('companies.id', $company->id)->exists()) {
            abort(403);
        }

        // Activer l'abonnement si ce n'est pas encore fait (le webhook le fera aussi)
        if (!$company->isSubscriptionActive()) {
            $company->activateSubscription(
                $request->query('plan', 'standard')
            );
        }

        return redirect(route('filament.admin.pages.default-dashboard', ['tenant' => $tenant]))
            ->with('success', 'Abonnement activé ! Bienvenue sur FRECORP Standard.');
    }

    /**
     * Webhook Stripe — écoute les événements de paiement.
     */
    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            Log::warning('Stripe webhook signature invalide : ' . $e->getMessage());
            return response('Signature invalide', 400);
        }

        match ($event->type) {
            'customer.subscription.created',
            'customer.subscription.updated'  => $this->handleSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted'  => $this->handleSubscriptionDeleted($event->data->object),
            'invoice.payment_failed'         => $this->handlePaymentFailed($event->data->object),
            default                          => null,
        };

        return response('OK', 200);
    }

    private function handleSubscriptionUpdated(object $subscription): void
    {
        $company = $this->findCompanyBySubscription($subscription);
        if (!$company) return;

        $status = $subscription->status; // active, past_due, canceled, etc.

        if (in_array($status, ['active', 'trialing'])) {
            $endsAt = $subscription->current_period_end
                ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end)
                : null;

            $company->forceFill([
                'subscription_status'      => 'active',
                'subscription_plan'        => 'standard',
                'stripe_subscription_id'   => $subscription->id,
                'subscription_ends_at'     => $endsAt,
            ])->save();

            Log::info("Stripe: abonnement activé pour company {$company->id}");
        } elseif ($status === 'past_due') {
            // Accès maintenu, bannière affichée — Stripe réessaie automatiquement
            $company->forceFill(['subscription_status' => 'past_due'])->save();
            Log::warning("Stripe: paiement en retard pour company {$company->id}");
        } elseif ($status === 'unpaid') {
            // Tous les essais Stripe épuisés → blocage
            $company->expireSubscription();
            Log::warning("Stripe: paiement impayé (accès bloqué) pour company {$company->id}");
        }
    }

    private function handleSubscriptionDeleted(object $subscription): void
    {
        $company = $this->findCompanyBySubscription($subscription);
        if (!$company) return;

        $company->expireSubscription();
        Log::info("Stripe: abonnement annulé pour company {$company->id}");
    }

    private function handlePaymentFailed(object $invoice): void
    {
        $company = Company::where('stripe_customer_id', $invoice->customer)->first();
        if (!$company) return;

        Log::warning("Stripe: échec de paiement pour company {$company->id}");
        // L'accès reste ouvert jusqu'à subscription.deleted (Stripe réessaie 3x)
    }

    private function findCompanyBySubscription(object $subscription): ?Company
    {
        // D'abord via les metadata
        $companyId = $subscription->metadata->company_id ?? null;
        if ($companyId) {
            return Company::find($companyId);
        }
        // Fallback via stripe_customer_id
        return Company::where('stripe_customer_id', $subscription->customer)->first();
    }
}
