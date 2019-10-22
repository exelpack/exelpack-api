<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCposmsDeliveryScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cposms_podeliveryschedule', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('pods_user_id')->unsigned();
            $table->integer('pods_item_id')->unsigned();
            $table->integer('pods_quantity');
            $table->string('pods_remarks');
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
        Schema::dropIfExists('cposms_podeliveryschedule');
    }
}
