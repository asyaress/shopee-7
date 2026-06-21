<?php

namespace Tests\Feature;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyDateDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_default_to_current_month_to_date(): void
    {
        Carbon::setTestNow('2026-06-21 12:00:00');

        try {
            Order::query()->create([
                'order_number' => 'CURRENT-MONTH',
                'customer_name' => 'Pelanggan Juni',
                'order_date' => '2026-06-10',
                'completion_date' => '2026-06-11',
                'jenis_transaksi' => 'Website',
            ]);
            Order::query()->create([
                'order_number' => 'PREVIOUS-MONTH',
                'customer_name' => 'Pelanggan Mei',
                'order_date' => '2026-05-31',
                'completion_date' => '2026-06-01',
                'jenis_transaksi' => 'Website',
            ]);

            $this->withSession(['simple_auth' => true])
                ->get('/orders')
                ->assertOk()
                ->assertSee('CURRENT-MONTH')
                ->assertDontSee('PREVIOUS-MONTH')
                ->assertSee('name="date_from" class="hub-form-control" value="2026-06-01"', false)
                ->assertSee('name="date_to" class="hub-form-control" value="2026-06-21"', false);

        } finally {
            Carbon::setTestNow();
        }
    }
}
