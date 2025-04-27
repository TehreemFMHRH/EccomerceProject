<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('cart_shipping_rates', function (Blueprint $table) {
            $table->integer('cart_id')->nullable()->unsigned();

            $table->foreign('cart_id')->references('id')->on('cart')->onDelete('cascade');
        });
    }

    
    public function down(): void
    {
        Schema::table('cart_shipping_rates', function (Blueprint $table) {
            $table->dropForeign(['cart_id']);
            $table->dropColumn('cart_id');
        });
    }
};
