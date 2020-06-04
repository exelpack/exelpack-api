<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableWipfgList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wipfg_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fw_customer', 155);
            $table->string('fw_code', 50);
            $table->string('fw_partnumber', 150)->nullable();
            $table->string('fw_itemdescription', 150);
            $table->unsignedInteger('fw_wipquantity');
            $table->unsignedInteger('fw_fgquantity');
            $table->string('fw_wiplocation', 150)->nullable();
            $table->string('fw_fglocation', 150)->nullable();
            $table->string('fw_wipremarks', 255)->nullable();
            $table->string('fw_fgremarks', 255)->nullable();
            $table->unsignedInteger('fw_wipmin');
            $table->unsignedInteger('fw_wipmax');
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
        Schema::dropIfExists('wipfg_list');
    }
}
