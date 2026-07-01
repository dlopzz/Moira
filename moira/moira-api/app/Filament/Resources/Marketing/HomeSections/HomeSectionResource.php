<?php

namespace App\Filament\Resources\Marketing\HomeSections;

use App\Filament\Resources\Marketing\HomeSections\Pages\CreateHomeSection;
use App\Filament\Resources\Marketing\HomeSections\Pages\EditHomeSection;
use App\Filament\Resources\Marketing\HomeSections\Pages\ListHomeSections;
use App\Filament\Resources\Marketing\HomeSections\Schemas\HomeSectionForm;
use App\Filament\Resources\Marketing\HomeSections\Tables\HomeSectionsTable;
use App\Models\HomeSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HomeSectionResource extends Resource
{
    protected static ?string $model = HomeSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Home';

    protected static ?string $modelLabel = 'Sección';

    protected static ?string $pluralModelLabel = 'Secciones del Home';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return HomeSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeSectionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListHomeSections::route('/'),
            'create' => CreateHomeSection::route('/create'),
            'edit'   => EditHomeSection::route('/{record}/edit'),
        ];
    }

    /**
     * Flattens the settings JSON into top-level form fields before filling the form.
     * Called in EditHomeSection::mutateFormDataBeforeFill().
     */
    public static function unpackSettings(array $data): array
    {
        $s = $data['settings'] ?? [];

        $data['slides']             = $s['slides'] ?? [];
        $data['per_tab']            = $s['per_tab'] ?? 8;
        $data['tabs']               = $s['tabs'] ?? [];
        $data['banner_image']       = $s['image'] ?? null;
        $data['banner_title']       = $s['title'] ?? null;
        $data['banner_subtitle']    = $s['subtitle'] ?? null;
        $data['banner_button_text'] = $s['button_text'] ?? null;
        $data['banner_button_link'] = $s['button_link'] ?? null;

        unset($data['settings']);

        return $data;
    }

    /**
     * Packs the top-level form fields back into the settings JSON before saving.
     * Called in Create/EditHomeSection::mutateFormDataBeforeSave().
     */
    public static function packSettings(array $data): array
    {
        $type = $data['type'] ?? '';

        $data['settings'] = match ($type) {
            'hero_slider' => [
                'slides' => $data['slides'] ?? [],
            ],
            'product_tabs' => [
                'per_tab' => (int) ($data['per_tab'] ?? 8),
                'tabs'    => $data['tabs'] ?? [],
            ],
            'banner' => [
                'image'       => $data['banner_image'] ?? null,
                'title'       => $data['banner_title'] ?? null,
                'subtitle'    => $data['banner_subtitle'] ?? null,
                'button_text' => $data['banner_button_text'] ?? null,
                'button_link' => $data['banner_button_link'] ?? null,
            ],
            default => [],
        };

        unset(
            $data['slides'],
            $data['per_tab'],
            $data['tabs'],
            $data['banner_image'],
            $data['banner_title'],
            $data['banner_subtitle'],
            $data['banner_button_text'],
            $data['banner_button_link'],
        );

        return $data;
    }
}
