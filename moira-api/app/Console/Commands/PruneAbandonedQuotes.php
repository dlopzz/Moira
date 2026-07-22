<?php

namespace App\Console\Commands;

use App\Models\Quote;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('quotes:prune {--days=7 : Días de gracia pasada la expiración antes de borrar}')]
#[Description('Borra los carritos de invitado abandonados (Quotes sin cliente, vencidos y no convertidos) para que la tabla no crezca sin límite.')]
class PruneAbandonedQuotes extends Command
{
    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        // Solo carritos de invitado: los de clientes están acotados (uno activo
        // por cliente) y los vencidos se reactivan al volver (Quote::reactivateOrCreate),
        // así que no se tocan. Se excluyen 'converted'/'processing' para no borrar
        // órdenes ni pagos en curso. El borrado cascadea a quote_items por FK.
        $deleted = Quote::query()
            ->whereNull('customer_id')
            ->whereNotIn('status', [Quote::STATUS_CONVERTED, Quote::STATUS_PROCESSING])
            ->where('expires_at', '<', $cutoff)
            ->delete();

        $this->info("Carritos de invitado abandonados borrados: {$deleted}");

        return self::SUCCESS;
    }
}
