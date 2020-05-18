<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchasesms_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('accounts_code',50)->nullable();
            $table->string('accounts_name',250);
            $table->boolean('accounts_requiredInvoice')->default(0);
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
        Schema::dropIfExists('purchasesms_accounts');
    }
}
