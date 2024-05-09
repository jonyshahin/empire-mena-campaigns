<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consumers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('outlet_id')->unsigned();
            $table->foreign('outlet_id')->references('id')->on('outlets')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->string('telephone')->nullable();
            $table->bigInteger('competitor_brand_id')->unsigned();
            $table->foreign('competitor_brand_id')->references('id')->on('competitor_brands')->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('franchise')->default(0);
            $table->boolean('did_he_switch')->default(0);
            $table->enum('aspen', ['aspen_menthol_blue', 'aspen_white', 'aspen_blue'])->default('aspen_menthol_blue');
            $table->integer('packs')->default(1);
            $table->enum('incentives', ['lvl1', 'lvl2'])->default('lvl1');
            $table->integer('age')->nullable();
            $table->string('nationality')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumers');
    }
};
