<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::create('attribute_families', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('name');
            $table->boolean('status')->default(0);
            $table->boolean('is_user_defined')->default(1);
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('attribute_families');
    }
};
