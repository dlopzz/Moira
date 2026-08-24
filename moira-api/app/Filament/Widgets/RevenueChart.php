<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\ChartStyle;
use App\Support\Dashboard\SalesPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;

/**
 * Recaudación (dinero facturado) a lo largo del tiempo.
 *
 * A diferencia del gráfico de cantidad, acá solo cuenta la plata que entró de
 * verdad: quedan afuera las pendientes (todavía no pagaron), las canceladas y
 * las reembolsadas (la plata se devolvió).
 */
class RevenueChart extends ChartWidget
{
    /** Estados en los que el cobro ya se concretó y no se revirtió. */
    private const COLLECTED = ['paid', 'processing', 'shipped', 'delivered'];

    protected ?string $heading = 'Recaudación';

    protected ?string $maxHeight = '280px';

    /**
     * Sin esto Filament refresca el widget cada 5 segundos (default de CanPoll),
     * y cada refresco es una agregación sobre toda la tabla orders — que no
     * tiene índice por status ni por created_at. Los datos de un dashboard de
     * ventas no cambian a ese ritmo: se actualizan al recargar la página.
     */
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 2;

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
        $period = SalesPeriod::make($this->filter);

        $total = DB::table('orders')
            ->whereNull('deleted_at')
            ->whereIn('status', self::COLLECTED)
            ->where('created_at', '>=', $period->start())
            ->sum('total');

        return 'Facturado por ' . SalesPeriod::unitLabel($this->filter)
            . ', sin pendientes ni canceladas ni reembolsadas. Total del período: '
            . '$' . number_format((float) $total, 0, ',', '.');
    }

    protected function getData(): array
    {
        $period = SalesPeriod::make($this->filter);

        $rows = DB::table('orders')
            ->selectRaw("{$period->sqlBucket('created_at')} as bucket, sum(total) as value")
            ->whereNull('deleted_at')
            ->whereIn('status', self::COLLECTED)
            ->where('created_at', '>=', $period->start())
            ->groupBy('bucket')
            ->pluck('value', 'bucket')
            ->map(fn ($v) => round((float) $v, 2));

        $series = $period->fill($rows);

        return [
            'datasets' => [ChartStyle::bars('Recaudación', $series['values'], ChartStyle::SUCCESS_SOFT, ChartStyle::SUCCESS)],
            'labels' => $series['labels'],
        ];
    }

    /**
     * RawJs y no un array: los montos necesitan un callback de JS para
     * abreviarse en el eje ($1,2 M) y mostrarse completos en el tooltip.
     */
    protected function getOptions(): RawJs
    {
        return ChartStyle::moneyOptions();
    }
}
