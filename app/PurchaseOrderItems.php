<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class PurchaseOrderItems extends Model
{
    //
	protected $guarded = ['id'];
	protected $table = 'cposms_purchaseorderitem';
	protected $hidden = ['created_at','updated_at'];

	public function po()
	{
		return $this->belongsTo('App\PurchaseOrder','poi_po_id');
	}

	public function delivery()
	{
		return $this->hasMany('App\PurchaseOrderDelivery','poidel_item_id');
	}

	public function jo()
	{
		return $this->hasMany('App\JobOrder','jo_po_item_id');
	}

	public function schedule()
	{
		return $this->hasMany('App\PurchaseOrderSchedule','pods_item_id');
	}

}
