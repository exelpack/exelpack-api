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
            $table->boolean('prsd_sentForApproval')->default(0);
            $table->string('prsd_approvalType')->default('LAN')->nullable();
            $table->string('prsd_approvalKey',300)->nullable();
            $table->boolean('prsd_isRejected')->default(0);
            $table->boolean('prsd_approvedForPO')->default(0);
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
