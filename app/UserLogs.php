<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserLogs extends Model
{
  
	protected $table = 'users_logs';
	
	protected $fillable = ['user_id','action','system'];

}
