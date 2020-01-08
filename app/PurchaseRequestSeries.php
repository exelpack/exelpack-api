<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestSeries extends Model
{
  protected $table = 'prms_prseries';
	protected $fillable = ['series_prefix','series_number'];

	public $timestamps = false;
}
