<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AiAnalyzePage;
use App\Filament\Pages\ImportPage;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\EligibleDomainResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Widgets\RecentPaymentsWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\RevenueStatsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(['primary' => Color::Indigo])
            ->brandName('Advernology Admin')
            ->resources([
                EligibleDomainResource::class,
                ProductResource::class,
                CustomerResource::class,
                PaymentResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                ImportPage::class,
                AiAnalyzePage::class,
            ])
            ->widgets([
                RevenueStatsWidget::class,
                RevenueChartWidget::class,
                RecentPaymentsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
