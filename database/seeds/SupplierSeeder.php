<?php

use Illuminate\Database\Seeder;
use App\Supplier;
class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      Supplier::insert([
        array(
          'sd_supplier_name' => 'Supplier 1',
        ),
        array(
          'sd_supplier_name' => 'Supplier 2',
        ),
      ]);
    }
}
