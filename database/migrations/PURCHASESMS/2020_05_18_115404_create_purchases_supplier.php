<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesSupplier extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchasesms_supplier', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('supplier_name',255);
            $table->integer('supplier_payment_terms');
            $table->string('supplier_address',300);
            $table->string('supplier_tin_number',100);
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
        Schema::dropIfExists('purchasesms_supplier');
    }
}
