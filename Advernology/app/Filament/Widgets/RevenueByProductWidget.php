<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RevenueByProductWidget extends BaseWidget
{
    protected static ?string $heading = 'Revenue by Product';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()->withCount('payments')->withSum('payments', 'amount')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Unit Price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payments_count')
                    ->label('Orders')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payments_sum_amount')
                    ->label('Total Revenue')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('emails_count')
                    ->label('Emails / Plan')
                    ->sortable(),
            ])
            ->defaultSort('payments_sum_amount', 'desc');
    }
}
