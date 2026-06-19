<?php

namespace App\Filament\Resources\Settings\PaymentMethods;

use App\Filament\Resources\Settings\PaymentMethods\Pages\CreatePaymentMethod;
use App\Filament\Resources\Settings\PaymentMethods\Pages\EditPaymentMethod;
use App\Filament\Resources\Settings\PaymentMethods\Pages\ListPaymentMethods;
use App\Filament\Resources\Settings\PaymentMethods\Schemas\PaymentMethodForm;
use App\Filament\Resources\Settings\PaymentMethods\Tables\PaymentMethodsTable;
use App\Models\PaymentMethod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Métodos de pago';

    protected static ?string $modelLabel = 'Método de pago';

    protected static ?string $pluralModelLabel = 'Métodos de pago';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PaymentMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentMethodsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPaymentMethods::route('/'),
            'create' => CreatePaymentMethod::route('/create'),
            'edit'   => EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
