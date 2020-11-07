<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRecommendedFieldOnApprovalDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('psms_prapprovaldetails', function (Blueprint $table) {
            //
            $table->integer('pra_recommendee_id')->default(0)->after('pra_approver_id');
            $table->string('pra_recommendee_user', 30)->nullable()->after('pra_approver_user');
            $table->boolean('pra_recommended')->default(0)->after('pra_approved');
            $table->date('pra_recommended_date')->nullable()->after('pra_rejected');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('psms_prapprovaldetails', function (Blueprint $table) {
            //
        });
    }
}
