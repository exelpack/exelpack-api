<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PjomsLogs extends Model
{
  protected $table = 'pjoms_logs';

  protected $fillable = ['user_id','action','before','after'];

  public function user()
  {
  	return $this->belongsTo('App\User','user_id')->withTrashed();
  }

}
