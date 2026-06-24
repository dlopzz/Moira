<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, callable $set) =>
                                $operation === 'create' ? $set('slug', Str::slug($state)) : null
                            ),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(Category::class, 'slug', ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('parent_id')
                            ->label('Categoría padre')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Sin padre (categoría raíz)')
                            ->nullable()
                            ->disabled(fn ($record) => $record !== null && $record->parent_id === null)
                            ->rules([
                                fn ($livewire) => function ($attribute, $value, $fail) use ($livewire) {
                                    if (! is_null($value)) {
                                        return;
                                    }
                                    $recordId = property_exists($livewire, 'record')
                                        ? $livewire->record?->getKey()
                                        : null;
                                    $exists = Category::whereNull('parent_id')
                                        ->when($recordId, fn ($q) => $q->whereKeyNot($recordId))
                                        ->exists();
                                    if ($exists) {
                                        $fail('Ya existe una categoría raíz. Solo puede haber una.');
                                    }
                                },
                            ]),

                        TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true),
                    ]),

                Section::make('Imagen')
                    ->components([
                        FileUpload::make('image')
                            ->label(false)
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('categories')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth(600)
                            ->imageResizeTargetHeight(600)
                            ->nullable(),
                    ]),

                Section::make('SEO')
                    ->description('Metadatos para motores de búsqueda')
                    ->collapsed()
                    ->components([
                        TextInput::make('meta_title')
                            ->label('Meta título')
                            ->nullable()
                            ->maxLength(70)
                            ->helperText('Recomendado: 50-70 caracteres'),

                        TextInput::make('meta_keywords')
                            ->label('Meta keywords')
                            ->nullable()
                            ->maxLength(255)
                            ->helperText('Separadas por coma'),

                        Textarea::make('meta_description')
                            ->label('Meta descripción')
                            ->nullable()
                            ->rows(3)
                            ->maxLength(160)
                            ->helperText('Recomendado: 120-160 caracteres'),
                    ]),
            ]);
    }
}
