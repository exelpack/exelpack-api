<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesInvoiceItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salesms_invoiceitems', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('sitem_sales_id')->unsigned();
            $table->string('sitem_drnum',50)->nullable();
            $table->string('sitem_ponum',50);
            $table->string('sitem_partnum',100)->nullable();
            $table->integer('sitem_quantity');
            $table->double('sitem_unitprice');
            $table->double('sitem_totalamount');
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
        Schema::dropIfExists('salesms_invoiceitems');
    }
}
