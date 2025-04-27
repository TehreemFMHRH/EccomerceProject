<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->integer('decimal')->unsigned()->default(2);
            $table->timestamps();
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('currencies');
    }
};
