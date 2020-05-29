<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\AccountingPurchasesSupplier;
use Faker\Generator as Faker;

$factory->define(AccountingPurchasesSupplier::class, function (Faker $faker) {
    return [
      'supplier_name' => $faker->company,
      'supplier_payment_terms' => $faker->randomElement($array = array (30, 60, 90)),
      'supplier_address' => $faker->address,
      'supplier_tin_number' => $faker->postcode,
    ];
});
