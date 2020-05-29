<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\AccountingPurchasesItems;
use Faker\Generator as Faker;
use Carbon\Carbon;

$factory->define(AccountingPurchasesItems::class, function (Faker $faker) {
    $date = new Carbon("Mar 10, 2020");
    $newDate = $date->addDays(rand(1,50));
    return [
      'item_datereceived' => $newDate,
      'item_datepurchased' => $newDate,
      'item_supplier_id' => rand(1,50),
      'item_accounts_id' => rand(1,50),
      'item_salesinvoice_no' => $faker->creditCardNumber,
      'item_deliveryreceipt_no' => $faker->ean13,
      'item_purchaseorder_no' => $faker->ean8,
      'item_purchaserequest_no' => $faker->isbn10,
      'item_particular' => $faker->words($nb = 3, $asText = true),
      'item_quantity' => rand(1,1999),
      'item_unit' => $faker->randomElement($array = array ('pc','pcs','unit')),
      'item_unitprice' => rand(3,100),
      'item_currency' => $faker->randomElement($array = array ('USD','PHP')),
    ];
});
