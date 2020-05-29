<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\AccountingPurchasesAccounts;
use Faker\Generator as Faker;

$factory->define(AccountingPurchasesAccounts::class, function (Faker $faker) {
    return [
      'accounts_code' => $faker->stateAbbr ,
      'accounts_name' => $faker->state,
      'accounts_requiredInvoice' => rand(0,1),
    ];
});
