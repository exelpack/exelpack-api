<?php

use Illuminate\Database\Seeder;
use App\JobOrderSeries;
class JobOrderSeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      JobOrderSeries::create([
      	'series_prefix' => 'JO',
      	'series_number' => 1,
      ]);	
    }
}
