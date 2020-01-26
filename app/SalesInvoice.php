<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Softdeletes;

class SalesInvoice extends Model
{

  use Softdeletes;

  protected $table = 'salesms_invoice';
  protected $guarded = ['id'];

  protected $hidden = ['created_at','updated_at','deleted_at'];

  public function customer()
  {
  	return $this->belongsTo('App\SalesCustomer','s_customer_id')->withTrashed();
  }

  public function items()
  {
  	return $this->hasMany('App\SalesInvoiceItems','sitem_sales_id');
  }
}
