<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItems extends Model
{

  protected $table = 'prms_pritems';
	protected $guarded = ['id','pri_pr_id'];
	protected $hidden = ['created_at','updated_at'];
	public $timestamps = false;

	public function pr()
	{
		return $this->belongsTo('App\PurchaseRequest','pri_pr_id');
	}

}
