<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionFG extends Model
{
    protected $table = 'production_fg';

    protected $fillable = ['id', 'prodinv_id', 'quantity', 'po_id'];
}
