<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('last_name');
            $table->integer('sponsor_id')->nullable()->unsigned();
            $table->foreign('sponsor_id')->references('id')->on('users');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->integer('current_status_id')->unsigned()->default(1);
            $table->foreign('current_status_id')->references('id')->on('status_types');
            $table->integer('maximal_status_id')->unsigned()->default(1);
            $table->foreign('maximal_status_id')->references('id')->on('status_types');
            $table->float('main_wallet')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
