<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderSchedule extends Model
{
   protected $table = 'cposms_podeliveryschedule';

   protected $guarded = ['pods_item_id','id'];
   protected $hidden = ['created_at','updated_at'];

   public function item()
   {
     return $this->belongsTo('App\PurchaseOrderItems','pods_item_id');
   }
   
}
