<x-filament-panels::page>
    <div class="flex -mx-4 md:-mx-6 lg:-mx-8 -my-8 border-t border-gray-200 dark:border-gray-700" style="min-height: calc(100vh - 8rem)">

        {{-- Panel árbol izquierdo --}}
        <div class="w-64 xl:w-72 shrink-0 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
            <div class="p-3">
                @livewire('category-tree', ['currentCategoryId' => $this->record->id], key('ct-' . $this->record->id))
            </div>
        </div>

        {{-- Panel formulario + productos --}}
        <div class="flex-1 min-w-0 overflow-y-auto bg-gray-50 dark:bg-gray-950">
            <div class="px-6 py-6 space-y-6">

                {{-- Formulario --}}
                <form wire:submit="save">
                    {{ $this->form }}
                    <div class="flex justify-end pt-4">
                        <x-filament::button type="submit" color="primary" size="sm">
                            Guardar cambios
                        </x-filament::button>
                    </div>
                </form>

                {{-- Productos (relation manager) --}}
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700"></div>
                @foreach ($this->getRelationManagers() as $manager)
                    @livewire(
                        $this->normalizeRelationManagerClass($manager),
                        ['ownerRecord' => $this->record, 'pageClass' => static::class],
                        key($manager . '-' . $this->record->id)
                    )
                @endforeach

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
