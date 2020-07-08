<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutgoingInvetory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wims_inventoryoutgoing', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('out_inventory_id')->unsigned();
            $table->integer('out_quantity')->unsigned();
            $table->integer('out_newQuantity')->unsigned();
            $table->date('out_date');
            $table->string('out_remarks',250)->nullable();
            $table->string('out_mr_num',50)->nullable();
            $table->integer('out_jo_id')->unsigned()->default(0)->nullable();
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
        Schema::dropIfExists('wims_inventoryOutgoing');
    }
}
