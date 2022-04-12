<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_status', function (Blueprint $table) {
           $table->increments('id');
           $table->string('name_rus');
           $table->string('name_kaz');
           $table->string('name_eng');
           $table->integer('is_show')->default(1);
           $table->timestamps();
       });

        Schema::create('product_category', function (Blueprint $table) {
           $table->increments('id');
           $table->string('name_rus');
           $table->string('name_kaz');
           $table->string('name_eng');
           $table->integer('is_show')->default(1);
           $table->timestamps();
       });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('mini_description_rus');
            $table->string('mini_description_kaz');
            $table->string('mini_description_eng');
            $table->text('description_rus');
            $table->text('description_kaz');
            $table->text('description_eng');
            $table->integer('amount');
            $table->integer('client_amount_perc');
            $table->integer('dist_amount_perc');
            $table->integer('product_category_id')->unsigned();
            $table->foreign('product_category_id')->references('id')->on('product_category')->onDelete('cascade');
            $table->string('image');
            $table->integer('product_status_id')->unsigned();
            $table->foreign('product_status_id')->references('id')->on('product_status')->onDelete('cascade');
            $table->integer('is_show')->default(1);
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
}
