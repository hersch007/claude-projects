<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('product_id')
                ->relationship('product', 'name')
                ->searchable()
                ->preload(),

            Forms\Components\TextInput::make('amount')
                ->required()
                ->numeric()
                ->prefix('$'),

            Forms\Components\Select::make('status')
                ->options([
                    'pending'  => 'Pending',
                    'paid'     => 'Paid',
                    'failed'   => 'Failed',
                    'refunded' => 'Refunded',
                ])
                ->required()
                ->default('pending'),

            Forms\Components\TextInput::make('transaction_id')
                ->label('Transaction ID')
                ->maxLength(255),

            Forms\Components\Select::make('payment_method')
                ->options([
                    'swipepay' => 'SwipePay',
                    'manual'   => 'Manual',
                    'imported' => 'Imported',
                ])
                ->default('swipepay'),

            Forms\Components\DateTimePicker::make('payment_date'),
            Forms\Components\DatePicker::make('renewal_date')->label('Renewal Date'),

            Forms\Components\Textarea::make('notes')->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')->searchable()->sortable()->label('Customer'),
                Tables\Columns\TextColumn::make('customer.email')->searchable()->label('Email'),
                Tables\Columns\TextColumn::make('product.name')->label('Product'),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger'  => 'failed',
                        'gray'    => 'refunded',
                    ]),
                Tables\Columns\TextColumn::make('payment_method')->label('Method'),
                Tables\Columns\TextColumn::make('payment_date')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'paid'     => 'Paid',
                        'failed'   => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'swipepay' => 'SwipePay',
                        'manual'   => 'Manual',
                        'imported' => 'Imported',
                    ]),
                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('payment_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('payment_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payment $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Payment $record) {
                        $record->markAsPaid();
                        Notification::make()->title('Payment marked as paid.')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\PaymentExporter::class)
                    ->label('Export CSV'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit'   => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
