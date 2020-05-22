<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsPurchases extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchasesms_apdetails', function (Blueprint $table) {
          $table->bigIncrements('id');
          $table->integer('ap_item_id')->unsigned();
          $table->double('ap_withholding')->default(0)->nullable();
          $table->string('ap_officialreceipt_no',100);
          $table->boolean('ap_is_check')->default(0);
          $table->string('ap_check_no',50)->nullable();
          $table->string('ap_bankname',150)->nullable();
          $table->date('ap_payment_date')->nullable();
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
        Schema::dropIfExists('purchasesms_apdetails');
    }
}
