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
        $table->double('loc_x');
        $table->double('loc_y');
        $table->integer('loc_width');
        $table->integer('loc_height');
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
