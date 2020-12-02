<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesCustomer extends Model
{

	use SoftDeletes;

  protected $table = 'salesms_customers';
  protected $fillable = ['c_customername','c_paymentterms', 'c_isVatable'];

  protected $hidden = ['created_at','deleted_at','updated_at'];

  public function invoice()
  {
  	return $this->hasMany('App\SalesInvoice','s_customer_id');
  }


}
