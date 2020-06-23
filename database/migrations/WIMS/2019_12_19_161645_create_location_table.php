<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('wims_locations', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('loc_description',50);
        $table->double('loc_x')->default(0);
        $table->double('loc_y')->default(0);
        $table->integer('loc_width')->default(100);
        $table->integer('loc_height')->default(80);
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
        Schema::dropIfExists('wims_locations');
    }
}
