<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('description');
            $table->json('meta_desc');
            $table->string('code');
            $table->string('warranty')->default(0);
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('collection_id');
            $table->json('technical_specifications');
            $table->string('product_type');
            $table->json('options');
            $table->json('videos');
            $table->json('variant_options');
            $table->json('languages');
            $table->boolean('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
