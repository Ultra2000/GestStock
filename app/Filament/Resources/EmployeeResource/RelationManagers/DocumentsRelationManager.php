<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom du document')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        'contract' => 'Contrat',
                        'id_card' => "Pièce d'identité",
                        'diploma' => 'Diplôme',
                        'certificate' => 'Certificat',
                        'medical' => 'Certificat médical',
                        'driving_license' => 'Permis de conduire',
                        'other' => 'Autre',
                    ])
                    ->required(),
                Forms\Components\FileUpload::make('file_path')
                    ->label('Fichier')
                    ->required()
                    ->directory('employee-documents')
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(5120),
                Forms\Components\DatePicker::make('expiry_date')
                    ->label("Date d'expiration"),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'contract' => 'Contrat',
                        'id_card' => "Pièce d'identité",
                        'diploma' => 'Diplôme',
                        'certificate' => 'Certificat',
                        'medical' => 'Médical',
                        'driving_license' => 'Permis',
                        'other' => 'Autre',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : ($record->isExpiringSoon() ? 'warning' : null)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ajouté le')
                    ->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'contract' => 'Contrat',
                        'id_card' => "Pièce d'identité",
                        'diploma' => 'Diplôme',
                        'certificate' => 'Certificat',
                        'medical' => 'Certificat médical',
                        'other' => 'Autre',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
