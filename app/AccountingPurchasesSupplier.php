<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountingPurchasesSupplier extends Model
{
  protected $table = 'purchasesms_supplier';
  protected $guarded = ['id'];

  protected $hidden = ['created_at','updated_at'];

  public function items(){
    return $this->hasMany('App\AccountingPurchasesItems','item_supplier_id')->withTrashed();
  }
}
