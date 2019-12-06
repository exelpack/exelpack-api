<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_account', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username',30);
            $table->string('password',255);
            $table->unsignedTinyInteger('npd_access');
            $table->unsignedTinyInteger('pmms_access');
            $table->unsignedTinyInteger('cposms_access');
            $table->unsignedTinyInteger('pjoms_access');
            $table->unsignedTinyInteger('cims_access');
            $table->unsignedTinyInteger('wims_access');
            $table->unsignedTinyInteger('psms_access');
            $table->string('type',20);
            $table->string('department',20);
            $table->string('fullname',50);
            $table->string('position',50);
            $table->string('signature',150)->nullable();
            $table->softdeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_account');
    }
}
