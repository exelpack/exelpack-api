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
    return $this->hasOne('App\PurchaseRequestApproval','pra_prs_id');
  }

  public function supplier(){
    return $this->belongsTo('App\Supplier','prsd_supplier_id');
  }

  public function po()
  {
    return $this->belongsTo('App\PurchaseOrderSupplier','prsd_spo_id');
  }

  public function user()
  {
    return $this->belongsTo('App\User','prsd_user_id')->withTrashed();
  }

}
