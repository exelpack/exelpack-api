<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('psms_prApprovalDetails', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->integer('pra_prs_id');
        $table->string('pra_key',300);
        $table->integer('pra_approver_id')->unsigned();
        $table->string('pra_approver_user',50);
        $table->string('pra_otherinfo',300);
        $table->string('pra_approvalType')->default('LAN')->nullable();
        $table->boolean('pra_approved')->default(0);
        $table->boolean('pra_rejected')->default(0);
        $table->date('pra_date')->nullable();
        $table->string('pra_remarks',300);
        $table->timestamps();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('psms_prApprovalDetails');
    }
}
