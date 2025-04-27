<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->integer('default_value')->nullable()->after('value_per_channel');
        });
    }

    
    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropColumn('default_value');
        });
    }
};
