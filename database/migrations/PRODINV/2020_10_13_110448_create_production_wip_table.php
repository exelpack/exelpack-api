<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionWipTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('production_wip', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('prodinv_id')->unsigned();
            $table->integer('quantity')->unsigned();
            $table->integer('po_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('production_wip');
    }
}
