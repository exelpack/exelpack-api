<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobOrder extends Model
{
    //
	protected $table = 'pjoms_joborder';
	protected $guarded = ['jo_po_item_id'];
	protected $hidden = ['created_at','deleted_at'];

	public function poitems()
	{
		return $this->belongsTo('App\PurchaseOrderItems','jo_po_item_id');
	}

	public function produced()
	{
		return $this->hasMany('App\JobOrderProduced','jop_jo_id');
	}

}
