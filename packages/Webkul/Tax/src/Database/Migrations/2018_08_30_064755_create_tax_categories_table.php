<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::create('tax_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->longtext('description');
            $table->timestamps();
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('tax_categories');
    }
};
