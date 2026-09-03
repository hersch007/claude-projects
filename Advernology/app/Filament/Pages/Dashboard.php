<?php

namespace App\Filament\Pages;

use App\Services\AnthropicService;
use App\Services\LumosImportService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Dashboard extends BaseDashboard
{
    use InteractsWithForms;

    public ?string $aiInsights = null;
    public bool $loadingAi = false;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\RevenueStatsWidget::class,
            \App\Filament\Widgets\RevenueChartWidget::class,
            \App\Filament\Widgets\RecentPaymentsWidget::class,
            \App\Filament\Widgets\RevenueByProductWidget::class,
            \App\Filament\Widgets\ExpiringRenewalsWidget::class,
        ];
    }

    public function analyzeWithAi(): void
    {
        $this->loadingAi = true;
        $service = app(AnthropicService::class);
        $this->aiInsights = $service->analyzePayments(30);
        $this->loadingAi  = false;

        Notification::make()->title('AI analysis complete.')->success()->send();
    }

    public function importLumosFile(array $data): void
    {
        $file    = $data['file'] ?? null;
        if (! $file) {
            Notification::make()->title('No file selected.')->warning()->send();
            return;
        }

        // Resolve from Livewire temp
        if ($file instanceof TemporaryUploadedFile) {
            $uploaded = $file;
        } else {
            $uploaded = collect((array) $file)->first();
        }

        $service = app(LumosImportService::class);
        $log     = $service->import($uploaded, auth()->id());

        Notification::make()
            ->title("Import complete: {$log->rows_imported} rows imported, {$log->rows_skipped} skipped.")
            ->success()
            ->send();
    }
}
