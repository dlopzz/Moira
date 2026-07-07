<?php

namespace App\Filament\Pages\Settings;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SecuritySettings extends Page
{
    protected string $view = 'filament.pages.settings.security-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Seguridad';

    protected static ?string $title = 'Configuración de seguridad';

    protected static ?int $navigationSort = 12;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::instance();

        $this->form->fill([
            'recaptcha_enabled' => $settings->recaptcha_enabled,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Toggle::make('recaptcha_enabled')
                    ->label('Activar reCAPTCHA')
                    ->helperText('Muestra el widget "No soy un robot" en el formulario de registro y de contacto. Requiere configurar las claves en el servidor.'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::updateOrCreate([], ['recaptcha_enabled' => $data['recaptcha_enabled']]);

        Notification::make()
            ->success()
            ->title('Configuración guardada')
            ->send();
    }
}
