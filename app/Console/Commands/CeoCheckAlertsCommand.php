<?php

namespace App\Console\Commands;

use App\Services\Ceo\CeoAlertService;
use Illuminate\Console\Command;

class CeoCheckAlertsCommand extends Command
{
    protected $signature = 'ceo:check-alerts';

    protected $description = 'Cek alert CEO (bleeder, budget, HPP) untuk semua toko';

    public function handle(CeoAlertService $alerts): int
    {
        $n = $alerts->checkAllShops();
        $this->info("Alert check selesai untuk {$n} toko.");

        return self::SUCCESS;
    }
}
