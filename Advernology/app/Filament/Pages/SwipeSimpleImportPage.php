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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/vnd.ms-excel'])
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

        // Resolve Livewire temp file. Use Livewire's own resolver rather than
        // guessing the storage path — it correctly locates the file on
        // whichever disk config('livewire.temporary_file_upload.disk') /
        // filesystems.default actually is, instead of assuming 'local'.
        $uploaded = is_string($file)
            ? TemporaryUploadedFile::createFromLivewire($file)
            : $file;

        $service = app(SwipeSimpleImportService::class);
        $log     = $service->import($uploaded, auth()->id(), $data['since'] ?? null);

        Notification::make()
            ->title("Import complete! {$log->rows_imported} imported, {$log->rows_refunded} refunded, {$log->rows_skipped} skipped.")
            ->success()
            ->send();

        $this->form->fill();
    }

    public function getLogs()
    {
        return ImportLog::latest()->take(10)->get();
    }
}
