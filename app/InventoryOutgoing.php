<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryOutgoing extends Model
{
  protected $table = 'wims_inventoryoutgoing';
  protected $guarded = ['id'];

  public function inventory(){
		return $this->belongsTo('App\Inventory','out_inventory_id');
	}

	public function jo(){
		return $this->belongsTo('App\JobOrder','out_jo_id');
	}
}
