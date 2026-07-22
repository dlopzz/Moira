<?php

namespace App\Filament\Pages\Settings;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
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
            'logo' => $settings->logo ? [$settings->logo] : [],
            'promo_text' => $settings->promo_text,
            'url' => $settings->url,
            'address' => $settings->address,
            'zip_code' => $settings->zip_code,
            'phone' => $settings->phone,
            'email' => $settings->email,
            'social_facebook' => $settings->social_facebook,
            'social_instagram' => $settings->social_instagram,
            'social_whatsapp' => $settings->social_whatsapp,
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

                FileUpload::make('logo')
                    ->label('Logo del sitio')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('logos')
                    ->nullable()
                    ->columnSpanFull()
                    ->helperText('Imagen que aparece en el header y footer. Formatos admitidos: PNG, JPG, WebP. Proporción recomendada: horizontal (ej: 312×70 px).'),

                TextInput::make('promo_text')
                    ->label('Texto promocional del header')
                    ->nullable()
                    ->maxLength(200)
                    ->columnSpanFull()
                    ->placeholder('NEW OFFER THIS WEEKEND ONLY! HURRY SHOP NOW!')
                    ->helperText('Aparece en la barra superior del header. Dejalo vacío para ocultarla.'),

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
                    ->required()
                    ->maxLength(30)
                    ->helperText('Se muestra en el header del sitio. Requerido para que aparezca ese bloque de contacto.'),

                TextInput::make('email')
                    ->label('Email de contacto')
                    ->email()
                    ->maxLength(255),

                TextInput::make('social_facebook')
                    ->label('Facebook')
                    ->url()
                    ->nullable()
                    ->maxLength(255)
                    ->placeholder('https://facebook.com/mitienda')
                    ->helperText('Link del ícono de Facebook en el header. Vacío = se oculta.'),

                TextInput::make('social_instagram')
                    ->label('Instagram')
                    ->url()
                    ->nullable()
                    ->maxLength(255)
                    ->placeholder('https://instagram.com/mitienda')
                    ->helperText('Link del ícono de Instagram en el header. Vacío = se oculta.'),

                TextInput::make('social_whatsapp')
                    ->label('WhatsApp')
                    ->url()
                    ->nullable()
                    ->maxLength(255)
                    ->placeholder('https://wa.me/54911...')
                    ->helperText('Link del ícono de WhatsApp en el header. Vacío = se oculta.'),
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

        // FileUpload returns an array; flatten to a single path or null
        if (isset($data['logo'])) {
            $data['logo'] = is_array($data['logo']) ? (array_values($data['logo'])[0] ?? null) : $data['logo'];
        }

        // These columns are NOT NULL in the DB — coerce nulls to empty string
        foreach (['address', 'zip_code', 'phone', 'email'] as $field) {
            $data[$field] = $data[$field] ?? '';
        }

        SiteSetting::updateOrCreate([], $data);

        Notification::make()
            ->success()
            ->title('Configuración guardada')
            ->send();
    }
}
