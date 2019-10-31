<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CposmsLogs extends Model
{
  protected $table = 'cposms_logs';

  protected $fillable = ['user_id','action','before','after','owner_id','class'];

  public function user()
  {
  	return $this->belongsTo('App\User')->withTrashed();
  }

}