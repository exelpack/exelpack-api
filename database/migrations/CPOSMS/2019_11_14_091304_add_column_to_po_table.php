<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToPoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cposms_purchaseorder', function (Blueprint $table) {
            $table->boolean('isEndorsed')->default(0)->after('po_cancellationRemarks');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cposms_purchaseorder', function (Blueprint $table) {
            $table->dropColumn('isEndorsed');
        });
    }
}
