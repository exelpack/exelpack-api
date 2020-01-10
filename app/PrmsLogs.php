<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PrmsLogs extends Model
{
	protected $table = 'prms_logs';

	protected $fillable = ['user_id','action','before','after'];

	public function user()
	{
		return $this->belongsTo('App\User','user_id')->withTrashed();
	}
}
