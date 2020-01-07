<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prms_prlist', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('pr_jo_id')->unsigned();
            $table->string('pr_supplier_id',10)->default(0)->nullable();
            $table->string('pr_prnum',60);
            $table->date('pr_date');
            $table->string('pr_remarks',200)->nullable();
            $table->string('pr_currency',10)->default('PHP')->nullable();
            $table->boolean('pr_forPricing')->default(0);
            $table->boolean('pr_hasPrice')->default(0);
            $table->boolean('pr_isRejected')->default(0);
            $table->boolean('pr_isApprovedForPO')->default(0);
            $table->integer('pr_po_id')->unsigned()->default(0);
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
        Schema::dropIfExists('prms_prlist');
    }
}
