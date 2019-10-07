<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDelivery extends Model
{

	protected $table = 'cposms_poitemdelivery';
	protected $guarded = ['id'];

	public function item()
	{
		return $this->belongsTo('App\PurchaseOrderItems','poidel_item_id');
	}

}
