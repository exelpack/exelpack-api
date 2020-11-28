<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVatableOption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salesms_customers', function (Blueprint $table) {
            $table->boolean('c_isVatable')->after('c_paymentterms')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salesms_customers', function (Blueprint $table) {
            $table->dropColumn('c_isVatable');
        });
    }
}
