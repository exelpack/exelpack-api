<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDisplayToMapColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wims_locations', function (Blueprint $table) {
          $table->boolean('loc_showOnMap')->default(0)->after('loc_height');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wims_locations', function (Blueprint $table) {
          $table->dropColumn('loc_showOnMap');
        });
    }
}
