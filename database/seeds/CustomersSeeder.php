<?php

use Illuminate\Database\Seeder;

class CustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      $faker = Faker\Factory::create();

      factory(App\Customers::class, 30)->create();
      for ($i=1; $i <= 30; $i++) { 
        App\Masterlist::create(
          [
            'm_moq' => rand(500, 5000),
            'm_mspecs' => str_replace(',', 'x', $faker->rgbcolor),
            'm_projectname' => $faker->streetName,
            'm_partnumber' => $faker->postcode,
            'm_code' => 'EP-' . str_pad($i, 6, '0', STR_PAD_LEFT),
            'm_regisdate' => date('Y-m-d'),
            'm_effectdate' => date('Y-m-d'),
            'm_requiredquantity' => rand(1, 50),
            'm_outs' => rand(1, 50),
            'm_unit' => $faker->randomElement($array = array ('pc', 'set')),
            'm_unitprice' => rand(100, 500),
            'm_budgetprice' =>  rand(100, 500),
            'm_customer_id' => rand(1, 30),
          ]
        );
      }
    }
}
