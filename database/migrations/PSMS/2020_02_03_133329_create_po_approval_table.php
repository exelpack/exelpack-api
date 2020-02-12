<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoApprovalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('psms_poApprovalDetails', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->integer('poa_po_id');
        $table->string('poa_key',300);
        $table->string('poa_approver_user',50);
        $table->string('poa_otherinfo',300);
        $table->string('poa_approvalType')->default('LAN')->nullable();
        $table->boolean('poa_approved')->default(0);
        $table->boolean('poa_rejected')->default(0);
        $table->string('poa_remarks',300);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('psms_poApprovalDetails');
    }
}
