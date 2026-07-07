<?php

namespace App\Filament\Resources\Orders\Returns;

use App\Filament\Resources\Orders\Returns\Pages\CreateOrderReturn;
use App\Filament\Resources\Orders\Returns\Pages\ListOrderReturns;
use App\Filament\Resources\Orders\Returns\Pages\ViewOrderReturn;
use App\Filament\Resources\Orders\Returns\Schemas\OrderReturnForm;
use App\Filament\Resources\Orders\Returns\Schemas\OrderReturnInfolist;
use App\Filament\Resources\Orders\Returns\Tables\OrderReturnsTable;
use App\Models\OrderReturn;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrderReturnResource extends Resource
{
    protected static ?string $model = OrderReturn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Órdenes';

    protected static ?string $navigationLabel = 'Devoluciones';

    protected static ?string $modelLabel = 'Devolución';

    protected static ?string $pluralModelLabel = 'Devoluciones';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return OrderReturnForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderReturnInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderReturnsTable::configure($table);
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderReturns::route('/'),
            'create' => CreateOrderReturn::route('/create'),
            'view' => ViewOrderReturn::route('/{record}'),
        ];
    }
}
