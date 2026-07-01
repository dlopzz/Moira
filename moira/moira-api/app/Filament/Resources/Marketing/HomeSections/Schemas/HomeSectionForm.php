<?php

namespace App\Filament\Resources\Marketing\HomeSections\Schemas;

use App\Filament\Support\WebpConverter;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class HomeSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Configuración general')->columns(2)->components([

                Select::make('type')
                    ->label('Tipo de sección')
                    ->options([
                        'hero_slider'  => '🖼 Galería / Slider',
                        'product_tabs' => '🏷 Tabs de productos',
                        'banner'       => '📢 Banner promocional',
                    ])
                    ->required()
                    ->live()
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->label('Título de sección')
                    ->placeholder('Ej: TOP PRODUCT')
                    ->hidden(fn (Get $get) => $get('type') === 'hero_slider')
                    ->maxLength(255)
                    ->nullable()
                    ->columnSpanFull(),

                TextInput::make('sort_order')
                    ->label('Orden (menor = primero)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                Toggle::make('is_active')
                    ->label('Sección activa')
                    ->default(true),

            ]),

            // ─── HERO SLIDER ───────────────────────────────────────────
            Section::make('Slides')
                ->description('Agregá y reordenás los slides de la galería.')
                ->visible(fn (Get $get) => $get('type') === 'hero_slider')
                ->components([
                    Repeater::make('slides')
                        ->label('')
                        ->addActionLabel('Agregar slide')
                        ->reorderableWithDragAndDrop()
                        ->defaultItems(0)
                        ->columns(2)
                        ->schema([
                            FileUpload::make('image')
                                ->label('Imagen')
                                ->image()
                                ->disk('public')
                                ->directory('home-slides')
                                ->visibility('public')
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('8:3')
                                ->imageResizeTargetWidth(1920)
                                ->imageResizeTargetHeight(720)
                                ->saveUploadedFileUsing(WebpConverter::saveAs('home-slides'))
                                ->helperText('Imagen panorámica horizontal (1920×720px). Se recortará automáticamente.')
                                ->required()
                                ->columnSpanFull(),

                            TextInput::make('title')
                                ->label('Título')
                                ->placeholder('Ej: Nueva Colección Verano')
                                ->nullable(),

                            TextInput::make('subtitle')
                                ->label('Subtítulo')
                                ->placeholder('Ej: Envío gratis en pedidos +$50.000')
                                ->nullable(),

                            TextInput::make('button_text')
                                ->label('Texto del botón')
                                ->placeholder('Ej: Ver colección')
                                ->nullable(),

                            TextInput::make('button_link')
                                ->label('Link del botón')
                                ->placeholder('Ej: /categories/bikinis')
                                ->url()
                                ->nullable(),

                            Select::make('transition')
                                ->label('Efecto de transición')
                                ->options([
                                    'fade'     => 'Fade — desvanecimiento',
                                    'slide'    => 'Slide — deslizamiento horizontal',
                                    'zoom'     => 'Zoom — acercamiento',
                                    'vertical' => 'Vertical — deslizamiento arriba/abajo',
                                    'flip'     => 'Flip — giro 3D',
                                    'blur'     => 'Blur — desenfoque',
                                    'wipe'     => 'Wipe — revelado lateral',
                                ])
                                ->default('fade')
                                ->nullable()
                                ->columnSpanFull(),
                        ]),
                ]),

            // ─── PRODUCT TABS ───────────────────────────────────────────
            Section::make('Tabs de productos')
                ->description('Configurá cada tab: elegí qué productos muestra y cómo se llama.')
                ->visible(fn (Get $get) => $get('type') === 'product_tabs')
                ->components([

                    TextInput::make('per_tab')
                        ->label('Productos por tab')
                        ->numeric()
                        ->default(8)
                        ->minValue(1)
                        ->maxValue(24)
                        ->helperText('Cuántos productos se muestran en cada tab.'),

                    Repeater::make('tabs')
                        ->label('Tabs')
                        ->addActionLabel('Agregar tab')
                        ->reorderableWithDragAndDrop()
                        ->defaultItems(0)
                        ->columns(2)
                        ->schema([

                            TextInput::make('label')
                                ->label('Etiqueta del tab')
                                ->placeholder('Ej: NOVEDADES')
                                ->required(),

                            Select::make('type')
                                ->label('Fuente de productos')
                                ->options([
                                    'latest'       => 'Más recientes',
                                    'on_sale'      => 'En oferta (con precio rebajado)',
                                    'best_sellers' => 'Más vendidos',
                                    'by_category'  => 'Por categoría',
                                    'custom'       => 'Personalizado (elegir productos)',
                                ])
                                ->required()
                                ->live(),

                            Select::make('category_slug')
                                ->label('Categoría')
                                ->options(fn () => Category::orderBy('name')->pluck('name', 'slug')->toArray())
                                ->searchable()
                                ->nullable()
                                ->hidden(fn (Get $get) => $get('type') !== 'by_category')
                                ->columnSpanFull(),

                            Select::make('product_ids')
                                ->label('Productos')
                                ->multiple()
                                ->searchable()
                                ->getSearchResultsUsing(fn (string $search) =>
                                    Product::where('name', 'like', "%{$search}%")
                                        ->where('is_active', true)
                                        ->limit(20)
                                        ->pluck('name', 'id')
                                        ->toArray()
                                )
                                ->getOptionLabelsUsing(fn (array $values) =>
                                    Product::whereIn('id', $values)->pluck('name', 'id')->toArray()
                                )
                                ->hidden(fn (Get $get) => $get('type') !== 'custom')
                                ->columnSpanFull(),

                        ]),
                ]),

            // ─── BANNER ─────────────────────────────────────────────────
            Section::make('Banner')
                ->description('Imagen con texto superpuesto y botón opcional.')
                ->visible(fn (Get $get) => $get('type') === 'banner')
                ->columns(2)
                ->components([

                    FileUpload::make('banner_image')
                        ->label('Imagen')
                        ->image()
                        ->disk('public')
                        ->directory('home-banners')
                        ->visibility('public')
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('8:3')
                        ->imageResizeTargetWidth(1920)
                        ->imageResizeTargetHeight(720)
                        ->saveUploadedFileUsing(WebpConverter::saveAs('home-banners'))
                        ->helperText('Imagen panorámica horizontal (1920×720px). Se recortará automáticamente.')
                        ->nullable()
                        ->columnSpanFull(),

                    TextInput::make('banner_title')
                        ->label('Título')
                        ->placeholder('Ej: Ofertas de temporada')
                        ->nullable(),

                    TextInput::make('banner_subtitle')
                        ->label('Subtítulo')
                        ->placeholder('Ej: Hasta 50% off en toda la colección')
                        ->nullable(),

                    TextInput::make('banner_button_text')
                        ->label('Texto del botón')
                        ->placeholder('Ej: Ver ofertas')
                        ->nullable(),

                    TextInput::make('banner_button_link')
                        ->label('Link del botón')
                        ->placeholder('Ej: /categories/ofertas')
                        ->nullable(),

                ]),

        ]);
    }
}
