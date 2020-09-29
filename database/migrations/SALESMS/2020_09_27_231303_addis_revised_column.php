<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddisRevisedColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salesms_invoice', function (Blueprint $table) {
           $table->boolean('s_isRevised')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salesms_invoice', function (Blueprint $table) {
            $table->dropColumn('s_isRevised');
        });
    }
}
