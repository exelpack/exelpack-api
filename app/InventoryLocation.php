<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryLocation extends Model
{
  protected $table = 'wims_locations';

  protected $fillable = ['loc_description'];

  public function inventory()
  {	
  	return $this->belongsToMany('App\Inventory','wims_inventory_locations',
  	'location_id', 'inventory_id')->withTimestamps();
  }
}
