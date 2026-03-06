<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use Illuminate\Console\Command;

class VerifyIptvPurchases extends Command
{
    protected $signature   = 'iptv:verify-purchases';
    protected $description = 'Auto-verifica compras IPTV completadas y entregadas que siguen en estado "pending".';

    public function handle(): int
    {
        $purchases = Purchase::where('purchase_status', 'completed')
            ->where('verification_status', 'pending')
            ->whereHas('serviceAccess')
            ->get();

        if ($purchases->isEmpty()) {
            $this->info('No hay compras IPTV pendientes de verificar.');
            return self::SUCCESS;
        }

        $this->info("Encontradas {$purchases->count()} compras. Verificando…");

        $updated = 0;
        foreach ($purchases as $purchase) {
            $purchase->update(['verification_status' => 'verified']);
            $this->line("  ✓ UUID {$purchase->purchase_uuid} (@{$purchase->telegram_username})");
            $updated++;
        }

        $this->info("Listo. {$updated} compras verificadas.");

        return self::SUCCESS;
    }
}
