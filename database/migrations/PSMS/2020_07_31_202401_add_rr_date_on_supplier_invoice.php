<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRrDateOnSupplierInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('psms_supplierinvoice', function (Blueprint $table) {
          $table->date('ssi_rrdate')->after('ssi_rrnum')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('psms_supplierinvoice', function (Blueprint $table) {
          $table->dropColumn('ssi_rrdate');
        });
    }
}
