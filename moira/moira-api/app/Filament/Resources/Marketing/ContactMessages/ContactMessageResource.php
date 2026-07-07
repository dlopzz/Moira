<?php

namespace App\Filament\Resources\Marketing\ContactMessages;

use App\Filament\Resources\Marketing\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\Marketing\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Resources\Marketing\ContactMessages\Tables\ContactMessagesTable;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Mensajes';

    protected static ?string $modelLabel = 'Mensaje';

    protected static ?string $pluralModelLabel = 'Mensajes de Contacto';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return ContactMessagesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}
