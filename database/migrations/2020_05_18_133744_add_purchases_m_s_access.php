<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchasesMSAccess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users_account', function (Blueprint $table) {
          $table->unsignedTinyInteger('purchasesms_access')->after('salesms_access');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users_account', function (Blueprint $table) {
          $table->dropColumn('purchasesms_access');
        });
    }
}
