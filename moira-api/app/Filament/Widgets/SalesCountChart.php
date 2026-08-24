<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\ChartStyle;
use App\Support\Dashboard\SalesPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Cantidad de ventas (órdenes) a lo largo del tiempo.
 *
 * Cuenta órdenes efectivas: excluye las canceladas, que no llegaron a venderse.
 * Las reembolsadas sí cuentan — fueron una venta que después se devolvió, y el
 * volumen de devoluciones se mira en su propio widget.
 */
class SalesCountChart extends ChartWidget
{
    protected ?string $heading = 'Cantidad de ventas';

    protected ?string $maxHeight = '280px';

    /**
     * Sin esto Filament refresca el widget cada 5 segundos (default de CanPoll),
     * y cada refresco es una agregación sobre toda la tabla orders — que no
     * tiene índice por status ni por created_at. Los datos de un dashboard de
     * ventas no cambian a ese ritmo: se actualizan al recargar la página.
     */
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = SalesPeriod::LAST_30_DAYS;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return SalesPeriod::options();
    }

    public function getDescription(): ?string
    {
        return 'Órdenes registradas por ' . SalesPeriod::unitLabel($this->filter) . ', sin contar las canceladas.';
    }

    protected function getData(): array
    {
        $period = SalesPeriod::make($this->filter);

        $rows = DB::table('orders')
            ->selectRaw("{$period->sqlBucket('created_at')} as bucket, count(*) as value")
            ->whereNull('deleted_at')
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $period->start())
            ->groupBy('bucket')
            ->pluck('value', 'bucket');

        $series = $period->fill($rows);

        return [
            'datasets' => [ChartStyle::bars('Ventas', $series['values'])],
            'labels' => $series['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ChartStyle::axes(),
        ];
    }
}
