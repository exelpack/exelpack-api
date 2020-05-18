<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchasesms_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('item_datereceived');
            $table->date('item_datepurchased');
            $table->integer('item_supplier_id');
            $table->integer('item_accounts_id');
            $table->string('item_salesinvoice_no',50)->nullable();
            $table->string('item_deliveryreceipt_no',50)->nullable();
            $table->string('item_purchaseorder_no',50)->nullable();
            $table->string('item_purchaserequest_no',50)->nullable();
            $table->string('item_particular',255);
            $table->integer('item_quantity');
            $table->string('item_unit',50);
            $table->double('item_unitprice');
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
        Schema::dropIfExists('purchasesms_items');
    }
}
