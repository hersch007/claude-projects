<?php

namespace App\Filament\Pages;

use App\Models\ImportLog;
use App\Services\LumosImportService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Import Lumos Data';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int    $navigationSort  = 10;
    protected static string  $view            = 'filament.pages.import-page';

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
                    ->label('Upload Lumos Excel/CSV File')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/vnd.ms-excel'])
                    ->required(),
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

        // Resolve Livewire temp file
        if (is_string($file)) {
            $path     = storage_path('app/livewire-tmp/' . $file);
            $uploaded = new \Illuminate\Http\UploadedFile($path, $file);
        } else {
            $uploaded = $file;
        }

        $service = app(LumosImportService::class);
        $log     = $service->import($uploaded, auth()->id());

        Notification::make()
            ->title("Import complete! {$log->rows_imported} imported, {$log->rows_skipped} skipped.")
            ->success()
            ->send();

        $this->form->fill();
    }

    public function getLogs()
    {
        return ImportLog::latest()->take(10)->get();
    }
}
