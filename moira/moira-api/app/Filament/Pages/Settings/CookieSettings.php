<?php

namespace App\Filament\Pages\Settings;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CookieSettings extends Page
{
    protected string $view = 'filament.pages.settings.security-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Cookie Notice';

    protected static ?string $title = 'Aviso de cookies';

    protected static ?int $navigationSort = 13;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::instance();

        $this->form->fill([
            'cookie_notice_enabled' => $settings->cookie_notice_enabled,
            'cookie_notice_text' => $settings->cookie_notice_text,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Toggle::make('cookie_notice_enabled')
                    ->label('Mostrar aviso de cookies')
                    ->helperText('Activa el popup de cookie notice en el sitio. El aviso se muestra una vez cada 365 días por usuario.'),

                Textarea::make('cookie_notice_text')
                    ->label('Texto del aviso')
                    ->helperText('Mensaje que verá el usuario. Podés incluir el nombre de la tienda, el tipo de cookies y un link a la política de privacidad.')
                    ->rows(4)
                    ->maxLength(1000),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::updateOrCreate([], [
            'cookie_notice_enabled' => $data['cookie_notice_enabled'],
            'cookie_notice_text' => $data['cookie_notice_text'],
        ]);

        Notification::make()
            ->success()
            ->title('Configuración guardada')
            ->send();
    }
}
