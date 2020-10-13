<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionWIP extends Model
{
    protected $table ='production_wip';

    protected $fillable = ['id', 'prodinv_id', 'quantity', 'po_id'];
}
