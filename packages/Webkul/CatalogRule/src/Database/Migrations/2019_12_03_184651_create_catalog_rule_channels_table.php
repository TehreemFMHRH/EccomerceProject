<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::create('catalog_rule_channels', function (Blueprint $table) {
            $table->integer('catalog_rule_id')->unsigned();
            $table->integer('channel_id')->unsigned();

            $table->primary(['catalog_rule_id', 'channel_id']);
            $table->foreign('catalog_rule_id')->references('id')->on('catalog_rules')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('catalog_rule_channels');
    }
};
