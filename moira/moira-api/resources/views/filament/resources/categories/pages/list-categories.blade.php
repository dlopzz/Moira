<x-filament-panels::page>
    <div class="flex -mx-4 md:-mx-6 lg:-mx-8 border-t border-gray-200 dark:border-gray-700" style="min-height: 75vh">

        {{-- Panel árbol izquierdo --}}
        <div class="w-64 xl:w-72 shrink-0 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
            <div class="p-3">
                @livewire('category-tree', [], key('category-tree'))
            </div>
        </div>

        {{-- Panel derecho: empty state --}}
        <div class="flex-1 flex items-center justify-center p-12 text-center bg-gray-50 dark:bg-gray-950">
            <div>
                <x-heroicon-o-folder-open class="mx-auto w-14 h-14 text-gray-300 dark:text-gray-700 mb-4"/>
                <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">
                    Seleccioná una categoría del árbol para editarla
                </p>
                <p class="text-gray-400 dark:text-gray-600 text-xs mb-5">
                    O creá una nueva desde el árbol
                </p>
            </div>
        </div>

    </div>
</x-filament-panels::page>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => Alpine.store('sidebar').close())
    document.addEventListener('livewire:navigated', () => Alpine.store('sidebar').close())
</script>
@endpush
