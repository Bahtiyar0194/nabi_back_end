<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MentorAwardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mentor', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('status_id')->unsigned();
            $table->foreign('status_id')->references('id')->on('status_types')->onDelete('cascade');
            $table->integer('personal_turnover');
            $table->integer('personal_group_volume');
            $table->integer('invite_count');
            $table->integer('count_teamlead_in_the_first_line');
            $table->integer('tl_in_depth');
            $table->integer('kickback');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
