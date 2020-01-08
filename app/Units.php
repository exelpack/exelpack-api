<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Units extends Model
{
  protected $table = 'options_select';
  protected $fillable = ['unit'];
  public $timestamps = false;
}
