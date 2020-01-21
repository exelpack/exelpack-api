<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItems extends Model
{

  protected $table = 'salesms_invoiceitems';
  protected $guarded = ['id'];

  protected $hidden = ['created_at','updated_at'];

  public function sales()
  {
  	return $this->belongsTo('App\SalesInvoice','sitem_sales_id');
  }

}
