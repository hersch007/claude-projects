<?php

namespace App\Filament\Exports;

use App\Models\Payment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PaymentExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('customer.name')->label('Customer Name'),
            ExportColumn::make('customer.email')->label('Customer Email'),
            ExportColumn::make('customer.domain')->label('Domain'),
            ExportColumn::make('product.name')->label('Product'),
            ExportColumn::make('amount')->label('Amount'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('payment_method')->label('Payment Method'),
            ExportColumn::make('payment_date')->label('Payment Date'),
            ExportColumn::make('renewal_date')->label('Renewal Date'),
            ExportColumn::make('transaction_id')->label('Transaction ID'),
            ExportColumn::make('created_at')->label('Created At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your payment export is ready. ' . number_format($export->successful_rows) . ' rows exported.';
    }
}
