<?php

namespace App\Support\Dashboard;

use Filament\Support\RawJs;

/**
 * Estilo compartido de los gráficos del dashboard.
 *
 * Chart.js se dibuja en canvas, así que no hereda nada del tema de Filament:
 * los colores hay que pasárselos a mano. Los de acá están elegidos para que se
 * lean tanto en el panel claro como en el oscuro, sin tener que detectar cuál
 * está activo.
 */
class ChartStyle
{
    /** Indigo del panel (primary), en sólido para las barras. */
    public const PRIMARY = '#6366f1';

    public const PRIMARY_SOFT = 'rgba(99, 102, 241, 0.75)';

    public const SUCCESS = '#10b981';

    public const SUCCESS_SOFT = 'rgba(16, 185, 129, 0.75)';

    public const DANGER = '#f43f5e';

    /**
     * Un color por estado de orden, alineado con Order::STATUS_COLORS: ámbar lo
     * que espera, azul lo que está en curso, verde lo entregado, rojo lo que se
     * cayó. Así la torta se lee igual que los badges del listado de órdenes.
     */
    public const STATUS = [
        'pending' => '#f59e0b',
        'paid' => '#0ea5e9',
        'processing' => '#6366f1',
        'shipped' => '#8b5cf6',
        'delivered' => '#10b981',
        'cancelled' => '#f43f5e',
        'refunded' => '#94a3b8',
    ];

    /** Gris medio: contrasta contra fondo blanco y contra el slate oscuro. */
    public const AXIS = '#94a3b8';

    public const GRID = 'rgba(148, 163, 184, 0.18)';

    /** @return array<string, mixed> */
    public static function bars(
        string $label,
        array $data,
        string $color = self::PRIMARY_SOFT,
        string $border = self::PRIMARY,
    ): array {
        return [
            'label' => $label,
            'data' => $data,
            'backgroundColor' => $color,
            'borderColor' => $border,
            'borderWidth' => 1,
            'borderRadius' => 4,
            'maxBarThickness' => 46,
        ];
    }

    /**
     * Ejes legibles: el default de Chart.js pinta los ticks en gris muy claro y
     * sobre el panel oscuro quedan casi invisibles.
     *
     * @return array<string, mixed>
     */
    public static function axes(bool $money = false): array
    {
        $ticks = [
            'color' => self::AXIS,
            'font' => ['size' => 12, 'weight' => '500'],
        ];

        return [
            'x' => [
                'ticks' => $ticks + ['maxRotation' => 0, 'autoSkipPadding' => 12],
                'grid' => ['display' => false],
            ],
            'y' => [
                'beginAtZero' => true,
                'ticks' => $ticks + ($money ? [] : ['precision' => 0]),
                'grid' => ['color' => self::GRID],
                'border' => ['display' => false],
            ],
        ];
    }

    /**
     * Opciones para gráficos de plata. Va como RawJs porque Chart.js necesita
     * funciones de verdad para formatear: el eje muestra el monto abreviado
     * ($1,2 M) y el tooltip el importe completo en pesos.
     */
    public static function moneyOptions(): RawJs
    {
        $axis = self::AXIS;
        $grid = self::GRID;

        return RawJs::make(<<<JS
            {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => new Intl.NumberFormat('es-AR', {
                                style: 'currency', currency: 'ARS', maximumFractionDigits: 0,
                            }).format(ctx.parsed.y),
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: '{$axis}', font: { size: 12, weight: '500' }, maxRotation: 0, autoSkipPadding: 12 },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: '{$grid}' },
                        ticks: {
                            color: '{$axis}',
                            font: { size: 12, weight: '500' },
                            callback: (value) => {
                                if (value >= 1000000) return '$' + (value / 1000000).toFixed(1).replace('.', ',') + ' M';
                                if (value >= 1000) return '$' + Math.round(value / 1000) + ' k';
                                return '$' + value;
                            },
                        },
                    },
                },
            }
            JS);
    }
}
