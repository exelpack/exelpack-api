<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterlistAttachments extends Model
{
  protected $guarded = ['id'];
  protected $table = 'pmms_masterlistattachment';
  public $timestamps = false;

  public function item()
  {
  	$this->belongsTo('App\Masterlist','ma_itemid');
  }
}
