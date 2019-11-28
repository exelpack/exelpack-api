<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PmmsLogs extends Model
{
	protected $table = 'pmms_logs';

	protected $fillable = ['user_id','action','before','after'];

	public function user()
	{
		return $this->belongsTo('App\User','user_id')->withTrashed();
	}
}
