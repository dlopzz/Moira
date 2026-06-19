<?php

namespace App\Filament\Pages\Settings;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MoiraSettings extends Page
{
    protected string $view = 'filament.pages.settings.moira-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Moira';

    protected static ?string $title = 'Configuración del sitio';

    protected static ?int $navigationSort = 10;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::instance();

        $this->form->fill([
            'name' => $settings->name,
            'url' => $settings->url,
            'address' => $settings->address,
            'zip_code' => $settings->zip_code,
            'phone' => $settings->phone,
            'email' => $settings->email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nombre del sitio')
                    ->required()
                    ->maxLength(255),

                TextInput::make('url')
                    ->label('URL del sitio')
                    ->placeholder('https://mitienda.com')
                    ->url()
                    ->required()
                    ->maxLength(255),

                TextInput::make('address')
                    ->label('Dirección')
                    ->maxLength(255),

                TextInput::make('zip_code')
                    ->label('Código postal')
                    ->maxLength(20),

                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(30),

                TextInput::make('email')
                    ->label('Email de contacto')
                    ->email()
                    ->maxLength(255),
            ])
            ->columns(2)
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar configuración')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::updateOrCreate([], $data);

        Notification::make()
            ->success()
            ->title('Configuración guardada')
            ->send();
    }
}
