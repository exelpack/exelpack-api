<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCPOSMSPurchaseOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cposms_purchaseorder', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('po_customer_id')->unsigned();
            $table->string('po_currency');
            $table->string('po_ponum',100);
            $table->string('po_forecast',255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cposms_purchaseorder');
    }
}
