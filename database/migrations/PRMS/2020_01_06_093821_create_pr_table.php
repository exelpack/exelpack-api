<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prms_prlist', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('pr_jo_id')->unsigned();
            $table->string('pr_prnum',60)->unique();
            $table->date('pr_date');
            $table->string('pr_remarks',200)->nullable();
            $table->boolean('pr_forPricing')->default(0);
            $table->integer('pr_user_id')->unsigned();
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
        Schema::dropIfExists('prms_prlist');
    }
}
