<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrencyOnItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchasesms_items', function (Blueprint $table) {
          $table->string('item_currency',3)->default('PHP')->after('item_unit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchasesms_items', function (Blueprint $table) {
          $table->dropColumn('item_currency');
        });
    }
}
