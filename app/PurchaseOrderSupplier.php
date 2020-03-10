<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderSupplier extends Model
{
  use SoftDeletes;

  protected $table = 'psms_spurchaseorder';
  protected $guarded = ['id','created_at','updated_at'];
  protected $hidden = ['deleted_at','updated_at'];

  public function prprice()
  {
    return $this->hasMany('App\PurchaseRequestSupplierDetails','prsd_spo_id');
  }

  public function user()
  {
    return $this->belongsTo('App\User','spo_user_id')->withTrashed();
  }
}
