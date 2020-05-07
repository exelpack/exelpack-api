<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;
class PurchaseOrder extends Model
{

		use SoftDeletes;

		protected $table = 'cposms_purchaseorder';

		protected $guarded = ['id'];
		protected $hidden = ['created_at','deleted_at','updated_at'];

    public function getPoPonumAttribute($value){
      return strtoupper($value);
    }

		public function poitems()
		{
			return $this->hasMany('App\PurchaseOrderItems','poi_po_id');
		}

		public function getTotalItemQuantity()
		{
			return $this->hasOne('App\PurchaseOrderItems','poi_po_id')
								->select(DB::raw('sum(poi_quantity) as totalQuantity'),'poi_po_id');
		}

		public function getTotalDeliveryQuantity()
		{
			return $this->hasOneThrough('App\PurchaseOrderDelivery','App\PurchaseOrderItems','poi_po_id','poidel_item_id','id','id')->select(DB::raw('sum(poidel_quantity + poidel_underrun_qty) as totalDelivered'),'poidel_deliverydate');
		}

		public function customer()
		{
			return $this->belongsTo('App\Customers','po_customer_id')->select('id','companyname');
		}

}
