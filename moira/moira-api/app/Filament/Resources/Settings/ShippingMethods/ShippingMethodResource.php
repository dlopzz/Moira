<?php

namespace App\Filament\Resources\Settings\ShippingMethods;

use App\Filament\Resources\Settings\ShippingMethods\Pages\CreateShippingMethod;
use App\Filament\Resources\Settings\ShippingMethods\Pages\EditShippingMethod;
use App\Filament\Resources\Settings\ShippingMethods\Pages\ListShippingMethods;
use App\Filament\Resources\Settings\ShippingMethods\Schemas\ShippingMethodForm;
use App\Filament\Resources\Settings\ShippingMethods\Tables\ShippingMethodsTable;
use App\Models\ShippingMethod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Métodos de envío';

    protected static ?string $modelLabel = 'Método de envío';

    protected static ?string $pluralModelLabel = 'Métodos de envío';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ShippingMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingMethodsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListShippingMethods::route('/'),
            'create' => CreateShippingMethod::route('/create'),
            'edit'   => EditShippingMethod::route('/{record}/edit'),
        ];
    }
}
