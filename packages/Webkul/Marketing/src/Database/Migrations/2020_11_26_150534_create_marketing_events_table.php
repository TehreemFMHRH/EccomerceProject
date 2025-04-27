<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::create('marketing_events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('date')->nullable();
            $table->timestamps();
        });

        
        DB::table('marketing_events')->insert([
            'name'        => 'Birthday',
            'description' => 'Birthday',
        ]);
    }

    
    public function down()
    {
        Schema::dropIfExists('marketing_events');
    }
};
