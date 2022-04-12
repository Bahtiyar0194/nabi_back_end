<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecruitingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recruiting', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('award_type_id')->default(1)->unsigned();
            $table->foreign('award_type_id')->references('id')->on('award_types');
            $table->integer('price');
            $table->integer('send_money');
            $table->integer('max_iteration');
            $table->integer('admin_id')->default(1)->unsigned();
            $table->foreign('admin_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recruiting');
    }
}
