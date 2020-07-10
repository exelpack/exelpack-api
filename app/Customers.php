<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customers extends Model
{
    //
	protected $table = 'customer_information';
  protected $guarded = ['id'];

	public function po()
	{
		return $this->hasMany('App\PurchaseOrder','po_customer_id');
	}

	public function mitem()
	{
		return $this->hasMany('App\Masterlist','m_customer_id');
	}
	
}
