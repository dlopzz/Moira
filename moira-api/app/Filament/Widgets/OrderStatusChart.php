<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\Dashboard\ChartStyle;
use App\Support\Dashboard\SalesPeriod;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Facades\DB;

/**
 * Distribución de órdenes por estado.
 *
 * Muestra cantidades, no porcentajes (el porcentaje queda en el tooltip), y
 * deja elegir qué estados entran en la torta: mirar solo "enviado" contra
 * "entregado" es la pregunta habitual, y con los siete estados juntos las
 * porciones chicas no se distinguen.
 */
class OrderStatusChart extends ChartWidget
{
    // El alias deja llegar al trigger original del trait: parent:: no sirve,
    // porque el método viene de un trait y no de la clase padre.
    use HasFiltersSchema {
        getFiltersTriggerAction as baseFiltersTriggerAction;
    }

    protected ?string $heading = 'Estado de pedidos';

    protected ?string $maxHeight = '320px';

    /**
     * Sin esto Filament refresca el widget cada 5 segundos (default de CanPoll),
     * y cada refresco es una agregación sobre toda la tabla orders — que no
     * tiene índice por status ni por created_at. Los datos de un dashboard de
     * ventas no cambian a ese ritmo: se actualizan al recargar la página.
     */
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    public ?string $filter = SalesPeriod::LAST_12_MONTHS;

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getFilters(): ?array
    {
        return SalesPeriod::options();
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            CheckboxList::make('statuses')
                ->label('Estados a mostrar')
                ->options(Order::STATUS_LABELS)
                ->default(array_keys(Order::STATUS_LABELS))
                ->bulkToggleable(),
        ]);
    }

    /**
     * El trigger que trae Filament es un botón de ícono (un embudo pelado) que
     * no dice qué filtra ni si hay algo filtrado. Acá va con texto y con la
     * cuenta de estados activos, para que se lea como un selector más.
     */
    public function getFiltersTriggerAction(): Action
    {
        $selected = count($this->selectedStatuses());
        $total = count(Order::STATUS_LABELS);

        return $this->baseFiltersTriggerAction()
            ->label($selected === $total ? 'Todos los estados' : "Estados: {$selected} de {$total}")
            ->button()
            ->outlined()
            ->color($selected === $total ? 'gray' : 'primary');
    }

    public function getDescription(): ?string
    {
        $total = array_sum($this->counts());

        return $total === 0
            ? 'No hay órdenes en el período y los estados elegidos.'
            : "{$total} órdenes en el período y los estados elegidos.";
    }

    protected function getData(): array
    {
        $counts = $this->counts();

        return [
            'datasets' => [[
                'label' => 'Órdenes',
                'data' => array_values($counts),
                'backgroundColor' => array_map(
                    fn (string $status) => ChartStyle::STATUS[$status] ?? ChartStyle::AXIS,
                    array_keys($counts),
                ),
                'borderWidth' => 0,
            ]],
            'labels' => array_map(
                fn (string $status) => Order::STATUS_LABELS[$status],
                array_keys($counts),
            ),
        ];
    }

    /**
     * Cantidad de órdenes por estado, ya filtrada.
     *
     * Arranca del mapa completo de estados para respetar siempre el mismo orden
     * de porciones y colores, y descarta los que quedaron en cero: una porción
     * vacía solo ensucia la leyenda.
     *
     * @return array<string, int>
     */
    private function counts(): array
    {
        $selected = $this->selectedStatuses();

        if (empty($selected)) {
            return [];
        }

        $rows = DB::table('orders')
            ->selectRaw('status, count(*) as value')
            ->whereNull('deleted_at')
            ->whereIn('status', $selected)
            ->where('created_at', '>=', SalesPeriod::make($this->filter)->start())
            ->groupBy('status')
            ->pluck('value', 'status');

        $counts = [];

        foreach (array_keys(Order::STATUS_LABELS) as $status) {
            if (in_array($status, $selected, true) && ($rows[$status] ?? 0) > 0) {
                $counts[$status] = (int) $rows[$status];
            }
        }

        return $counts;
    }

    /** @return array<int, string> */
    private function selectedStatuses(): array
    {
        return $this->filters['statuses'] ?? array_keys(Order::STATUS_LABELS);
    }

    /** El tooltip por defecto muestra el valor pelado; acá suma el porcentaje. */
    protected function getOptions(): RawJs
    {
        $axis = ChartStyle::AXIS;

        return RawJs::make(<<<JS
            {
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: '{$axis}', font: { size: 12, weight: '500' }, boxWidth: 12, padding: 14 },
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total ? Math.round((ctx.parsed / total) * 100) : 0;
                                return ` \${ctx.label}: \${ctx.parsed} (\${pct}%)`;
                            },
                        },
                    },
                },
            }
            JS);
    }
}
