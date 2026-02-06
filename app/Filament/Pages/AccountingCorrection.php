<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AccountingEntryResource;
use App\Models\AccountingEntry;
use App\Models\AccountingSetting;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;

class AccountingCorrection extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static string $view = 'filament.pages.accounting-correction';
    protected static ?string $navigationLabel = 'Écritures OD';
    protected static ?string $title = 'Opérations Diverses & Corrections';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?int $navigationSort = 8;

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('accounting') ?? true;
    }

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant?->isModuleEnabled('accounting')) return false;
        
        $user = auth()->user();
        if (!$user) return false;
        
        return $user->isAdmin() || $user->hasPermission('accounting.manage');
    }

    public ?array $data = [];
    public ?array $lines = [];

    public function mount(): void
    {
        $this->form->fill([
            'entry_date' => now()->format('Y-m-d'),
            'journal_code' => 'OD',
        ]);
        
        $this->lines = [
            ['account_number' => '', 'label' => '', 'debit' => 0, 'credit' => 0],
            ['account_number' => '', 'label' => '', 'debit' => 0, 'credit' => 0],
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Nouvelle écriture OD')
                    ->description('Créez des écritures manuelles pour corrections, reclassements ou régularisations')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('entry_date')
                                    ->label('Date de l\'écriture')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Select::make('journal_code')
                                    ->label('Journal')
                                    ->options([
                                        'OD' => 'OD - Opérations Diverses',
                                        'AN' => 'AN - À Nouveau',
                                        'EX' => 'EX - Extourne',
                                    ])
                                    ->default('OD')
                                    ->required(),

                                Forms\Components\TextInput::make('piece_number')
                                    ->label('N° de pièce')
                                    ->placeholder('Auto-généré si vide')
                                    ->helperText('Laissez vide pour auto-génération'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Motif de l\'écriture')
                            ->placeholder('Ex: Reclassement compte 707000 vers 706000 suite à changement de paramétrage')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Lignes d\'écriture')
                    ->description('Le total des débits doit être égal au total des crédits')
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('account_number')
                                    ->label('N° Compte')
                                    ->required()
                                    ->maxLength(10)
                                    ->placeholder('Ex: 707000')
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('account_auxiliary')
                                    ->label('Auxiliaire')
                                    ->maxLength(20)
                                    ->placeholder('Optionnel'),

                                Forms\Components\TextInput::make('label')
                                    ->label('Libellé')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('debit')
                                    ->label('Débit')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('€')
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('credit')
                                    ->label('Crédit')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('€')
                                    ->live(onBlur: true),
                            ])
                            ->columns(6)
                            ->minItems(2)
                            ->defaultItems(2)
                            ->addActionLabel('Ajouter une ligne')
                            ->reorderable(false)
                            ->collapsible(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        
        // Validation de l'équilibre
        $totalDebit = collect($data['lines'])->sum('debit');
        $totalCredit = collect($data['lines'])->sum('credit');
        
        if (abs($totalDebit - $totalCredit) > 0.01) {
            Notification::make()
                ->title('Écriture déséquilibrée')
                ->body("Total Débit: {$totalDebit}€ ≠ Total Crédit: {$totalCredit}€")
                ->danger()
                ->send();
            return;
        }

        if ($totalDebit == 0 && $totalCredit == 0) {
            Notification::make()
                ->title('Écriture vide')
                ->body('Veuillez saisir au moins un montant')
                ->danger()
                ->send();
            return;
        }

        // Génération du numéro de pièce si non fourni
        $pieceNumber = $data['piece_number'] ?: $this->generatePieceNumber($data['journal_code']);
        
        $companyId = filament()->getTenant()->id;

        DB::beginTransaction();

        try {
            $entriesCreated = 0;
            
            foreach ($data['lines'] as $line) {
                if (empty($line['account_number'])) continue;
                if ($line['debit'] == 0 && $line['credit'] == 0) continue;

                AccountingEntry::create([
                    'company_id' => $companyId,
                    'entry_date' => $data['entry_date'],
                    'piece_number' => $pieceNumber,
                    'journal_code' => $data['journal_code'],
                    'account_number' => $line['account_number'],
                    'account_auxiliary' => $line['account_auxiliary'] ?? null,
                    'label' => $line['label'] . ($data['description'] ? " - {$data['description']}" : ''),
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'creation_source' => 'manual',
                    'created_by' => auth()->id(),
                ]);
                
                $entriesCreated++;
            }

            DB::commit();

            Notification::make()
                ->title('Écriture OD créée')
                ->body("{$entriesCreated} lignes créées avec le n° {$pieceNumber}")
                ->success()
                ->send();

            // Reset du formulaire
            $this->form->fill([
                'entry_date' => now()->format('Y-m-d'),
                'journal_code' => 'OD',
                'piece_number' => '',
                'description' => '',
                'lines' => [
                    ['account_number' => '', 'label' => '', 'debit' => 0, 'credit' => 0],
                    ['account_number' => '', 'label' => '', 'debit' => 0, 'credit' => 0],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Erreur')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function createReclassement(): void
    {
        // Pré-remplir pour un reclassement type
        $this->form->fill([
            'entry_date' => now()->format('Y-m-d'),
            'journal_code' => 'OD',
            'description' => 'Reclassement comptable',
            'lines' => [
                ['account_number' => '', 'label' => 'Contre-passation ancien compte', 'debit' => 0, 'credit' => 0],
                ['account_number' => '', 'label' => 'Imputation nouveau compte', 'debit' => 0, 'credit' => 0],
            ],
        ]);
    }

    protected function generatePieceNumber(string $journal): string
    {
        $companyId = filament()->getTenant()->id;
        $year = date('Y');
        $prefix = "{$journal}-{$year}-";
        
        $lastNumber = AccountingEntry::where('company_id', $companyId)
            ->where('piece_number', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(piece_number, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as max_num")
            ->value('max_num') ?? 0;
        
        return $prefix . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('reclassement')
                ->label('Modèle Reclassement')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(fn () => $this->createReclassement()),
                
            \Filament\Actions\Action::make('voir_grand_livre')
                ->label('Voir Grand Livre')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->url(fn () => AccountingEntryResource::getUrl('index')),
        ];
    }
}
