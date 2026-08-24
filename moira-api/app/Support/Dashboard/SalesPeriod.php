<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rango de tiempo elegido en los filtros de los gráficos del dashboard.
 *
 * Centraliza tres cosas que si no se repiten en cada widget: el bucket SQL
 * (día o mes), el relleno de los períodos sin ventas — sin esto el eje X se
 * saltea los días vacíos y el gráfico miente — y las etiquetas en castellano.
 */
class SalesPeriod
{
    public const LAST_7_DAYS = '7d';

    public const LAST_30_DAYS = '30d';

    public const LAST_90_DAYS = '90d';

    public const LAST_12_MONTHS = '12m';

    public const THIS_YEAR = 'year';

    /** Chart.js parte las etiquetas array en varias líneas: día arriba, fecha abajo. */
    private const WEEKDAYS = [
        0 => 'dom', 1 => 'lun', 2 => 'mar', 3 => 'mié', 4 => 'jue', 5 => 'vie', 6 => 'sáb',
    ];

    private const MONTHS = [
        1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
        7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
    ];

    private function __construct(
        private readonly string $key,
        private readonly Carbon $start,
        private readonly bool $byMonth,
    ) {}

    public static function make(?string $key): self
    {
        return match ($key) {
            self::LAST_7_DAYS => new self($key, Carbon::today()->subDays(6), false),
            self::LAST_90_DAYS => new self($key, Carbon::today()->subDays(89), false),
            self::LAST_12_MONTHS => new self($key, Carbon::today()->startOfMonth()->subMonths(11), true),
            self::THIS_YEAR => new self($key, Carbon::today()->startOfYear(), true),
            default => new self(self::LAST_30_DAYS, Carbon::today()->subDays(29), false),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::LAST_7_DAYS => 'Últimos 7 días',
            self::LAST_30_DAYS => 'Últimos 30 días',
            self::LAST_90_DAYS => 'Últimos 90 días',
            self::LAST_12_MONTHS => 'Últimos 12 meses',
            self::THIS_YEAR => 'Este año',
        ];
    }

    public static function unitLabel(?string $key): string
    {
        return self::make($key)->byMonth ? 'mes' : 'día';
    }

    public function start(): Carbon
    {
        return $this->start->copy()->startOfDay();
    }

    public function isByMonth(): bool
    {
        return $this->byMonth;
    }

    /** Expresión de agrupación. Postgres: to_char devuelve la clave ya ordenable. */
    public function sqlBucket(string $column): string
    {
        $format = $this->byMonth ? 'YYYY-MM' : 'YYYY-MM-DD';

        return "to_char({$column}, '{$format}')";
    }

    /**
     * Completa con cero los períodos sin datos y arma las etiquetas del eje.
     *
     * Devuelve labels y values por separado — no un mapa — porque en modo día
     * la etiqueta es un array de dos líneas y eso no puede ser clave de array.
     *
     * @param  Collection<string, int|float>  $rows  indexada por la clave del bucket
     * @return array{labels: array<int, mixed>, values: array<int, int|float>}
     */
    public function fill(Collection $rows): array
    {
        $labels = [];
        $values = [];
        $cursor = $this->byMonth ? $this->start()->startOfMonth() : $this->start();
        $end = Carbon::today();

        while ($cursor->lte($end)) {
            $key = $cursor->format($this->byMonth ? 'Y-m' : 'Y-m-d');

            $labels[] = $this->label($cursor);
            $values[] = $rows[$key] ?? 0;

            $this->byMonth ? $cursor->addMonth() : $cursor->addDay();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** @return string|array<int, string> */
    private function label(Carbon $at): string | array
    {
        if ($this->byMonth) {
            return self::MONTHS[$at->month] . ' ' . $at->format('y');
        }

        return [self::WEEKDAYS[$at->dayOfWeek], $at->format('d/m')];
    }
}
