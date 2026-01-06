<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'RH';

    protected static ?string $navigationLabel = 'Employés';

    protected static ?string $modelLabel = 'Employé';

    protected static ?string $pluralModelLabel = 'Employés';

    protected static ?int $navigationSort = 4;
    
    public static function shouldRegisterNavigation(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant?->isModuleEnabled('hr')) {
            return false;
        }
        
        $user = auth()->user();
        if (!$user) return false;
        
        return $user->isAdmin() || $user->hasPermission('employees.view') || $user->hasPermission('employees.*');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Employé')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informations personnelles')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\FileUpload::make('photo')
                                    ->label('Photo')
                                    ->image()
                                    ->avatar()
                                    ->circleCropper()
                                    ->directory('employees'),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('employee_number')
                                            ->label('Matricule')
                                            ->disabled()
                                            ->placeholder('Auto'),
                                        Forms\Components\TextInput::make('first_name')
                                            ->label('Prénom')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('last_name')
                                            ->label('Nom')
                                            ->required()
                                            ->maxLength(255),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->required(fn (Forms\Get $get) => $get('create_user')),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Téléphone')
                                            ->tel(),
                                        Forms\Components\DatePicker::make('birth_date')
                                            ->label('Date de naissance'),
                                    ]),
                                Forms\Components\Textarea::make('address')
                                    ->label('Adresse')
                                    ->rows(2),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('city')
                                            ->label('Ville'),
                                        Forms\Components\TextInput::make('postal_code')
                                            ->label('Code postal'),
                                        Forms\Components\TextInput::make('country')
                                            ->label('Pays')
                                            ->default('France'),
                                    ]),
                                Forms\Components\TextInput::make('social_security_number')
                                    ->label('N° Sécurité sociale')
                                    ->maxLength(15),
                            ]),
                        Forms\Components\Tabs\Tab::make('Contrat & Poste')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('position')
                                            ->label('Poste')
                                            ->required(),
                                        Forms\Components\TextInput::make('department')
                                            ->label('Service'),
                                        Forms\Components\Select::make('warehouse_id')
                                            ->label('Entrepôt par défaut')
                                            ->relationship('warehouse', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->default(fn () => Warehouse::where('is_default', true)->first()?->id),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('contract_type')
                                            ->label('Type de contrat')
                                            ->options([
                                                'cdi' => 'CDI',
                                                'cdd' => 'CDD',
                                                'interim' => 'Intérim',
                                                'stage' => 'Stage',
                                                'apprentissage' => 'Apprentissage',
                                                'freelance' => 'Freelance',
                                            ])
                                            ->default('cdi')
                                            ->required(),
                                        Forms\Components\DatePicker::make('hire_date')
                                            ->label("Date d'embauche")
                                            ->required()
                                            ->default(now()),
                                        Forms\Components\DatePicker::make('contract_end_date')
                                            ->label('Fin de contrat'),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('weekly_hours')
                                            ->label('Heures/semaine')
                                            ->numeric()
                                            ->default(35),
                                        Forms\Components\TextInput::make('hourly_rate')
                                            ->label('Taux horaire')
                                            ->numeric()
                                            ->prefix('€'),
                                        Forms\Components\TextInput::make('monthly_salary')
                                            ->label('Salaire mensuel')
                                            ->numeric()
                                            ->prefix('€'),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('commission_rate')
                                            ->label('Taux de commission')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(0)
                                            ->helperText('% sur les ventes réalisées'),
                                        Forms\Components\Select::make('status')
                                            ->label('Statut')
                                            ->options([
                                                'active' => 'Actif',
                                                'on_leave' => 'En congé',
                                                'terminated' => 'Terminé',
                                            ])
                                            ->default('active')
                                            ->required(),
                                    ]),
                                
                                Forms\Components\Section::make('Accès Système')
                                    ->description('Création de compte utilisateur')
                                    ->schema([
                                        Forms\Components\Toggle::make('create_user')
                                            ->label('Créer un compte utilisateur')
                                            ->helperText('Permet à cet employé de se connecter à l\'application')
                                            ->live()
                                            ->dehydrated(false)
                                            ->visible(fn ($livewire) => $livewire instanceof Pages\CreateEmployee),
                                            
                                        Forms\Components\Grid::make(2)
                                            ->visible(fn (Forms\Get $get) => $get('create_user'))
                                            ->schema([
                                                Forms\Components\TextInput::make('password')
                                                    ->label('Mot de passe')
                                                    ->password()
                                                    ->revealable()
                                                    ->required(fn (Forms\Get $get) => $get('create_user'))
                                                    ->dehydrated(false),
                                                    
                                                Forms\Components\Select::make('role_id')
                                                    ->label('Rôle')
                                                    ->options(fn () => \App\Models\Role::where('company_id', Filament::getTenant()->id)->pluck('name', 'id'))
                                                    ->required(fn (Forms\Get $get) => $get('create_user'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->dehydrated(false),
                                            ]),
                                            
                                        Forms\Components\Placeholder::make('user_info')
                                            ->label('Compte utilisateur')
                                            ->content(fn (?Employee $record) => $record && $record->user ? $record->user->name . ' (' . $record->user->email . ')' : 'Aucun compte lié')
                                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditEmployee),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Contact urgence & Banque')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Forms\Components\Section::make("Contact d'urgence")
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('emergency_contact.name')
                                                    ->label('Nom'),
                                                Forms\Components\TextInput::make('emergency_contact.phone')
                                                    ->label('Téléphone')
                                                    ->tel(),
                                            ]),
                                    ]),
                                Forms\Components\Section::make('Coordonnées bancaires')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('bank_details.iban')
                                                    ->label('IBAN')
                                                    ->maxLength(34),
                                                Forms\Components\TextInput::make('bank_details.bic')
                                                    ->label('BIC')
                                                    ->maxLength(11),
                                            ]),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Notes')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notes internes')
                                    ->rows(5),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=7c3aed&color=fff'),
                Tables\Columns\TextColumn::make('employee_number')
                    ->label('Matricule')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nom complet')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Poste')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department')
                    ->label('Service')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Entrepôt')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('contract_type')
                    ->label('Contrat')
                    ->colors([
                        'success' => 'cdi',
                        'warning' => 'cdd',
                        'info' => 'interim',
                        'gray' => fn ($state) => in_array($state, ['stage', 'apprentissage']),
                    ])
                    ->formatStateUsing(fn ($state) => strtoupper($state)),
                Tables\Columns\TextColumn::make('hire_date')
                    ->label('Embauche')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Commission')
                    ->suffix('%')
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'on_leave',
                        'danger' => 'terminated',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'active' => 'Actif',
                        'on_leave' => 'En congé',
                        'terminated' => 'Terminé',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('user.is_active')
                    ->label('Compte actif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'on_leave' => 'En congé',
                        'terminated' => 'Terminé',
                    ]),
                Tables\Filters\SelectFilter::make('contract_type')
                    ->label('Type de contrat')
                    ->options([
                        'cdi' => 'CDI',
                        'cdd' => 'CDD',
                        'interim' => 'Intérim',
                        'stage' => 'Stage',
                        'apprentissage' => 'Apprentissage',
                        'freelance' => 'Freelance',
                    ]),
                Tables\Filters\SelectFilter::make('department')
                    ->label('Service')
                    ->options(fn () => Employee::where('company_id', Filament::getTenant()?->id)
                        ->whereNotNull('department')
                        ->distinct()
                        ->pluck('department', 'department')
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('clock_in')
                        ->label('Pointer entrée')
                        ->icon('heroicon-o-arrow-right-start-on-rectangle')
                        ->color('success')
                        ->action(fn (Employee $record) => $record->clockIn())
                        ->visible(fn (Employee $record) => $record->status === 'active'),
                    Tables\Actions\Action::make('clock_out')
                        ->label('Pointer sortie')
                        ->icon('heroicon-o-arrow-right-end-on-rectangle')
                        ->color('warning')
                        ->action(fn (Employee $record) => $record->clockOut())
                        ->visible(fn (Employee $record) => $record->status === 'active'),
                    Tables\Actions\Action::make('activate_account')
                        ->label('Activer le compte')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Activer le compte utilisateur')
                        ->modalDescription('L\'utilisateur pourra se connecter au système avec son email. Voulez-vous continuer ?')
                        ->action(function (Employee $record) {
                            if ($record->user) {
                                $record->user->update(['is_active' => true]);
                                Notification::make()
                                    ->title('Compte activé')
                                    ->body("Le compte de {$record->full_name} a été activé.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Aucun compte')
                                    ->body('Cet employé n\'a pas de compte utilisateur associé.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn (Employee $record) => $record->user && !$record->user->is_active),
                    Tables\Actions\Action::make('deactivate_account')
                        ->label('Désactiver le compte')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Désactiver le compte utilisateur')
                        ->modalDescription('L\'utilisateur ne pourra plus se connecter au système. Voulez-vous continuer ?')
                        ->action(function (Employee $record) {
                            if ($record->user) {
                                $record->user->update(['is_active' => false]);
                                Notification::make()
                                    ->title('Compte désactivé')
                                    ->body("Le compte de {$record->full_name} a été désactivé.")
                                    ->success()
                                    ->send();
                            }
                        })
                        ->visible(fn (Employee $record) => $record->user && $record->user->is_active),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('first_name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttendancesRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\CommissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Filament::getTenant()?->id);
    }
}
