<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\PurchaseOrder;
use Faker\Generator as Faker;

$factory->define(PurchaseOrder::class, function (Faker $faker) {
    return [
      'po_customer_id' => rand(1, 30),
      'po_currency' => randomElement($array = array ('PHP', 'USD')),
      'po_date' => date('Y-m-d'),
      'po_ponum' => $faker->ean8,
    ];
});
