<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemarksAndOthersFieldOnInvoiceItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salesms_invoiceitems', function (Blueprint $table) {
            $table->string('sitem_remarks',150)->after('sitem_totalamount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salesms_invoiceitems', function (Blueprint $table) {
            $table->dropColumn('sitem_remarks');
        });
    }
}
