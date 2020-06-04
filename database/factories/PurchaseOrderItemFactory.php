<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\PurchaseOrderItems;
use Faker\Generator as Faker;

$factory->define(PurchaseOrderItems::class, function (Faker $faker) {
  $date = new Carbon();
  $newDate = $date->addDays(rand(1,50));
  $item = App\Masterlist::find(rand(1, 30));
    return [
      'poi_code' => $item->m_code,
      'poi_partnum' => $item->m_partnumber,
      'poi_itemdescription' => $item->m_projectname,
      'poi_quantity' => $item->m_requiredquantity,
      'poi_unit' => $item->m_unit,
      'poi_unitprice' => $item->m_unitprice,
      'poi_deliverydate' => $newDate,
    ];
});
