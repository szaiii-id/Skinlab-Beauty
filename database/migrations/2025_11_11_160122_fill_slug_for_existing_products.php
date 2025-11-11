<?php

use App\Models\Product; 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str; 

return new class extends Migration
{
    public function up(): void
    {
        Product::all()->each(function (Product $product) {
            $product->slug = Str::slug($product->name);
            $product->save();
        });
        
        Schema::table('products', function (Blueprint $table) {
            $table->unique('slug'); 
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });
    }
};