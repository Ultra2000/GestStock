<?php

namespace App\Filament\Superadmin\Resources;

use App\Models\AuditLog;
use App\Models\Company;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionAuditResource extends Resource
{
    protected static ?string $model              = AuditLog::class;
    protected static ?string $navigationIcon     = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel    = 'Historique abonnements';
    protected static ?string $navigationGroup    = 'Supervision';
    protected static ?string $slug               = 'subscription-audit';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->whereIn('event', [
                'trial_started',
                'trial_extended',
                'trial_ended_by_superadmin',
                'subscription_activated',
                'subscription_expired',
                'stripe_subscription_activated',
                'stripe_subscription_cancelled',
                'stripe_payment_past_due',
            ])
            ->with(['company', 'user'])
            ->latest();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Entreprise')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('event')
                    ->label('Événement')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'subscription_activated', 'stripe_subscription_activated' => 'success',
                        'trial_started', 'trial_extended'                         => 'info',
                        'trial_ended_by_superadmin', 'subscription_expired',
                        'stripe_subscription_cancelled'                           => 'danger',
                        'stripe_payment_past_due'                                 => 'warning',
                        default                                                   => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'trial_started'                  => 'Trial démarré',
                        'trial_extended'                 => 'Trial prolongé',
                        'trial_ended_by_superadmin'      => 'Trial terminé (superadmin)',
                        'subscription_activated'         => 'Abonnement activé',
                        'subscription_expired'           => 'Abonnement expiré',
                        'stripe_subscription_activated'  => 'Stripe : activé',
                        'stripe_subscription_cancelled'  => 'Stripe : annulé',
                        'stripe_payment_past_due'        => 'Stripe : paiement en retard',
                        default                          => $state ?? '—',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Par')
                    ->placeholder('Système / Stripe')
                    ->searchable(),

                Tables\Columns\TextColumn::make('old_values')
                    ->label('Avant')
                    ->formatStateUsing(fn ($state) => $state ? collect($state)->map(fn ($v, $k) => "{$k}: {$v}")->implode(', ') : '—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('new_values')
                    ->label('Après')
                    ->formatStateUsing(fn ($state) => $state ? collect($state)->map(fn ($v, $k) => "{$k}: {$v}")->implode(', ') : '—')
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Entreprise')
                    ->options(Company::pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('event')
                    ->label('Événement')
                    ->options([
                        'trial_started'                 => 'Trial démarré',
                        'trial_extended'                => 'Trial prolongé',
                        'trial_ended_by_superadmin'     => 'Trial terminé (superadmin)',
                        'subscription_activated'        => 'Abonnement activé',
                        'subscription_expired'          => 'Abonnement expiré',
                        'stripe_subscription_activated' => 'Stripe : activé',
                        'stripe_subscription_cancelled' => 'Stripe : annulé',
                        'stripe_payment_past_due'       => 'Stripe : paiement en retard',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Superadmin\Resources\SubscriptionAuditResource\Pages\ListSubscriptionAudits::route('/'),
        ];
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
}
