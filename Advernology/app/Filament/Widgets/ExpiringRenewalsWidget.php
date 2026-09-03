<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ExpiringRenewalsWidget extends BaseWidget
{
    protected static ?string $heading = 'Expiring Renewals (Next 90 Days)';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->with(['customer', 'product'])
                    ->where('status', 'paid')
                    ->whereNotNull('renewal_date')
                    ->whereBetween('renewal_date', [now(), now()->addDays(90)])
                    ->orderBy('renewal_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('renewal_date')
                    ->label('Renewal Date')
                    ->date()
                    ->sortable()
                    ->color(fn (Payment $record) => match(true) {
                        $record->renewal_date->lte(now()->addDays(30)) => 'danger',
                        $record->renewal_date->lte(now()->addDays(60)) => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('days_until_renewal')
                    ->label('Days Left')
                    ->state(fn (Payment $record) => now()->diffInDays($record->renewal_date, false) . ' days'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('window')
                    ->label('Renewal Window')
                    ->options([
                        '30' => 'Next 30 days',
                        '60' => 'Next 60 days',
                        '90' => 'Next 90 days',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            $query->whereBetween('renewal_date', [now(), now()->addDays((int) $data['value'])]);
                        }
                    }),
            ]);
    }
}
