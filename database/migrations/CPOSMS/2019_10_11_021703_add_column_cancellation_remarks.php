<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnCancellationRemarks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cposms_purchaseorder', function (Blueprint $table) {
            $table->string('po_cancellationRemarks',100)->after('po_isForeCast')->nullable();
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
            $table->dropColumn('po_cancellationRemarks');
        });
    }
}
