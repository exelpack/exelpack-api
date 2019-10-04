<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customers extends Model
{
    //
	protected $table = 'customer_information';

	public function po()
	{
		return $this->hasMany('App\PurchaseOrder','po_customer_id');
	}
	
}
