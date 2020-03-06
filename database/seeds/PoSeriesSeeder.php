<?php

use Illuminate\Database\Seeder;
use App\PurchaseOrderSeries;
class PoSeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      PurchaseOrderSeries::create([
        'series_prefix' => 'PO',
        'series_number' => 1,
      ]); 
    }
}
