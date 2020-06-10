<?php

use Illuminate\Database\Seeder;

class PurchaseOrderAndItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      factory(App\PurchaseOrder::class, 30)
        ->create()
        ->each(function ($po) {
          $po->poitems()->save(factory(App\PurchaseOrderItems::class)->make());
        });
    }
}
