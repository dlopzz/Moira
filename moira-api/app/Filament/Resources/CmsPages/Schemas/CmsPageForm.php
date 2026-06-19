<?php

namespace App\Filament\Resources\CmsPages\Schemas;

use App\Models\CmsPage;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CmsPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, callable $set) =>
                        $operation === 'create' ? $set('slug', Str::slug($state)) : null
                    ),

                TextInput::make('subtitle')
                    ->label('Subtítulo')
                    ->maxLength(255)
                    ->nullable()
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(CmsPage::class, 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->helperText('Ej: terminos-y-condiciones → moira.com/pages/terminos-y-condiciones'),

                RichEditor::make('content')
                    ->label('Contenido')
                    ->columnSpanFull()
                    ->nullable(),

                Grid::make(3)
                    ->columnSpanFull()
                    ->components([
                        Toggle::make('is_active')
                            ->label('Página activa')
                            ->default(true),

                        Toggle::make('show_in_footer')
                            ->label('Mostrar en footer')
                            ->default(false),

                        TextInput::make('sort_order')
                            ->label('Orden en footer')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Menor número = primero'),
                    ]),
            ]);
    }
}
