<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Softdeletes;

class InventoryLocation extends Model
{
  use SoftDeletes;

  protected $table = 'wims_locations';

  protected $guarded = ['id'];

  public $timestamps = false;

  public function inventory()
  {	
  	return $this->belongsToMany('App\Inventory','wims_inventory_locations',
  	'location_id', 'inventory_id')->withTimestamps();
  }

  public function setLocDescriptionAttribute($value){
    $this->attributes['loc_description'] = trim(strtoupper($value));
  }
}
