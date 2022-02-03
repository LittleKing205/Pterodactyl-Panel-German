<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubdomainsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('subdomain_manager_subdomains', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('server_id');
            $table->integer('domain_id');
            $table->text('subdomain');
            $table->integer('port');
            $table->text('record_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('subdomain_manager_subdomains');
    }
}
