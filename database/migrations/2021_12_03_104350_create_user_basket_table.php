<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserBasketTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_status', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('color');
        });

        Schema::create('user_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('buyer_id')->nullable()->unsigned();
            $table->foreign('buyer_id')->references('id')->on('users');
            $table->integer('payment_type_id')->nullable()->unsigned();
            $table->foreign('payment_type_id')->references('id')->on('payment_type');
            $table->integer('status')->default(1)->unsigned();;
            $table->foreign('status')->references('id')->on('order_status');
            $table->timestamps();
        });

        Schema::create('basket_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_id')->unsigned();
            $table->foreign('order_id')->references('id')->on('user_orders');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
            $table->float('product_amount')->nullable();
            $table->float('product_mark')->nullable();
            $table->integer('product_count')->default(1);
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
        Schema::dropIfExists('user_basket');
    }
}
