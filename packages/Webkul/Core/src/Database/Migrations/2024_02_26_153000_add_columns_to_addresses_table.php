<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->unsignedInteger('parent_address_id')->nullable()->after('address_type');
            $table->boolean('use_for_shipping')->default(0)->after('default_address');

            $table->foreign('parent_address_id')->references('id')->on('addresses')->onDelete('set null');
        });
    }

    
    public function down()
    {
        Schema::dropIfExists('addresses');
    }
};
