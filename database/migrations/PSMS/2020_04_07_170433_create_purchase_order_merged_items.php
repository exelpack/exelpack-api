<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderMergedItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('psms_spurchaseorderitems', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->boolean('spoi_po_id')->unsigned();
        $table->string('spoi_code',50);
        $table->string('spoi_mspecs',255);
        $table->string('spoi_uom',50);
        $table->integer('spoi_quantity');
        $table->double('spoi_unitprice')->default(0);
        $table->string('spoi_remarks',100)->nullable();
        $table->date('spoi_deliverydate');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('psms_spurchaseorderitems');
    }
}
