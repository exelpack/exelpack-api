<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeightColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pmms_masterlist', function (Blueprint $table) {
            //
            $table->integer('m_weight')->after('m_costing')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pmms_masterlist', function (Blueprint $table) {
            //
            $table->dropColumn('m_weight');
        });
    }
}
