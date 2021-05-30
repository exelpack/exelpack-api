<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTinAndAddressToCustomer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salesms_customers', function (Blueprint $table) {
            $table->string('c_address')->after('c_customername');
            $table->string('c_tin')->after('c_address');
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
            $table->dropColumn('c_address');
            $table->dropColumn('c_tin');
        });
    }
}
