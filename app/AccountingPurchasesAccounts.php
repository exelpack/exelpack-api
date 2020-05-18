<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountingPurchasesAccounts extends Model
{
  protected $table = 'purchasesms_accounts';
  protected $guarded = ['id'];

  protected $hidden = ['created_at','updated_at'];

  public function items(){
    return $this->hasMany('App\AccountingPurchasesItems','item_accounts_id')->withTrashed();
  }
}
