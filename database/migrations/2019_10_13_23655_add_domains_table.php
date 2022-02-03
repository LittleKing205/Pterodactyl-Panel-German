<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDomainsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('subdomain_manager_domains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('domain');
            $table->text('egg_ids');
            $table->text('protocol');
            $table->text('protocol_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('subdomain_manager_domains');
    }
}
