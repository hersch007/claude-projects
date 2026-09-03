<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;

class SettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?string $title = 'Settings';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'mail_mailer'       => Setting::get('mail_mailer', 'smtp'),
            'mail_host'         => Setting::get('mail_host', ''),
            'mail_port'         => Setting::get('mail_port', '587'),
            'mail_username'     => Setting::get('mail_username', ''),
            'mail_password'     => Setting::get('mail_password', ''),
            'mail_encryption'   => Setting::get('mail_encryption', 'tls'),
            'mail_from_address' => Setting::get('mail_from_address', ''),
            'mail_from_name'    => Setting::get('mail_from_name', 'Advernology Service'),
            'test_email'        => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Mail Settings')
                    ->description('Configure outgoing email. Changes take effect immediately.')
                    ->schema([
                        Select::make('mail_mailer')
                            ->label('Mail Driver')
                            ->options([
                                'smtp' => 'SMTP',
                                'log'  => 'Log (testing only)',
                            ])
                            ->required(),

                        TextInput::make('mail_host')
                            ->label('SMTP Host')
                            ->placeholder('mail.yourdomain.com'),

                        TextInput::make('mail_port')
                            ->label('SMTP Port')
                            ->placeholder('587'),

                        Select::make('mail_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls'  => 'TLS',
                                'ssl'  => 'SSL',
                                'none' => 'None',
                            ]),

                        TextInput::make('mail_username')
                            ->label('SMTP Username')
                            ->placeholder('you@yourdomain.com'),

                        TextInput::make('mail_password')
                            ->label('SMTP Password')
                            ->password()
                            ->revealable(),

                        TextInput::make('mail_from_address')
                            ->label('From Email Address')
                            ->email()
                            ->placeholder('support@advernologyservice.com'),

                        TextInput::make('mail_from_name')
                            ->label('From Name')
                            ->placeholder('Advernology Service'),
                    ])->columns(2),

                Section::make('Send Test Email')
                    ->schema([
                        TextInput::make('test_email')
                            ->label('Send test to')
                            ->email()
                            ->placeholder('your@email.com'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $keys = [
            'mail_mailer', 'mail_host', 'mail_port', 'mail_username',
            'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name',
        ];

        foreach ($keys as $key) {
            if (isset($data[$key])) {
                Setting::set($key, $data[$key]);
            }
        }

        Notification::make()->title('Settings saved.')->success()->send();
    }

    public function sendTest(): void
    {
        $data = $this->form->getState();
        $to   = $data['test_email'] ?? null;

        if (! $to) {
            Notification::make()->title('Enter a test email address first.')->warning()->send();
            return;
        }

        $this->applyMailConfig($data);

        try {
            Mail::raw('This is a test email from Advernology Service admin panel.', function ($message) use ($to, $data) {
                $message->to($to)
                    ->from($data['mail_from_address'], $data['mail_from_name'])
                    ->subject('Advernology Service — Test Email');
            });

            Notification::make()->title("Test email sent to {$to}.")->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
        }
    }

    private function applyMailConfig(array $data): void
    {
        config([
            'mail.default'                    => $data['mail_mailer'] ?? 'smtp',
            'mail.mailers.smtp.host'          => $data['mail_host'] ?? '',
            'mail.mailers.smtp.port'          => $data['mail_port'] ?? 587,
            'mail.mailers.smtp.username'      => $data['mail_username'] ?? '',
            'mail.mailers.smtp.password'      => $data['mail_password'] ?? '',
            'mail.mailers.smtp.encryption'    => $data['mail_encryption'] === 'none' ? null : ($data['mail_encryption'] ?? 'tls'),
            'mail.from.address'               => $data['mail_from_address'] ?? '',
            'mail.from.name'                  => $data['mail_from_name'] ?? '',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label('Send Test Email')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->action('sendTest'),

            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('save'),
        ];
    }
}
