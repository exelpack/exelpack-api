<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountingPurchasesItems extends Model
{
  protected $table = 'purchasesms_items';
  protected $guarded = ['id'];

  protected $hidden = ['created_at','updated_at'];

  public function account(){
    return $this->belongsTo('App\AccountingPurchasesAccounts','item_accounts_id');
  }

  public function supplier(){
    return $this->belongsTo('App\AccountingPurchasesSupplier','item_supplier_id');
  }
}
