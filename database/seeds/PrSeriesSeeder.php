<?php

use Illuminate\Database\Seeder;
use App\PurchaseRequestSeries;
class PrSeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      PurchaseRequestSeries::create([
      	'series_prefix' => 'PR',
      	'series_number' => 1,
      ]);	
    }
}
