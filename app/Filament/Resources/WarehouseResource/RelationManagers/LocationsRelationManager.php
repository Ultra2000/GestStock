<?php

namespace App\Filament\Resources\WarehouseResource\RelationManagers;

use App\Models\WarehouseLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';

    protected static ?string $title = 'Emplacements';
    protected static ?string $modelLabel = 'Emplacement';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('parent_id')
                    ->label('Emplacement parent')
                    ->options(fn () => $this->ownerRecord->locations()
                        ->pluck('name', 'id')
                        ->toArray())
                    ->searchable(),
                Forms\Components\TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(50)
                    ->unique(WarehouseLocation::class, 'code', ignoreRecord: true),
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->required()
                    ->options([
                        'zone' => 'Zone',
                        'aisle' => 'Allée',
                        'rack' => 'Rack',
                        'shelf' => 'Étagère',
                        'bin' => 'Emplacement',
                    ])
                    ->default('bin'),
                Forms\Components\TextInput::make('barcode')
                    ->label('Code-barres')
                    ->maxLength(100),
                Forms\Components\TextInput::make('capacity')
                    ->label('Capacité')
                    ->numeric()
                    ->helperText('Nombre maximum d\'unités'),
                Forms\Components\TextInput::make('max_weight')
                    ->label('Poids max (kg)')
                    ->numeric()
                    ->step(0.01),
                Forms\Components\Toggle::make('is_picking_location')
                    ->label('Emplacement de picking'),
                Forms\Components\Toggle::make('is_receiving_location')
                    ->label('Emplacement de réception'),
                Forms\Components\Toggle::make('is_shipping_location')
                    ->label('Emplacement d\'expédition'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'zone' => 'Zone',
                        'aisle' => 'Allée',
                        'rack' => 'Rack',
                        'shelf' => 'Étagère',
                        'bin' => 'Emplacement',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->default('-'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->state(fn (WarehouseLocation $record) => $record->getStock())
                    ->badge(),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacité')
                    ->default('-'),
                Tables\Columns\TextColumn::make('usage')
                    ->label('Utilisation')
                    ->state(fn (WarehouseLocation $record) => $record->getUsagePercent() . '%')
                    ->color(fn (WarehouseLocation $record) => match(true) {
                        $record->getUsagePercent() >= 90 => 'danger',
                        $record->getUsagePercent() >= 70 => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'zone' => 'Zone',
                        'aisle' => 'Allée',
                        'rack' => 'Rack',
                        'shelf' => 'Étagère',
                        'bin' => 'Emplacement',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = filament()->getTenant()->id;
                        return $data;
                    }),
                Tables\Actions\Action::make('generateBarcode')
                    ->label('Générer code-barres')
                    ->icon('heroicon-o-qr-code')
                    ->form([
                        Forms\Components\Select::make('location_id')
                            ->label('Emplacement')
                            ->options(fn () => $this->ownerRecord->locations()
                                ->whereNull('barcode')
                                ->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $location = WarehouseLocation::find($data['location_id']);
                        $location->update([
                            'barcode' => WarehouseLocation::generateBarcode($this->ownerRecord->id),
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code');
    }
}
