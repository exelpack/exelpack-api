<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{

	protected $table = 'prms_prlist';
	protected $guarded = ['id','pr_jo_id'];
	protected $hidden = ['created_at','updated_at'];

	public function pritems()
	{
		return $this->hasMany('App\PurchaseRequestItems','pri_pr_id');
	}

	public function jo()
	{
		return $this->belongsTo('App\JobOrder','pr_jo_id');
	}

  public function prpricing()
  {
    return $this->hasOne('App\PurchaseRequestSupplierDetails','prsd_pr_id');
  }

}
