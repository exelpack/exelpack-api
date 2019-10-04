<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItems extends Model
{
    //
	protected $timestamps = false;
	protected $guarded = ['id'];
	protected $table = 'cposms_purchaseorderitem';

	public function po()
	{
		return $this->belongsTo('App\PurchaseOrder','poi_po_id');
	}

}
