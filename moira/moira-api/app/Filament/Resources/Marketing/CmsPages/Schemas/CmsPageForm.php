<?php

namespace App\Filament\Resources\Marketing\CmsPages\Schemas;

use App\Models\CmsPage;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, callable $set) =>
                        $operation === 'create' ? $set('slug', Str::slug($state)) : null
                    )
                    ->columnSpanFull(),

                TextInput::make('subtitle')
                    ->label('Subtítulo')
                    ->nullable()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(CmsPage::class, 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('URL de la página: /p/{slug}'),

                Toggle::make('is_active')
                    ->label('Publicada')
                    ->default(true),

                RichEditor::make('content')
                    ->label('Contenido')
                    ->nullable()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'h2',
                        'h3',
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'bulletList',
                        'orderedList',
                        'blockquote',
                        'codeBlock',
                        'undo',
                        'redo',
                    ]),
            ]);
    }
}
