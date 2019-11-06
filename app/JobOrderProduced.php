<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobOrderProduced extends Model
{
  	protected $table = 'pjoms_joProduced';

  	protected $fillable = ['jop_jo_id','jop_date','jop_quantity'];

  	public $timestamps = false;

  	public function jo()
  	{
  		return $this->belongsTo('App\JobOrder','jop_jo_id');
  	}
}
