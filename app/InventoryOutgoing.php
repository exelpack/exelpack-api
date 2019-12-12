<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryOutgoing extends Model
{
  protected $table = 'wims_inventoryoutgoing';
  protected $guarded = ['id'];

  public $timestamps = false;
}
