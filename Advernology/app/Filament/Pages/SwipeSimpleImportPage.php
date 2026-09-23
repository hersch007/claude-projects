<?php

namespace App\Filament\Pages;

use App\Models\ImportLog;
use App\Services\SwipeSimpleImportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class SwipeSimpleImportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Import SwipeSimple Transactions';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int    $navigationSort  = 10;
    protected static string  $view            = 'filament.pages.swipesimple-import';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('file')
                    ->label('Upload SwipeSimple Transactions Export')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                    // Cardholder names and partial card numbers pass through this
                    // file — keep it off the public disk, not web-accessible.
                    ->disk('local')
                    ->directory('swipesimple-imports')
                    ->visibility('private')
                    ->required(),

                DatePicker::make('since')
                    ->label('Only Import Transactions On/After')
                    ->helperText('Leave blank to import every transaction in the file.'),
            ])
            ->statePath('data');
    }

    public function import(): void
    {
        $data = $this->form->getState();
        $file = $data['file'] ?? null;

        if (! $file) {
            Notification::make()->title('Please select a file.')->warning()->send();
            return;
        }

        // By the time getState() runs, Filament's FileUpload has already
        // saved the file permanently to the disk configured above — $file
        // here is just that filename (relative to the 'local' disk), not a
        // raw Livewire temp upload reference.
        $path = is_string($file) ? Storage::disk('local')->path($file) : null;

        if (! $path || ! is_readable($path)) {
            Notification::make()
                ->title('That upload seems to have expired or gotten corrupted. Please reload this page and re-upload the file.')
                ->danger()
                ->send();
            return;
        }

        $uploaded = new \Illuminate\Http\UploadedFile($path, basename($path), null, null, true);

        try {
            $service = app(SwipeSimpleImportService::class);
            $log     = $service->import($uploaded, auth()->id(), $data['since'] ?? null);

            Notification::make()
                ->title("Import complete! {$log->rows_imported} imported, {$log->rows_refunded} refunded, {$log->rows_skipped} skipped.")
                ->success()
                ->send();
        } finally {
            // Don't leave transaction data (cardholder names, card fragments) sitting in storage.
            Storage::disk('local')->delete($file);
        }

        $this->form->fill();
    }

    public function getLogs()
    {
        return ImportLog::latest()->take(10)->get();
    }
}
