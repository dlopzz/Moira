<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images')
                    ->label('')
                    ->disk('public')
                    ->getStateUsing(fn ($record) => $record->images[0] ?? null)
                    ->width(48)
                    ->height(48)
                    ->defaultImageUrl(fn () => 'https://placehold.co/48x48/f3f4f6/9ca3af?text=Sin+imagen')
                    ->extraImgAttributes(['class' => 'rounded object-cover']),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('categories.name')
                    ->label('Categorías')
                    ->badge()
                    ->separator(',')
                    ->placeholder('Sin categoría'),

                TextColumn::make('price')
                    ->label('Precio')
                    ->money('ARS')
                    ->sortable(),

                TextColumn::make('sale_price')
                    ->label('Oferta')
                    ->money('ARS')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state) => match(true) {
                        $state === 0 => 'danger',
                        $state <= 5  => 'warning',
                        default      => 'success',
                    }),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('replicate')
                    ->label('Duplicar')
                    ->icon(Heroicon::DocumentDuplicate)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Duplicar producto')
                    ->modalDescription('Se creará una copia inactiva con sus variantes, categorías y relacionados. El SKU quedará vacío.')
                    ->action(function (Product $record): void {
                        static::duplicateProduct($record);

                        Notification::make()
                            ->success()
                            ->title('Producto duplicado')
                            ->body('La copia se creó inactiva. Editala para publicarla.')
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Clone a product into a new inactive copy, replicating its categories,
     * related products and variants. Unique fields (slug, sku) are regenerated
     * to avoid collisions with the original and any soft-deleted records.
     */
    protected static function duplicateProduct(Product $original): void
    {
        DB::transaction(function () use ($original): void {
            $copy = $original->replicate(['slug', 'sku']);
            $copy->name = $original->name.' (copia)';
            $copy->slug = static::uniqueSlug(Str::slug($original->name));
            $copy->sku = null;
            $copy->is_active = false;
            $copy->save();

            $copy->categories()->sync($original->categories->pluck('id'));
            $copy->relatedProducts()->sync($original->relatedProducts->pluck('id'));

            foreach ($original->variants as $variant) {
                $newVariant = $variant->replicate(['sku']);
                $newVariant->product_id = $copy->id;
                $newVariant->sku = null;
                $newVariant->save();
            }
        });
    }

    /**
     * Build a slug that is unique across the products table, including
     * soft-deleted rows, appending "-copia" / "-copia-N" as needed.
     */
    protected static function uniqueSlug(string $base): string
    {
        $slug = $base.'-copia';
        $suffix = 2;

        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-copia-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
