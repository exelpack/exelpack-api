<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobOrderSeries extends Model
{

	protected $table = 'pjoms_joseries';
	protected $fillable = ['series_prefix','series_number'];

	public $timestamps = false;
}
