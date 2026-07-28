@props(['category', 'currentCategoryId' => null, 'ancestorIds' => []])

@php $hasChildren = $category->children->isNotEmpty(); @endphp

<div
    x-data="{
        open: false,
        dragOver: false,
        init() {
            const key = 'cat-open-{{ $category->id }}';
            const stored = sessionStorage.getItem(key);
            if (stored !== null) {
                this.open = JSON.parse(stored);
            } else {
                // Si no hay estado guardado, abrir si es ancestro del nodo activo
                this.open = {{ ($hasChildren && in_array($category->id, $ancestorIds)) ? 'true' : 'false' }};
            }
            this.$watch('open', v => sessionStorage.setItem(key, String(v)));
        }
    }"
    @expand-all.window="open = true; sessionStorage.setItem('cat-open-{{ $category->id }}', 'true')"
    @collapse-all.window="open = false; sessionStorage.setItem('cat-open-{{ $category->id }}', 'false')"
>

    <div
        class="flex items-center group rounded transition-colors"
        :class="dragOver ? 'ring-2 ring-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/30' : ''"
        x-on:dragover.prevent.stop="if (window.__catDrag && window.__catDrag !== {{ $category->id }}) dragOver = true"
        x-on:dragleave.stop="dragOver = false"
        x-on:drop.prevent.stop="dragOver = false; const s = window.__catDrag; window.__catDrag = null; if (s && s !== {{ $category->id }}) { let el = $el; while (el && !el.getAttribute('wire:id')) el = el.parentElement; if (el) window.Livewire.find(el.getAttribute('wire:id')).call('moveCategory', s, {{ $category->id }}); }"
    >

        {{-- Handle para arrastrar (no aplica a la raíz) --}}
        @if($category->parent_id !== null)
            <span
                draggable="true"
                title="Arrastrar para mover a otra categoría"
                class="shrink-0 w-4 mr-0.5 flex items-center justify-center cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-300 opacity-60 group-hover:opacity-100 transition-opacity"
                x-on:dragstart="window.__catDrag = {{ $category->id }}; $event.dataTransfer.effectAllowed = 'move'; $event.dataTransfer.setData('text/plain', '{{ $category->id }}')"
                x-on:dragend="window.__catDrag = null"
            >
                <x-heroicon-o-bars-3 class="w-3.5 h-3.5"/>
            </span>
        @else
            <span class="w-4 mr-0.5 shrink-0"></span>
        @endif

        {{-- Botón +/- --}}
        @if($hasChildren)
            <button
                @click="open = !open"
                class="shrink-0 w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 font-mono text-xs leading-none border border-gray-300 dark:border-gray-600 rounded-sm bg-white dark:bg-gray-800 mr-1"
            >
                <span x-show="open">−</span>
                <span x-show="!open" x-cloak>+</span>
            </button>
        @else
            <span class="w-5 mr-1 shrink-0"></span>
        @endif

        {{-- Link navegable --}}
        <a
            wire:navigate
            draggable="false"
            href="{{ \App\Filament\Resources\Categories\CategoryResource::getUrl('edit', ['record' => $category]) }}"
            class="flex-1 flex items-center gap-1.5 rounded-md px-2 py-1 text-sm transition-colors min-w-0
                {{ $category->id === $currentCategoryId
                    ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-semibold'
                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
        >
            @if($hasChildren)
                <span x-show="open" class="shrink-0 text-slate-400">
                    <x-heroicon-o-folder-open class="w-4 h-4"/>
                </span>
                <span x-show="!open" x-cloak class="shrink-0 text-slate-400">
                    <x-heroicon-o-folder class="w-4 h-4"/>
                </span>
            @else
                <span class="shrink-0 text-gray-400">
                    <x-heroicon-o-tag class="w-4 h-4"/>
                </span>
            @endif

            <span class="truncate leading-5">{{ $category->name }}</span>

            @if($hasChildren)
                {{-- Padre: cuenta subcategorías --}}
                <span class="ml-auto text-xs text-gray-400 dark:text-gray-500 shrink-0 pl-1">
                    {{ $category->children->count() }}
                </span>
            @elseif($category->products_count > 0)
                {{-- Hoja: cuenta productos --}}
                <span class="ml-auto text-xs text-slate-400 dark:text-slate-500 shrink-0 pl-1">
                    {{ $category->products_count }}
                </span>
            @endif
        </a>

    </div>

    @if($hasChildren)
        <div x-show="open" x-cloak class="ml-6 pl-2 border-l border-gray-200 dark:border-gray-700 mt-0.5 mb-0.5">
            @foreach($category->children->sortBy('sort_order') as $child)
                <x-category-tree-node
                    :category="$child"
                    :current-category-id="$currentCategoryId"
                    :ancestor-ids="$ancestorIds"
                />
            @endforeach
        </div>
    @endif

</div>
