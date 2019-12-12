<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wims_inventory', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('i_mspecs', 255);
            $table->string('i_projectname', 255);
            $table->string('i_partnumber', 150);
            $table->string('i_code', 50);
            $table->double('i_unitprice')->nullable();
            $table->string('i_unit',50)->nullable();
            $table->integer('i_quantity')->unsigned();
            $table->integer('i_min')->unsigned()->nullable();
            $table->integer('i_max')->unsigned()->nullable();
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
        Schema::dropIfExists('inventory');
    }
}
