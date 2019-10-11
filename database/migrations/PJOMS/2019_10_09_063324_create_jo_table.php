<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pjoms_joborder', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('jo_po_item_id')->unsigned();
            $table->string('jo_joborder',60);
            $table->date('jo_dateissued');
            $table->date('jo_dateneeded');
            $table->integer('jo_quantity');
            $table->string('jo_remarks',150)->nullable();
            $table->string('jo_others',150)->nullable();
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
        Schema::dropIfExists('pjoms_joborder');
    }
}
