<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
  
  protected $table = 'wims_inventory';
  protected $guarded = ['id','deleted_at'];
  protected $hidden = ['deleted_at'];

  public $timestamps = false;

  public function masterlist()
  {
  	return $this->belongsTo('App\Masterlist','i_code','m_code');
  }
  
}
