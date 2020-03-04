<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrSupplierDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('psms_prsupplierdetails', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->integer('prsd_pr_id')->unsigned();
        $table->integer('prsd_supplier_id')->unsigned();
        $table->string('prsd_currency',5);
        $table->integer('prsd_spo_id')->unsigned();
        $table->integer('prsd_user_id')->unsigned();
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
        Schema::dropIfExists('psms_prsupplierdetails');
    }
}
