<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::create('cart_item_inventories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('qty')->unsigned()->default(0);
            $table->integer('inventory_source_id')->unsigned()->nullable();
            $table->integer('cart_item_id')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('cart_item_inventories');
    }
};
