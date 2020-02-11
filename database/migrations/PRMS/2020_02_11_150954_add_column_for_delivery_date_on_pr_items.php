<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnForDeliveryDateOnPrItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prms_pritems', function (Blueprint $table) {
          $table->date('pri_deliverydate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prms_pritems', function (Blueprint $table) {
          $table->dropColumn('pri_deliverydate');
        });
    }
}
