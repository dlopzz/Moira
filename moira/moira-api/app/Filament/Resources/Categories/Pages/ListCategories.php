<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected string $view = 'filament.resources.categories.pages.list-categories';

    public function mount(): void
    {
        parent::mount();

        $root = Category::whereNull('parent_id')->first();
        if ($root) {
            $this->redirect(
                CategoryResource::getUrl('edit', ['record' => $root]),
                navigate: true
            );
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
