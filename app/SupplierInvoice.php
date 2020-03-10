<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
  protected $table = 'psms_supplierinvoice';

  protected $guarded = ['id'];
  public $timestamps = false;

  public function pritem()
  {
    return $this->belongsTo('App\PurchaseRequestItems','ssi_pritem_id');
  }
}
