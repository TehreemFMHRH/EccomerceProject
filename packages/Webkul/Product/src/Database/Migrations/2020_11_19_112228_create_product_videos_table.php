<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::create('product_videos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->unsigned();
            $table->string('type')->nullable();
            $table->string('path');
            $table->integer('position')->default(0)->unsigned();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('product_videos');
    }
};
