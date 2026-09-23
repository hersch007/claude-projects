<?php
namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $title = 'Order History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label('Package'),
                Tables\Columns\TextColumn::make('amount')->money('usd'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger'  => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('email_addresses')
                    ->label('Email Addresses')
                    ->formatStateUsing(fn ($state) => $state ? implode(', ', $state) : '—')
                    ->wrap(),
                Tables\Columns\TextColumn::make('payment_date')->dateTime('M j, Y')->label('Paid')->default('—'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('M j, Y')->label('Ordered'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
