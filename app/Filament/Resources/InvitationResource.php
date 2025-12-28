<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvitationResource\Pages;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Role;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 12;
    protected static ?string $navigationLabel = 'Invitations';
    protected static ?string $modelLabel = 'Invitation';
    protected static ?string $pluralModelLabel = 'Invitations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Inviter un utilisateur')
                    ->description('Envoyez une invitation par email pour rejoindre votre entreprise')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Adresse email')
                            ->email()
                            ->required()
                            ->unique(
                                table: 'invitations',
                                modifyRuleUsing: fn ($rule) => $rule
                                    ->where('company_id', Filament::getTenant()?->id)
                                    ->whereNull('accepted_at')
                            )
                            ->helperText('L\'utilisateur recevra un email avec un lien d\'invitation'),
                        
                        Forms\Components\Select::make('role_id')
                            ->label('Rôle')
                            ->options(function () {
                                return Role::where('company_id', Filament::getTenant()?->id)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->helperText('Le rôle attribué à l\'utilisateur après acceptation'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Rôle')
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('inviter.name')
                    ->label('Invité par')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->getStateUsing(function (Invitation $record): string {
                        if ($record->isAccepted()) {
                            return 'Acceptée';
                        }
                        if ($record->isExpired()) {
                            return 'Expirée';
                        }
                        return 'En attente';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Acceptée' => 'success',
                        'Expirée' => 'danger',
                        'En attente' => 'warning',
                    }),
                    
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('accepted_at')
                    ->label('Acceptée le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Non acceptée')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Envoyée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'accepted' => 'Acceptée',
                        'expired' => 'Expirée',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value']) {
                            'pending' => $query->whereNull('accepted_at')->where('expires_at', '>', now()),
                            'accepted' => $query->whereNotNull('accepted_at'),
                            'expired' => $query->whereNull('accepted_at')->where('expires_at', '<=', now()),
                            default => $query,
                        };
                    }),
                    
                Tables\Filters\SelectFilter::make('role')
                    ->relationship('role', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('resend')
                    ->label('Renvoyer')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Invitation $record) => !$record->isAccepted())
                    ->requiresConfirmation()
                    ->modalHeading('Renvoyer l\'invitation')
                    ->modalDescription('Un nouvel email d\'invitation sera envoyé.')
                    ->action(function (Invitation $record) {
                        // Prolonger l'expiration
                        $record->update(['expires_at' => now()->addDays(7)]);
                        
                        // Renvoyer l'email
                        Mail::to($record->email)->send(new InvitationMail($record));
                        
                        Notification::make()
                            ->title('Invitation renvoyée')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('copy_link')
                    ->label('Copier le lien')
                    ->icon('heroicon-o-clipboard')
                    ->color('gray')
                    ->visible(fn (Invitation $record) => $record->isValid())
                    ->action(function (Invitation $record) {
                        // Le lien sera copié via JS
                    })
                    ->extraAttributes(fn (Invitation $record) => [
                        'x-on:click' => "navigator.clipboard.writeText('" . $record->getAcceptUrl() . "').then(() => \$tooltip('Lien copié!'))",
                    ]),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Annuler'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Annuler les invitations'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvitations::route('/'),
            'create' => Pages\CreateInvitation::route('/create'),
        ];
    }
}
