<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::create('product_grouped_products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->unsigned();
            $table->integer('associated_product_id')->unsigned();
            $table->integer('qty')->default(0);
            $table->integer('sort_order')->default(0);

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('associated_product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('product_grouped_products');
    }
};
