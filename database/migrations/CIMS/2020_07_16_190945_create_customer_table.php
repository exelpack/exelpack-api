<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_information', function (Blueprint $table) {
          $table->bigIncrements('id');
          $table->string('companyname',100)->unique();
          $table->string('companyaddress',150);
          $table->string('companynature',150);
          $table->string('companypremises',10);
          $table->integer('companyoperationyears');
          $table->string('companybusinesstype',50);
          $table->string('companycontactperson',50);
          $table->string('companycontactposition',50)->nullable();
          $table->integer('partnershiplength_years')->nullable();
          $table->integer('partnershiplength_months')->nullable();
          $table->string('companytelephone',20)->nullable();
          $table->string('companyfax',20)->nullable();
          $table->string('companysss',30)->nullable();
          $table->string('companytin',30)->nullable();
          $table->string('companyemail',50)->nullable();
          $table->string('ownername',50)->nullable();
          $table->string('owneraddress',100)->nullable();
          $table->string('ownertelephone',50)->nullable();
          $table->string('owneremail',50)->nullable();
          $table->string('approval_status',30)->default('PENDING APPROVAL')->nullable();
          $table->string('approved_by',60)->nullable();
          $table->date('approval_date')->nullable();
          $table->string('recommended_by',50)->nullable();
          $table->date('recommended_date')->nullable();
          $table->string('comment',150)->nullable();
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
        Schema::dropIfExists('customer_information');
    }
}
