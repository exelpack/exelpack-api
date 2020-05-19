<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountingPurchasesAP extends Model
{
    //
  protected $table = 'purchasesms_apdetails';
  protected $guarded = ['id'];

  protected $hidden = ['created_at','updated_at'];

  public function item(){
    return $this->belongsTo('App\AccountingPurchasesItems','ap_item_id');
  }
}
