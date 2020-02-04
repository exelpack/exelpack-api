<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestSupplierDetails extends Model
{
  protected $table = 'psms_supplierdetails';
  protected $guarded = ['id'];
  protected $hidden = ['created_at','updated_at'];

  public function pr()
  {
    return $this->belongsTo('App\PurchaseRequest','prsd_pr_id');
  }

}
