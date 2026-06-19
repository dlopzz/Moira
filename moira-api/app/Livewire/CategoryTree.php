<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Component;

class CategoryTree extends Component
{
    public ?int $currentCategoryId = null;

    public function render()
    {
        return view('livewire.category-tree', [
            'roots'       => Category::whereNull('parent_id')
                ->with([
                    'children'                           => fn ($q) => $q->withCount('products')->orderBy('sort_order')->orderBy('name'),
                    'children.children'                  => fn ($q) => $q->withCount('products')->orderBy('sort_order')->orderBy('name'),
                    'children.children.children'         => fn ($q) => $q->withCount('products')->orderBy('sort_order')->orderBy('name'),
                    'children.children.children.children'=> fn ($q) => $q->withCount('products')->orderBy('sort_order')->orderBy('name'),
                ])
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'ancestorIds' => $this->ancestorIds(),
        ]);
    }

    private function ancestorIds(): array
    {
        if (! $this->currentCategoryId) {
            return [];
        }

        $ids = [];
        $cat = Category::find($this->currentCategoryId);

        while ($cat?->parent_id) {
            $ids[] = $cat->parent_id;
            $cat   = Category::find($cat->parent_id);
        }

        return $ids;
    }
}
