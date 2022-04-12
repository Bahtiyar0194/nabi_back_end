<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperationTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('roles_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('status_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('personal_invitation_condition');
            $table->integer('monthly_activation_condition');
        });

        Schema::create('operation', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name_rus');
            $table->string('name_kaz');
            $table->string('name_eng');
        });

        Schema::create('operation_type', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('operation_id')->unsigned();
            $table->foreign('operation_id')->references('id')->on('operation');
            $table->string('name_rus');
            $table->string('name_kaz');
            $table->string('name_eng');
        });

        Schema::create('user_operations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('operation_type_id')->unsigned();
            $table->foreign('operation_type_id')->references('id')->on('operation_type')->onDelete('cascade');
            $table->integer('amount')->nullable();
            $table->integer('author_id')->unsigned();
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('recipient_id')->unsigned()->nullable();
            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('award_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name_rus');
            $table->string('name_kaz');
            $table->string('name_eng');
            $table->integer('is_show')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operation_type');
    }
}
