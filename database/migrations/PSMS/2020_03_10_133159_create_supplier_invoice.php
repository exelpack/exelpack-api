<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('psms_supplierinvoice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('ssi_poitem_id')->unsigned();
            $table->string('ssi_invoice',50)->nullable();
            $table->string('ssi_dr',50);
            $table->date('ssi_date');
            $table->integer('ssi_underrunquantity')->unsigned();
            $table->integer('ssi_drquantity')->unsigned();
            $table->string('ssi_rrnum',100)->nullable();
            $table->integer('ssi_inspectedquantity')->unsigned();
            $table->integer('ssi_receivedquantity')->unsigned();
            $table->string('ssi_remarks',100);
            $table->integer('ssi_rejectquanttiy')->unsigned();
            $table->string('ssi_rejectionremarks',250)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('psms_supplierinvoice');
    }
}
