<?php

namespace App\Filament\Resources\Orders\Returns\Schemas;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('order_id')
                ->label('Orden')
                ->searchable()
                ->required()
                ->live()
                ->default(fn () => request()->query('order_id'))
                ->getSearchResultsUsing(fn (string $search) => self::eligibleOrdersQuery()
                    ->where(fn ($q) => $q->where('id', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")))
                    ->limit(20)
                    ->get()
                    ->mapWithKeys(fn (Order $o) => [$o->id => "#{$o->id} — ".($o->customer->name ?? 'Invitado')]))
                ->getOptionLabelUsing(fn ($value) => ($o = Order::find($value))
                    ? "#{$o->id} — ".($o->customer->name ?? 'Invitado')
                    : null)
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    $order = $state ? Order::with('items')->find($state) : null;

                    $set('items', $order
                        ? $order->items->map(fn (OrderItem $item) => ['order_item_id' => $item->id, 'quantity' => 0])->toArray()
                        : []);
                })
                ->columnSpanFull(),

            Repeater::make('items')
                ->label('Productos a devolver')
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->visible(fn (Get $get) => filled($get('order_id')))
                ->columns(3)
                ->schema([
                    Hidden::make('order_item_id'),

                    Placeholder::make('product')
                        ->label('Producto')
                        ->content(function (Get $get) {
                            $item = self::resolveOrderItem($get('order_item_id'));

                            return $item ? $item->product_name.($item->variant_label ? " ({$item->variant_label})" : '') : '—';
                        }),

                    Placeholder::make('available')
                        ->label('Disponible')
                        ->content(fn (Get $get) => optional(self::resolveOrderItem($get('order_item_id')))->remainingReturnableQuantity() ?? 0),

                    TextInput::make('quantity')
                        ->label('Cantidad a devolver')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(fn (Get $get) => optional(self::resolveOrderItem($get('order_item_id')))->remainingReturnableQuantity() ?? 0),
                ])
                ->columnSpanFull(),

            Select::make('reason')
                ->label('Motivo')
                ->options(array_combine(OrderReturn::REASONS, OrderReturn::REASONS))
                ->required(),

            TextInput::make('telephone')
                ->label('Teléfono')
                ->tel()
                ->required(),

            Textarea::make('description')
                ->label('Descripción')
                ->nullable()
                ->columnSpanFull(),

            Toggle::make('restock')
                ->label('Reingresar stock al recibir')
                ->default(true)
                ->helperText('Desactivá esto si el producto devuelto no es revendible.')
                ->columnSpanFull(),

        ]);
    }

    /**
     * Mismas dos reglas que OrderReturn::isWithinReturnWindow()/isStatusReturnable(),
     * expresadas como predicados SQL en vez de un chequeo por instancia: este select
     * es buscable y pagina con ->limit(20), así que filtrar a nivel de fila en PHP
     * después de traer resultados rompería esa paginación. Si esas reglas cambian,
     * actualizar también acá.
     */
    private static function eligibleOrdersQuery(): Builder
    {
        return Order::query()
            ->whereNotIn('status', Order::RESTOCKING_STATUSES)
            ->where('created_at', '>=', now()->subDays(OrderReturn::RETURN_WINDOW_DAYS));
    }

    /** Evita repetir un OrderItem::find() por cada closure de la misma fila del repeater. */
    private static function resolveOrderItem(mixed $orderItemId): ?OrderItem
    {
        static $cache = [];

        if (! $orderItemId) {
            return null;
        }

        return $cache[$orderItemId] ??= OrderItem::find($orderItemId);
    }
}
