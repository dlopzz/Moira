<?php

namespace App\Filament\Pages\Settings;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CartSettings extends Page
{
    protected string $view = 'filament.pages.settings.cart-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Carrito';

    protected static ?string $title = 'Configuración del carrito';

    protected static ?int $navigationSort = 11;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::instance();

        $this->form->fill([
            'cart_expiration_days' => $settings->cart_expiration_days,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('cart_expiration_days')
                    ->label('Días de expiración del carrito')
                    ->helperText('El carrito se marcará como vencido si no hay actividad durante este período.')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(365)
                    ->required()
                    ->suffix('días'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::updateOrCreate([], ['cart_expiration_days' => $data['cart_expiration_days']]);

        Notification::make()
            ->success()
            ->title('Configuración guardada')
            ->send();
    }
}
