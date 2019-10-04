<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{

		use SoftDeletes;

		protected $table = 'cposms_purchaseorder';

		protected $guarded = ['id'];
		protected $hidden = ['created_at','deleted_at','updated_at'];

		public function poitems()
		{
			return $this->hasMany('App\PurchaseOrderItems','poi_po_id');
		}

		public function customer()
		{
			return $this->belongsTo('App\Customers','po_customer_id');
		}

}
