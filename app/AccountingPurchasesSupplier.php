<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingPurchasesSupplier extends Model
{
  use SoftDeletes;
  protected $table = 'purchasesms_supplier';
  protected $guarded = ['id'];

  protected $hidden = ['created_at','updated_at'];

  public function items(){
    return $this->hasMany('App\AccountingPurchasesItems','item_supplier_id');
  }
}
