<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuyerInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buyer_info', function (Blueprint $table) {
            $table->increments('id');
            $table->string('last_name');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->integer('order_id')->unsigned();
            $table->foreign('order_id')->references('id')->on('user_orders')->onDelete('cascade');
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
        Schema::dropIfExists('buyer_info');
    }
}
