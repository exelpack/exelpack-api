<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesInvoiceInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salesms_invoice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('s_customer_id')->unsigned();
            $table->string('s_invoicenum',50);
            $table->date('s_deliverydate');
            $table->string('s_currency',5);
            $table->string('s_ornumber',100)->nullable();
            $table->date('s_datecollected')->nullable();
            $table->double('s_withholding')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salesms_invoice');
    }
}
