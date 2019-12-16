<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryIncoming extends Model
{
	protected $table = 'wims_inventoryincoming';
	protected $guarded = ['id'];

	public function inventory()
	{
		return $this->belongsTo('App\Inventory','inc_inventory_id');
	}

}
