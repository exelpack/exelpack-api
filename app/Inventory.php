<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
	use SoftDeletes;

	protected $table = 'wims_inventory';
	protected $guarded = ['id','deleted_at'];
	protected $hidden = ['deleted_at'];

	public $timestamps = false;

	public function masterlist()
	{
		return $this->belongsTo('App\Masterlist','i_code','m_code');
	}

	public function incoming()
	{
		return $this->hasMany('App\InventoryIncoming','inc_inventory_id');
	}

	public function outgoing()
	{
		return $this->hasMany('App\InventoryOutgoing','out_inventory_id');
	}


}
