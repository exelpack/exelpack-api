<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJoProduce extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pjoms_joProduced', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('jop_jo_id')->unsigned();
            $table->date('jop_date');
            $table->integer('jop_quantity');
            $table->string('jop_remarks',150)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pjoms_joProduced');
    }
}
