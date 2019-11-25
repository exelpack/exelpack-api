<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentForMasterlist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pmms_masterlistattachment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('ma_itemid');
            $table->string('ma_attachment', 150);
            $table->tinyInteger('ma_isPublic')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pmms_masterlistattachment');
    }
}
