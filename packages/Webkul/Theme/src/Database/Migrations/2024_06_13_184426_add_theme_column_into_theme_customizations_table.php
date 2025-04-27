<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('theme_customizations', function (Blueprint $table) {
            $table->string('theme_code')->nullable()->after('id')->default('default');
        });
    }

    
    public function down(): void
    {
        Schema::table('theme_customizations', function (Blueprint $table) {
            $table->dropColumn('theme_code');
        });
    }
};
