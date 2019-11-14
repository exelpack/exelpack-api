<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailListCposms extends Model
{
  
  public $timestamps = false;

  protected $table = 'cposms_mail';

  protected $fillable = ['email'];

}
