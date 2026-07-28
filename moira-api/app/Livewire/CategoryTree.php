<?php

namespace App\Livewire;

use App\Models\Category;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CategoryTree extends Component
{
    /** Profundidad máxima soportada por el árbol (raíz + 4 niveles eager-loaded). */
    private const MAX_DEPTH = 5;

    public ?int $currentCategoryId = null;

    public function render()
    {
        $roots = Category::whereNull('parent_id')
            ->with([
                'children'                           => fn ($q) => $q->withCount('products')->orderBy('sort_order')->orderBy('name'),
                'children.children'                  => fn ($q) => $q->withCount('products')->orderBy('sort_order')->orderBy('name'),
                'children.children.children'         => fn ($q) => $q->withCount('products')->orderBy('sort_order')->orderBy('name'),
                'children.children.children.children' => fn ($q) => $q->withCount('products')->orderBy('sort_order')->orderBy('name'),
            ])
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('livewire.category-tree', [
            'roots'       => $roots,
            'ancestorIds' => $this->ancestorIds(),
        ]);
    }

    public function moveCategory(int $id, int $newParentId): void
    {
        $category = Category::findOrFail($id);

        if ($category->parent_id === null) {
            Notification::make()->danger()->title('No se puede mover la categoría raíz.')->send();

            return;
        }

        if ($id === $newParentId || $category->parent_id === $newParentId) {
            return;
        }

        if ($category->descendantsAndSelf()->pluck('id')->contains($newParentId)) {
            Notification::make()->danger()
                ->title('No podés mover una categoría dentro de sí misma o de una subcategoría suya.')
                ->send();

            return;
        }

        $newParent    = Category::findOrFail($newParentId);
        $parentDepth  = $newParent->ancestors()->count() + 1;
        $subtreeHeight = 1 + (int) $category->descendants()->get()->max('depth');

        if ($parentDepth + $subtreeHeight > self::MAX_DEPTH) {
            Notification::make()->danger()
                ->title('El movimiento supera la profundidad máxima del árbol.')
                ->send();

            return;
        }

        DB::transaction(function () use ($category, $newParentId): void {
            $maxOrder = (int) Category::where('parent_id', $newParentId)->max('sort_order');
            $category->update([
                'parent_id'  => $newParentId,
                'sort_order' => $maxOrder + 1,
            ]);
        });

        Notification::make()->success()->title('Categoría movida.')->send();
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
