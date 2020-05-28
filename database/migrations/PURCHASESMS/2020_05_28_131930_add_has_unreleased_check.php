<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHasUnreleasedCheck extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchasesms_items', function (Blueprint $table) {
          $table->boolean('item_with_unreleasedcheck')->default(false)->after('item_unit');
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
          $table->dropColumn('item_with_unreleasedcheck');
        });
    }
}
