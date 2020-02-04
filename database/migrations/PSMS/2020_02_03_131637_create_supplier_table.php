<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('psms_supplierdetails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sd_supplier_name',250);
            $table->string('sd_address',255)->nullable();
            $table->string('sd_tin',50)->nullable();
            $table->string('sd_attention',50)->nullable();
            $table->string('sd_paymentterms',50)->nullable();
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
        Schema::dropIfExists('psms_supplierdetails');
    }
}
