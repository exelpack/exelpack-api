<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Masterlist extends Model
{
	protected $table = 'pmms_masterlist';
	protected $guarded = ['id'];

	public function attachments()
	{
		return $this->hasMany('App\MasterlistAttachments','ma_itemid');
	}

	public function customer()
	{
		return $this->belongsTo('App\Customers','m_customer_id');
	}
}
