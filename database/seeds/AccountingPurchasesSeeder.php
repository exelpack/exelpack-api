<?php

use Illuminate\Database\Seeder;

class AccountingPurchasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      factory(App\AccountingPurchasesAccounts::class,50)->create();
      factory(App\AccountingPurchasesSupplier::class,50)->create();
      factory(App\AccountingPurchasesItems::class,10000)->create();
    }
}
