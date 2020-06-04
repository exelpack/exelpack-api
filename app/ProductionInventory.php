<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionInventory extends Model
{
    use SoftDeletes;
    protected $table = 'wipfg_list';
    protected $guarded = ['id'];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
