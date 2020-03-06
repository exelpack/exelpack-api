<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestApproval extends Model
{
  protected $table = 'psms_prapprovaldetails';
  protected $guarded = ['id'];
  protected $hidden = ['updated_at'];

  public function prprice()
  {
    return $this->belongsTo('App\PurchaseRequestSupplierDetails','pra_prs_id');
  }

  public function user()
  {
    return $this->belongsTo('App\User','pra_approver_id')->withTrashed();
  }

}
