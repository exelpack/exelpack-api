<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Customers;
use Faker\Generator as Faker;

$factory->define(Customers::class, function (Faker $faker) {
    return [
      'companyname' => $faker->company,
      'companyaddress' => $faker->address,
      'companynature' => $faker->randomElement($array = array ('Manufacturing', 'Agriculture', 'Merchandise')),
      'companypremises' => $faker->randomElement($array = array ('OWNED', 'RENTED')),
      'companyoperationyears' => rand(1, 10),
      'companybusinesstype' => $faker->randomElement($array = array ('Corporation', 'Single Proprietorship', 'Partnership')),
      'companycontactperson' => $faker->name,
    ];
});
