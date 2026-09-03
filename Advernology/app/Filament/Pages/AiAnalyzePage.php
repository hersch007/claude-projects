<?php

namespace App\Filament\Pages;

use App\Services\AnthropicService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AiAnalyzePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'AI Insights';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int    $navigationSort  = 9;
    protected static string  $view            = 'filament.pages.ai-analyze';

    public ?array $data    = ['days' => 30];
    public ?array $result = null;
    public bool $loading   = false;

    public function mount(): void
    {
        $this->form->fill(['days' => 30]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('days')
                    ->label('Analysis Period')
                    ->options([
                        7   => 'Last 7 days',
                        30  => 'Last 30 days',
                        90  => 'Last 90 days',
                        365 => 'Last 12 months',
                    ])
                    ->default(30),
            ])
            ->statePath('data');
    }

    public function analyze(): void
    {
        $days = $this->form->getState()['days'] ?? 30;

        try {
            $this->result = app(AnthropicService::class)->analyzePayments((int) $days);
            Notification::make()->title('Analysis complete.')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }
}
