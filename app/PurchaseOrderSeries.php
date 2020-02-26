<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderSeries extends Model
{
  protected $table = 'psms_poseries';
  protected $fillable = ['series_prefix','series_number'];

  public $timestamps = false;
}
