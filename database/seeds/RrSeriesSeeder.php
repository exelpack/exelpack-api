<?php

use Illuminate\Database\Seeder;
use App\ReceivingReportSeries;
class RrSeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      ReceivingReportSeries::create([
        'series_prefix' => 'RR',
        'series_number' => 1,
      ]); 
    }
}
