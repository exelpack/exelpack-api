<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReflectOnReportColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchasesms_supplier', function (Blueprint $table) {
            $table->boolean('supplier_reflect_on_report')->default(0)->after('supplier_tin_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchasesms_supplier', function (Blueprint $table) {
            //
        });
    }
}
