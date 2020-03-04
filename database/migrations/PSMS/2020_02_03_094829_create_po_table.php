<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('psms_spurchaseorder', function (Blueprint $table) {
          $table->bigIncrements('id');
          $table->string('spo_ponum',50);
          $table->date('spo_date');
          $table->string('spo_status',50)->default('Pending');
          $table->integer('spo_user_id')->unsigned();
          $table->softDeletes();
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
        Schema::dropIfExists('psms_spurchaseorder');
    }
}
