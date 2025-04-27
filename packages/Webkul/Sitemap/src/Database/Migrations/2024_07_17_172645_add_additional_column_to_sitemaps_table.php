<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::table('sitemaps', function (Blueprint $table) {
            $table->json('additional')->after('path')->nullable();
        });
    }

    
    public function down()
    {
        Schema::table('sitemaps', function (Blueprint $table) {
            $table->dropColumn('additional');
        });
    }
};
