<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EligibleDomainResource\Pages;
use App\Models\EligibleDomain;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EligibleDomainResource extends Resource
{
    protected static ?string $model = EligibleDomain::class;
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Configuration';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('domain')
                ->label('Domain (e.g. lumos.com)')
                ->required()
                ->unique(ignoreRecord: true)
                ->lowercase()
                ->placeholder('example.com'),

            Forms\Components\TextInput::make('notes')
                ->label('Notes')
                ->maxLength(255),

            Forms\Components\Toggle::make('active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('notes')->limit(40),
                Tables\Columns\IconColumn::make('active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEligibleDomains::route('/'),
            'create' => Pages\CreateEligibleDomain::route('/create'),
            'edit'   => Pages\EditEligibleDomain::route('/{record}/edit'),
        ];
    }
}
