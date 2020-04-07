<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReceivingReportSeries extends Model
{
  protected $table = 'psms_rrseries';
  protected $fillable = ['series_prefix','series_number'];

  public $timestamps = false;
}
