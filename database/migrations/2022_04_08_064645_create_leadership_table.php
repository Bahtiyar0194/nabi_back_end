<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadershipTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leadership', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('status_id')->unsigned();
            $table->foreign('status_id')->references('id')->on('status_types')->onDelete('cascade');
            $table->integer('count_tl_f_l');
            $table->integer('count_dd_f_l');
            $table->integer('count_month')->unsigned();
            $table->integer('personal_turnover');
            $table->integer('personal_group_turnover');
            $table->integer('invite_count');
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
        Schema::dropIfExists('leadership');
    }
}
