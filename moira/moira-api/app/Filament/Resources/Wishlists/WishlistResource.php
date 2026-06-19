<?php

namespace App\Filament\Resources\Wishlists;

use App\Filament\Resources\Wishlists\Pages\ListWishlists;
use App\Filament\Resources\Wishlists\Pages\ViewWishlist;
use App\Filament\Resources\Wishlists\Tables\WishlistsTable;
use App\Models\Wishlist;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WishlistResource extends Resource
{
    protected static ?string $model = Wishlist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Wishlists';

    protected static ?string $modelLabel = 'Wishlist';

    protected static ?string $pluralModelLabel = 'Wishlists';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('customer.name')
                    ->label('Cliente'),

                TextEntry::make('customer.email')
                    ->label('Email'),

                TextEntry::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i'),

                TextEntry::make('products_count')
                    ->label('Total productos')
                    ->state(fn (Wishlist $record) => $record->products()->count()),

                RepeatableEntry::make('products')
                    ->label('Productos en la wishlist')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')->label('Nombre'),
                        TextEntry::make('sku')->label('SKU')->placeholder('—'),
                        TextEntry::make('price')->label('Precio')->money('ARS'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return WishlistsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
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
            'index' => ListWishlists::route('/'),
            'view'  => ViewWishlist::route('/{record}'),
        ];
    }
}
