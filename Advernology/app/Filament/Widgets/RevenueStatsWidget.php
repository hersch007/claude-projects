<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRevenue    = Payment::paid()->sum('amount');
        $monthRevenue    = Payment::paid()->whereMonth('payment_date', now()->month)->sum('amount');
        $totalCustomers  = Customer::count();
        $pendingPayments = Payment::pending()->count();

        return [
            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('All-time paid orders')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('This Month', '$' . number_format($monthRevenue, 2))
                ->description('Revenue this calendar month')
                ->color('primary')
                ->icon('heroicon-o-calendar'),

            Stat::make('Total Customers', number_format($totalCustomers))
                ->description('Registered customers')
                ->color('info')
                ->icon('heroicon-o-users'),

            Stat::make('Pending Payments', $pendingPayments)
                ->description('Awaiting payment')
                ->color('warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}
