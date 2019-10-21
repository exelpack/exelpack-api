<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoItemDeliverySchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cposms_poitemdelivery', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('poidel_item_id')->unsigned();
            $table->integer('poidel_quantity')->nullable();
            $table->integer('poidel_underrun_qty')->nullable();
            $table->date('poidel_deliverydate');
            $table->string('poidel_invoice',70)->nullable();
            $table->string('poidel_dr',70)->nullable();
            $table->string('poidel_remarks',150)->nullable();
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
        Schema::dropIfExists('cposms_poitemdelivery');
    }
}
