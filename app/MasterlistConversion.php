<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterlistConversion extends Model
{
    protected $table = 'pmms_conversions';
	protected $fillable = ['conversion'];

    public function items()
	{
		return $this->belongsToMany('App\Masterlist', 'masterlist_conversion', 'item_id', 'conversion_id');
	}
}
