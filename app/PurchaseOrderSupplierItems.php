<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderSupplierItems extends Model
{
  protected $table = 'psms_spurchaseorderitems';
  protected $guarded = ['id'];

  public $timestamps = false;

  public function spo(){
    return $this->belongsTo('App\PurchaseOrderSupplier', 'spoi_po_id');
  }

  public function invoice()
  {
    return $this->hasMany('App\SupplierInvoice','ssi_poitem_id');
  }
}
