<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterlistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pmms_masterlist', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('m_moq')->default(0)->nullable();
            $table->string('m_mspecs', 255);
            $table->string('m_projectname', 255);
            $table->string('m_partnumber', 150)->default('N/A')->nullable();
            $table->string('m_code', 50);
            $table->date('m_regisdate')->nullable();
            $table->date('m_effectdate')->nullable();
            $table->string('m_customername', 150);
            $table->integer('m_requiredquantity');
            $table->integer('m_outs');
            $table->string('m_unit', 50)->nullable();
            $table->double('m_unitprice')->default(0)->nullable();
            $table->string('m_supplierprice', 100)->nullable();
            $table->string('m_remarks', 150)->nullable();
            $table->string('m_dwg', 150)->nullable();
            $table->string('m_bom', 150)->nullable();
            $table->string('m_costing', 150)->nullable();
            $table->double('m_budgetprice')->nullable();
            $table->integer('m_customer_id')->nullable();
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
        Schema::dropIfExists('pmms_masterlist');
    }
}
