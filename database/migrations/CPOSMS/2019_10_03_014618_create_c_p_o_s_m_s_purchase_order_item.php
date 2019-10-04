<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCPOSMSPurchaseOrderItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cposms_purchaseorderitem', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('poi_po_id')->integer();
            $table->string('poi_code',50);
            $table->string('poi_partnum',50);
            $table->string('poi_itemdescription',150);
            $table->double('poi_quantity');
            $table->string('poi_unit',30);
            $table->date('poi_deliverydate');
            $table->string('poi_kpi',20)->nullable();
            $table->string('poi_others',60)->nullable();
            $table->string('poi_remarks',100)->nullable();
            // $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cposms_purchaseorderitem');
    }
}
