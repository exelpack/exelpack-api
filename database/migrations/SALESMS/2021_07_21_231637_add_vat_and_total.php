<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVatAndTotal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salesms_invoiceitems', function (Blueprint $table) {
            $table->double('sitem_vat')->nullable();
            $table->double('sitem_total')->nullable();
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
            //
        });
    }
}
