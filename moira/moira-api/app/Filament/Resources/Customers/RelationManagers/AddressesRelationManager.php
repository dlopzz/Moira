<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Direcciones';

    /** @var array<string, string> */
    private const PROVINCES = [
        'Buenos Aires'                                        => 'Buenos Aires',
        'Catamarca'                                           => 'Catamarca',
        'Chaco'                                               => 'Chaco',
        'Chubut'                                              => 'Chubut',
        'Córdoba'                                             => 'Córdoba',
        'Corrientes'                                          => 'Corrientes',
        'Entre Ríos'                                          => 'Entre Ríos',
        'Formosa'                                             => 'Formosa',
        'Jujuy'                                               => 'Jujuy',
        'La Pampa'                                            => 'La Pampa',
        'La Rioja'                                            => 'La Rioja',
        'Mendoza'                                             => 'Mendoza',
        'Misiones'                                            => 'Misiones',
        'Neuquén'                                             => 'Neuquén',
        'Río Negro'                                           => 'Río Negro',
        'Salta'                                               => 'Salta',
        'San Juan'                                            => 'San Juan',
        'San Luis'                                            => 'San Luis',
        'Santa Cruz'                                          => 'Santa Cruz',
        'Santa Fe'                                            => 'Santa Fe',
        'Santiago del Estero'                                 => 'Santiago del Estero',
        'Tierra del Fuego, Antártida e Islas del Atlántico Sur' => 'Tierra del Fuego',
        'Tucumán'                                             => 'Tucumán',
        'Ciudad Autónoma de Buenos Aires'                     => 'Ciudad Autónoma de Buenos Aires',
    ];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('label')
                    ->label('Alias')
                    ->placeholder('Casa, Trabajo, ...')
                    ->required()
                    ->maxLength(50),

                TextInput::make('telephone')
                    ->label('Teléfono')
                    ->tel()
                    ->required()
                    ->maxLength(30),

                TextInput::make('street')
                    ->label('Calle y número')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('address_line_2')
                    ->label('Piso / Depto / Interior')
                    ->nullable()
                    ->maxLength(100)
                    ->columnSpanFull(),

                TextInput::make('city')
                    ->label('Ciudad')
                    ->required()
                    ->maxLength(100),

                Select::make('state')
                    ->label('Provincia')
                    ->options(self::PROVINCES)
                    ->required()
                    ->searchable(),

                TextInput::make('zip_code')
                    ->label('Código postal')
                    ->required()
                    ->maxLength(20),

                TextInput::make('country')
                    ->label('País')
                    ->default('AR')
                    ->disabled()
                    ->dehydrated(),

                Toggle::make('is_default')
                    ->label('Dirección predeterminada')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Alias')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('street')
                    ->label('Dirección')
                    ->description(fn ($record) => implode(', ', array_filter([
                        $record->address_line_2,
                        $record->city,
                        $record->state,
                    ])))
                    ->wrap(),

                TextColumn::make('zip_code')
                    ->label('CP'),

                TextColumn::make('telephone')
                    ->label('Teléfono'),

                IconColumn::make('is_default')
                    ->label('Predeterminada')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus'),
            ])
            ->headerActions([
                CreateAction::make()->label('Nueva dirección'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
