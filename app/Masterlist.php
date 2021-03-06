<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Masterlist extends Model
{
	protected $table = 'pmms_masterlist';
	protected $guarded = ['id'];

	public function customer()
	{
		return $this->belongsTo('App\Customers','m_customer_id');
	}

}
