<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePsmsLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('psms_logs', function (Blueprint $table) {
          $table->bigIncrements('id');
          $table->integer('user_id')->unsigned();
          $table->string('action');
          $table->string('before',300);
          $table->string('after',300);
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
        Schema::dropIfExists('psms_logs');
    }
}
