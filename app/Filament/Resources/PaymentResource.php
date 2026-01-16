<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Purchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Règlements';

    protected static ?string $modelLabel = 'Règlement';

    protected static ?string $pluralModelLabel = 'Règlements';

    protected static ?string $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du règlement')
                    ->schema([
                        Forms\Components\Select::make('payable_type')
                            ->label('Type de document')
                            ->options([
                                Sale::class => 'Vente (Client)',
                                Purchase::class => 'Achat (Fournisseur)',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('payable_id', null)),

                        Forms\Components\Select::make('payable_id')
                            ->label('Document')
                            ->options(function ($get) {
                                $type = $get('payable_type');
                                if (!$type) return [];

                                if ($type === Sale::class) {
                                    return Sale::where('company_id', filament()->getTenant()->id)
                                        ->where('status', 'completed')
                                        ->whereIn('payment_status', ['pending', 'partial'])
                                        ->pluck('invoice_number', 'id');
                                }

                                return Purchase::where('company_id', filament()->getTenant()->id)
                                    ->where('status', 'completed')
                                    ->pluck('reference', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $type = $get('payable_type');
                                if (!$state || !$type) return;

                                $document = $type::find($state);
                                if ($document) {
                                    $remaining = $document->total - ($document->amount_paid ?? 0);
                                    $set('amount', $remaining);
                                }
                            }),

                        Forms\Components\TextInput::make('amount')
                            ->label('Montant')
                            ->required()
                            ->numeric()
                            ->prefix('€')
                            ->minValue(0.01),

                        Forms\Components\Select::make('payment_method')
                            ->label('Mode de paiement')
                            ->options(Payment::METHODS)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $account = Payment::ACCOUNTS[$state] ?? '512000';
                                $set('account_number', $account);
                            }),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Date du règlement')
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->helperText('Numéro de chèque, référence virement...')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('account_number')
                            ->label('Compte comptable')
                            ->required()
                            ->default('512000')
                            ->helperText('512000 = Banque, 530000 = Caisse, 511200 = Chèques à encaisser'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state === Sale::class ? 'Client' : 'Fournisseur')
                    ->badge()
                    ->color(fn ($state) => $state === Sale::class ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('payable.invoice_number')
                    ->label('Document')
                    ->getStateUsing(function ($record) {
                        $payable = $record->payable;
                        if ($payable instanceof Sale) {
                            return $payable->invoice_number;
                        }
                        return $payable?->reference ?? '-';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Mode')
                    ->formatStateUsing(fn ($state) => Payment::METHODS[$state] ?? $state)
                    ->badge(),

                Tables\Columns\TextColumn::make('account_number')
                    ->label('Compte')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Mode de paiement')
                    ->options(Payment::METHODS),

                Tables\Filters\SelectFilter::make('payable_type')
                    ->label('Type')
                    ->options([
                        Sale::class => 'Clients',
                        Purchase::class => 'Fournisseurs',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('to')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('payment_date', '>=', $date))
                            ->when($data['to'], fn ($q, $date) => $q->whereDate('payment_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('payment_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', filament()->getTenant()?->id);
    }
}
