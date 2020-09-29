<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderApproval extends Model
{
  protected $table = 'psms_poapprovaldetails';
  protected $guarded = ['id'];
  protected $hidden = ['updated_at'];

  public function po()
  {
    return $this->belongsTo('App\PurchaseOrderSupplier','poa_po_id');
  }

  public function user()
  {
    return $this->belongsTo('App\User','poa_approver_id')->withTrashed();
  }
}
