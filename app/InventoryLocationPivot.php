<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryLocationPivot extends Model
{
  protected $table = 'wims_inventory_locations';

  protected $guarded = ['id'];
}
