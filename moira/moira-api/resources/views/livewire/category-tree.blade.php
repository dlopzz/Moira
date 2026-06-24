<div class="select-none">

    {{-- Botones de acción --}}
    <div class="flex flex-col gap-1.5 pb-3 mb-3 border-b border-gray-200 dark:border-gray-700">
        @if($currentCategoryId)
            <a wire:navigate
               href="{{ \App\Filament\Resources\Categories\CategoryResource::getUrl('create') }}?parent_id={{ $currentCategoryId }}"
               class="inline-flex items-center gap-1.5 text-xs font-medium text-green-700 hover:text-green-900 dark:text-green-500 dark:hover:text-green-300 transition-colors"
            >
                <x-heroicon-o-plus class="w-3.5 h-3.5 shrink-0"/>
                Agregar subcategoría
            </a>
        @endif
        <a wire:navigate
           href="{{ \App\Filament\Resources\Categories\CategoryResource::getUrl('create') }}"
           class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
        >
            <x-heroicon-o-plus class="w-3.5 h-3.5 shrink-0"/>
            Agregar categoría
        </a>
    </div>

    {{-- Expandir / Contraer todo --}}
    <div class="flex items-center gap-2 mb-3 text-xs text-gray-400 dark:text-gray-500">
        <button @click="$dispatch('expand-all')" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            Expandir todo
        </button>
        <span>|</span>
        <button @click="$dispatch('collapse-all')" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            Contraer todo
        </button>
    </div>

    {{-- Árbol --}}
    @if($roots->isEmpty())
        <p class="text-sm text-gray-400 italic">Sin categorías todavía.</p>
    @else
        @foreach($roots as $root)
            <x-category-tree-node :category="$root" :current-category-id="$currentCategoryId" :ancestor-ids="$ancestorIds"/>
        @endforeach
    @endif

</div>
