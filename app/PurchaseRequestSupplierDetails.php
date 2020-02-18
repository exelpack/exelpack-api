<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestSupplierDetails extends Model
{
  protected $table = 'psms_prsupplierdetails';
  protected $guarded = ['id'];
  protected $hidden = ['created_at','updated_at'];

  public function pr()
  {
    return $this->belongsTo('App\PurchaseRequest','prsd_pr_id');
  }

  public function prApproval()
  {
    return $this->hasMany('App\PurchaseRequestApproval','pra_prs_id');
  }

  public function supplier(){
    return $this->belongsTo('App\Supplier','prsd_supplier_id');
  }

}
