<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, callable $set) =>
                        $operation === 'create' ? $set('slug', Str::slug($state)) : null
                    ),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(Product::class, 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('sku')
                    ->label('SKU')
                    ->unique(Product::class, 'sku', ignoreRecord: true)
                    ->nullable()
                    ->maxLength(100)
                    ->placeholder('Ej: CAM-001-NEG')
                    ->columnSpanFull(),

                Select::make('product_type')
                    ->label('Tipo de producto')
                    ->options(['simple' => 'Simple', 'configurable' => 'Configurable'])
                    ->default('simple')
                    ->required()
                    ->live()
                    ->columnSpanFull()
                    ->helperText('Simple: un único SKU/stock. Configurable: múltiples variantes (color, talle, etc.).'),

                /* Price — on configurables this is the "from" reference price */
                TextInput::make('price')
                    ->label(fn (Get $get) => $get('product_type') === 'configurable' ? 'Precio base (referencia)' : 'Precio')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0),

                /* Sale price — hidden for configurable (managed per variant) */
                TextInput::make('sale_price')
                    ->label('Precio oferta')
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0)
                    ->nullable()
                    ->hidden(fn (Get $get) => $get('product_type') === 'configurable')
                    ->rules([
                        fn ($get) => function ($attribute, $value, $fail) use ($get) {
                            if ($value !== null && $value >= $get('price')) {
                                $fail('El precio oferta debe ser menor al precio normal.');
                            }
                        },
                    ]),

                /* Stock — hidden for configurable (managed per variant) */
                TextInput::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->required(fn (Get $get) => $get('product_type') === 'simple')
                    ->hidden(fn (Get $get) => $get('product_type') === 'configurable'),

                Select::make('categories')
                    ->label('Categorías')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->name)
                    ->nullable()
                    ->columnSpanFull(),

                Textarea::make('short_description')
                    ->label('Descripción corta')
                    ->rows(2)
                    ->nullable()
                    ->maxLength(500)
                    ->helperText('Se muestra en listados y previews. Máx. 500 caracteres.')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Descripción completa')
                    ->rows(4)
                    ->nullable()
                    ->columnSpanFull(),

                FileUpload::make('images')
                    ->label('Imágenes')
                    ->image()
                    ->multiple()
                    ->maxFiles(5)
                    ->reorderable()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('products')
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth(800)
                    ->imageResizeTargetHeight(800)
                    ->columnSpanFull()
                    ->helperText('Máximo 5 imágenes. La primera es la imagen principal.'),

                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->columnSpanFull(),

                Select::make('relatedProducts')
                    ->label('Productos relacionados')
                    ->relationship('relatedProducts', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->nullable()
                    ->columnSpanFull()
                    ->helperText('Se muestran al pie del producto en la tienda. Máximo recomendado: 6.'),

                Section::make('SEO')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('Meta título')
                            ->nullable()
                            ->maxLength(70)
                            ->helperText('Si está vacío se usa el nombre del producto. Máx. 70 caracteres.'),

                        Textarea::make('meta_description')
                            ->label('Meta descripción')
                            ->nullable()
                            ->rows(2)
                            ->maxLength(160)
                            ->helperText('Si está vacía se usa la descripción corta. Máx. 160 caracteres.'),
                    ]),

                Section::make('Variantes')
                    ->description('Cada variante define una combinación de atributos con su propio SKU, precio y stock.')
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('product_type') === 'configurable')
                    ->schema([
                        Repeater::make('variants')
                            ->relationship()
                            ->label('')
                            ->addActionLabel('Agregar variante')
                            ->orderColumn('sort_order')
                            ->defaultItems(0)
                            ->columns(3)
                            ->schema([
                                KeyValue::make('attributes')
                                    ->label('Atributos')
                                    ->columnSpanFull()
                                    ->keyLabel('Atributo (ej: Color)')
                                    ->valueLabel('Valor (ej: Rojo)')
                                    ->required()
                                    ->helperText('Definí los atributos que identifican esta variante.'),

                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->maxLength(100)
                                    ->nullable()
                                    ->unique(ProductVariant::class, 'sku', ignoreRecord: true),

                                TextInput::make('price')
                                    ->label('Precio override')
                                    ->numeric()
                                    ->prefix('$')
                                    ->nullable()
                                    ->helperText('Vacío = hereda precio del producto'),

                                TextInput::make('stock')
                                    ->label('Stock')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),

                                Toggle::make('is_active')
                                    ->label('Activa')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }
}
