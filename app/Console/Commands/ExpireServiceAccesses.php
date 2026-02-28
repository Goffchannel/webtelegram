<?php

namespace App\Console\Commands;

use App\Models\PurchaseServiceAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireServiceAccesses extends Command
{
    protected $signature   = 'iptv:expire-accesses';
    protected $description = 'Mark all expired service accesses (IPTV subscriptions) as "expired".';

    public function handle(): int
    {
        $count = PurchaseServiceAccess::query()
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        if ($count > 0) {
            Log::info("ExpireServiceAccesses: {$count} access(es) marked as expired.");
            $this->info("{$count} access(es) marked as expired.");
        } else {
            $this->info('No expired accesses found.');
        }

        return Command::SUCCESS;
    }
}
