<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Masterlist extends Model
{
  	protected $table = 'pmms_masterlist';

  	protected $guarded = ['id'];
}
