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
            $table->date('po_date');
            $table->string('po_ponum',100);
            $table->tinyInteger('po_isForeCast')->default(0);
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
