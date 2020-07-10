<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
  protected $table = 'psms_supplierinvoice';

  protected $guarded = ['id'];
  public $timestamps = false;

  public function poitem()
  {
    return $this->belongsTo('App\PurchaseOrderSupplierItems','ssi_poitem_id');
  }

  //set attribs
  public function setSsiInvoiceAttribute($value){
    $this->attributes['ssi_invoice'] = strtoupper($value); 
  }

  public function setSsiDrAttribute($value){
    $this->attributes['ssi_dr'] = strtoupper($value); 
  }

  public function setSsiRrnumAttribute($value){
    $this->attributes['ssi_rrnum'] = $value ? strtoupper($value) : null; 
  }
}
