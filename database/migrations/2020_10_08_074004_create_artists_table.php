<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->string('age');
            $table->string('email');
            $table->timestamps();
        });

        Schema::create('artist_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('artist_id')->unsigned();
            $table->string('locale', 5)->index();
            $table->string('name');


            $table->foreign('artist_id')
                ->references('id')->on('artists')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('artists');
        Schema::dropIfExists('artist_translations');
    }
}
