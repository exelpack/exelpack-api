<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionInventory extends Model
{
    protected $table = "production_inventory";
    
    protected $fillable = ['id', 'code', 'itemdescription'];

    
}
