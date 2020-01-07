<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prms_pritems', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('pri_pr_id')->unsigned();
            $table->string('pri_code',50);
            $table->string('pri_mspecs',255);
            $table->string('pri_projectname',255);
            $table->string('pri_uom',50);
            $table->integer('pri_quantity');
            $table->double('pri_unitprice')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prms_pritems');
    }
}
