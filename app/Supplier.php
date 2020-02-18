<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{

  use SoftDeletes;

  protected $table = 'psms_supplierdetails';
  protected $guarded = ['id'];
  protected $hidden = ['created_at','updated_at','deleted_at'];

  public function prpricing(){
    return $this->hasMany('App\PurchaseRequestSupplierDetails','prsd_supplier_id');
  }
}
