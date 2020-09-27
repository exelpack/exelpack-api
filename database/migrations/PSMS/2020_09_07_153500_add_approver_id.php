<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApproverId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('psms_poapprovaldetails', function (Blueprint $table) {
          $table->integer('poa_approver_id')->after('poa_po_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('psms_poapprovaldetails', function (Blueprint $table) {
          $table->dropColumn('poa_approver_id');
        });
    }
}
