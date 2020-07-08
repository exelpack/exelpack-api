<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomingInvetory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wims_inventoryincoming', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('inc_inventory_id')->unsigned();
            $table->integer('inc_quantity')->unsigned();
            $table->integer('inc_newQuantity')->unsigned();
            $table->date('inc_date');
            $table->string('inc_remarks',250)->nullable();
            $table->integer('inc_spoi_id')->unsigned()->default(0); //spo =  po's item for supplier
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
        Schema::dropIfExists('wims_inventoryIncoming');
    }
}
